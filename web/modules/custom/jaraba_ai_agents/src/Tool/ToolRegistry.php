<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

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
     * @param string $toolId
     *   ID de la herramienta.
     * @param array $params
     *   Parámetros de entrada.
     * @param array $context
     *   Contexto de ejecución.
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

        // Ejecutar.
        try {
            $result = $tool->execute($params, $context);
            $this->logger->info('Tool @id executed: success=@success', [
                '@id' => $toolId,
                '@success' => $result['success'] ? 'true' : 'false',
            ]);
            return $result;
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

}
