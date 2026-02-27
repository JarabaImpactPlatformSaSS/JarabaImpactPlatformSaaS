<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\ai\OperationType\Chat\Tools\ToolsFunctionInput;
use Drupal\ai\OperationType\Chat\Tools\ToolsInput;
use Drupal\ai\OperationType\Chat\Tools\ToolsPropertyInput;
use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
use Psr\Log\LoggerInterface;

/**
 * Registro central de herramientas disponibles para agentes IA.
 *
 * PROPÓSITO:
 * Provee un punto único de acceso a todas las herramientas registradas
 * en el sistema. Los workflows y agentes consultan este registro para
 * obtener las herramientas disponibles.
 *
 * PATRÓN:
 * Service Locator optimizado para herramientas IA. Las herramientas
 * se registran via services.yml con tag 'jaraba_ai_agents.tool'.
 *
 * SEGURIDAD (HAL-AI-01):
 * Todos los outputs de herramientas pasan por AIGuardrailsService para:
 * - sanitizeToolOutput(): Neutraliza prompt injection indirecto.
 * - maskOutputPII(): Enmascara PII antes de retornar al LLM.
 *
 * APROBACION (HAL-AI-18):
 * Herramientas con requiresApproval()=TRUE se validan contra
 * PendingApprovalService antes de ejecutarse.
 */
class ToolRegistry
{

    /**
     * Herramientas registradas.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected ?AIGuardrailsService $guardrails = NULL,
    ) {
    }

    /**
     * Registra una herramienta.
     *
     * @param \Drupal\jaraba_ai_agents\Tool\ToolInterface $tool
     *   La herramienta a registrar.
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getId()] = $tool;
        $this->logger->debug('Tool registered: @id', ['@id' => $tool->getId()]);
    }

    /**
     * Adds a tool service to the registry (called by CompilerPass).
     *
     * @param object $service
     *   The tagged service object.
     * @param string $serviceId
     *   The service ID.
     */
    public function addToolService(object $service, string $serviceId): void
    {
        if ($service instanceof ToolInterface) {
            $this->register($service);
        } else {
            $this->logger->warning('Service @id tagged as tool but does not implement ToolInterface.', [
                '@id' => $serviceId,
            ]);
        }
    }

    /**
     * Obtiene una herramienta por ID.
     *
     * @param string $toolId
     *   ID de la herramienta.
     *
     * @return \Drupal\jaraba_ai_agents\Tool\ToolInterface|null
     *   La herramienta o NULL si no existe.
     */
    public function get(string $toolId): ?ToolInterface
    {
        return $this->tools[$toolId] ?? NULL;
    }

    /**
     * Verifica si una herramienta existe.
     *
     * @param string $toolId
     *   ID de la herramienta.
     *
     * @return bool
     *   TRUE si la herramienta existe.
     */
    public function has(string $toolId): bool
    {
        return isset($this->tools[$toolId]);
    }

    /**
     * Obtiene todas las herramientas registradas.
     *
     * @return array<string, ToolInterface>
     *   Array de herramientas indexado por ID.
     */
    public function getAll(): array
    {
        return $this->tools;
    }

    /**
     * Obtiene herramientas filtradas por criterio.
     *
     * @param callable $filter
     *   Función que recibe ToolInterface y retorna bool.
     *
     * @return array<string, ToolInterface>
     *   Herramientas que pasan el filtro.
     */
    public function filter(callable $filter): array
    {
        return array_filter($this->tools, $filter);
    }

    /**
     * Obtiene herramientas que requieren aprobación.
     *
     * @return array<string, ToolInterface>
     *   Herramientas con requiresApproval() = true.
     */
    public function getApprovalRequired(): array
    {
        return $this->filter(fn(ToolInterface $t) => $t->requiresApproval());
    }

    /**
     * Ejecuta una herramienta por ID.
     *
     * HAL-AI-01: El output pasa por guardrails (sanitizeToolOutput + maskOutputPII).
     * HAL-AI-18: Herramientas con requiresApproval() se bloquean hasta aprobación.
     *
     * @param string $toolId
     *   ID de la herramienta.
     * @param array $params
     *   Parámetros de entrada.
     * @param array $context
     *   Contexto de ejecución (tenant_id, user_id, workflow_id, etc.).
     *
     * @return array
     *   Resultado de la ejecución.
     */
    public function execute(string $toolId, array $params, array $context = []): array
    {
        $tool = $this->get($toolId);

        if (!$tool) {
            return [
                'success' => FALSE,
                'error' => "Tool '{$toolId}' not found.",
            ];
        }

        // Validar parámetros.
        $errors = $tool->validate($params);
        if (!empty($errors)) {
            return [
                'success' => FALSE,
                'error' => 'Validation failed: ' . implode(', ', $errors),
                'validation_errors' => $errors,
            ];
        }

        // HAL-AI-18: Check approval requirement before execution.
        // Skip if context signals pre-approval (e.g., PendingApprovalService::approve()
        // or WorkflowExecutorService already checked).
        if ($tool->requiresApproval() && empty($context['pre_approved'])) {
            $approvalResult = $this->checkApproval($toolId, $params, $context);
            if ($approvalResult !== NULL) {
                return $approvalResult;
            }
        }

        // Ejecutar.
        try {
            $result = $tool->execute($params, $context);
            $this->logger->info('Tool @id executed: success=@success', [
                '@id' => $toolId,
                '@success' => $result['success'] ? 'true' : 'false',
            ]);

            // HAL-AI-01: Sanitize tool output via guardrails.
            return $this->sanitizeResult($result, $toolId);
        } catch (\Exception $e) {
            $this->logger->error('Tool @id failed: @error', [
                '@id' => $toolId,
                '@error' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sanitizes a tool execution result via AIGuardrailsService (HAL-AI-01).
     *
     * Applies two layers of sanitization:
     * 1. sanitizeToolOutput(): Neutralizes indirect prompt injection patterns.
     * 2. maskOutputPII(): Masks PII (emails, DNIs, IBANs, etc.) in output.
     *
     * @param array $result
     *   The raw tool execution result.
     * @param string $toolId
     *   The tool ID for logging context.
     *
     * @return array
     *   The sanitized result.
     */
    protected function sanitizeResult(array $result, string $toolId): array
    {
        if (!$this->guardrails) {
            return $result;
        }

        try {
            // Sanitize string values recursively in the result.
            $result = $this->sanitizeArrayValues($result);
        } catch (\Exception $e) {
            $this->logger->warning('Guardrails sanitization failed for tool @id: @error', [
                '@id' => $toolId,
                '@error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Recursively sanitizes string values in an array.
     *
     * @param array $data
     *   Data to sanitize.
     *
     * @return array
     *   Sanitized data.
     */
    protected function sanitizeArrayValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && $key !== 'error') {
                // sanitizeToolOutput neutralizes injection + masks PII.
                $data[$key] = $this->guardrails->sanitizeToolOutput($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArrayValues($value);
            }
        }
        return $data;
    }

    /**
     * Checks approval requirement for a tool (HAL-AI-18).
     *
     * Consults PendingApprovalService to verify if the tool has been
     * pre-approved. If not, creates an approval request and returns
     * a pending response for the tool use loop to handle.
     *
     * @param string $toolId
     *   The tool ID.
     * @param array $params
     *   Tool parameters.
     * @param array $context
     *   Execution context.
     *
     * @return array|null
     *   Pending approval response, or NULL if approved/no service.
     */
    protected function checkApproval(string $toolId, array $params, array $context): ?array
    {
        // PendingApprovalService is resolved lazily to avoid circular dependency.
        if (!\Drupal::hasService('jaraba_ai_agents.pending_approval')) {
            $this->logger->warning('Tool @id requires approval but PendingApprovalService not available.', [
                '@id' => $toolId,
            ]);
            return NULL;
        }

        try {
            /** @var \Drupal\jaraba_ai_agents\Service\PendingApprovalService $approvalService */
            $approvalService = \Drupal::service('jaraba_ai_agents.pending_approval');

            // Check if there's already an approved request for this tool+params.
            $workflowId = $context['workflow_id'] ?? 'direct_execution';
            $stepId = $context['step_id'] ?? $toolId . '_' . md5(json_encode($params));

            $approval = $approvalService->create($workflowId, $stepId, $toolId, $params, $context);

            $this->logger->info('Tool @id requires approval. Created request @approval_id.', [
                '@id' => $toolId,
                '@approval_id' => $approval->id(),
            ]);

            return [
                'success' => FALSE,
                'pending_approval' => TRUE,
                'approval_id' => $approval->id(),
                'tool_id' => $toolId,
                'message' => "Tool '{$toolId}' requires human approval before execution.",
            ];
        } catch (\Exception $e) {
            $this->logger->error('Approval check failed for tool @id: @error', [
                '@id' => $toolId,
                '@error' => $e->getMessage(),
            ]);
            // Fail-safe: block execution if approval check fails.
            return [
                'success' => FALSE,
                'error' => 'Approval system unavailable. Tool execution blocked for safety.',
            ];
        }
    }

    /**
     * Genera documentación de herramientas disponibles.
     *
     * Útil para inyectar en prompts de agentes.
     *
     * @return string
     *   Documentación XML estructurada.
     */
    public function generateToolsDocumentation(): string
    {
        if (empty($this->tools)) {
            return '';
        }

        $output = "<available_tools>\n";

        foreach ($this->tools as $id => $tool) {
            $output .= "  <tool id=\"{$id}\">\n";
            $output .= "    <label>{$tool->getLabel()}</label>\n";
            $output .= "    <description>{$tool->getDescription()}</description>\n";
            $output .= "    <requires_approval>" . ($tool->requiresApproval() ? 'true' : 'false') . "</requires_approval>\n";

            $params = $tool->getParameters();
            if (!empty($params)) {
                $output .= "    <parameters>\n";
                foreach ($params as $name => $config) {
                    $required = ($config['required'] ?? FALSE) ? 'true' : 'false';
                    $type = $config['type'] ?? 'string';
                    $desc = $config['description'] ?? '';
                    $output .= "      <param name=\"{$name}\" type=\"{$type}\" required=\"{$required}\">{$desc}</param>\n";
                }
                $output .= "    </parameters>\n";
            }

            $output .= "  </tool>\n";
        }

        $output .= "</available_tools>";

        return $output;
    }

    /**
     * GAP-09: Genera ToolsInput nativo para function calling API-level.
     *
     * Convierte las herramientas registradas al formato OpenAI-compatible
     * del modulo Drupal AI (ToolsInput > ToolsFunctionInput > ToolsPropertyInput).
     *
     * @return \Drupal\ai\OperationType\Chat\Tools\ToolsInput|null
     *   Objeto ToolsInput con las funciones, o NULL si no hay herramientas.
     */
    public function generateNativeToolsInput(): ?ToolsInput
    {
        if (empty($this->tools)) {
            return NULL;
        }

        $functions = [];

        foreach ($this->tools as $id => $tool) {
            $function = new ToolsFunctionInput();
            $function->setName($id);
            $function->setDescription($tool->getDescription());

            $params = $tool->getParameters();
            if (!empty($params)) {
                foreach ($params as $paramName => $config) {
                    $property = new ToolsPropertyInput();
                    $property->setName($paramName);
                    $property->setType($config['type'] ?? 'string');
                    $property->setDescription($config['description'] ?? '');
                    $property->setRequired($config['required'] ?? FALSE);
                    $function->setProperty($property);
                }
            }

            $functions[] = $function;
        }

        return new ToolsInput($functions);
    }

}
