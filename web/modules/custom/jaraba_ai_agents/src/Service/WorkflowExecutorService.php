<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Entity\AIWorkflow;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\jaraba_ai_agents\Entity\PendingApproval;
use Psr\Log\LoggerInterface;

/**
 * Executes AI Workflows by chaining agent actions.
 *
 * Handles step execution, input/output mapping, conditions, and error handling.
 * Supports both agent actions and tool executions.
 */
class WorkflowExecutorService
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The agent orchestrator.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AgentOrchestrator
     */
    protected AgentOrchestrator $orchestrator;

    /**
     * The observability service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService
     */
    protected AIObservabilityService $observability;

    /**
     * The tool registry.
     *
     * @var \Drupal\jaraba_ai_agents\Tool\ToolRegistry
     */
    protected ToolRegistry $toolRegistry;

    /**
     * The pending approval service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\PendingApprovalService|null
     */
    protected ?PendingApprovalService $pendingApprovalService = NULL;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a WorkflowExecutorService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AgentOrchestrator $orchestrator,
        AIObservabilityService $observability,
        LoggerInterface $logger,
        ?ToolRegistry $toolRegistry = NULL,
        ?PendingApprovalService $pendingApprovalService = NULL,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->orchestrator = $orchestrator;
        $this->observability = $observability;
        $this->logger = $logger;
        $this->toolRegistry = $toolRegistry ?? new ToolRegistry($logger);
        $this->pendingApprovalService = $pendingApprovalService;
    }

    /**
     * Executes a workflow by ID.
     *
     * @param string $workflowId
     *   The workflow ID.
     * @param array $initialContext
     *   Initial context/inputs for the workflow.
     *
     * @return array
     *   Execution result with all step outputs.
     */
    public function execute(string $workflowId, array $initialContext = []): array
    {
        $workflow = $this->loadWorkflow($workflowId);

        if (!$workflow) {
            return [
                'success' => FALSE,
                'error' => "Workflow '{$workflowId}' not found.",
            ];
        }

        if (!$workflow->status()) {
            return [
                'success' => FALSE,
                'error' => "Workflow '{$workflowId}' is disabled.",
            ];
        }

        return $this->executeWorkflow($workflow, $initialContext);
    }

    /**
     * Executes a workflow entity.
     *
     * @param \Drupal\jaraba_ai_agents\Entity\AIWorkflow $workflow
     *   The workflow entity.
     * @param array $context
     *   Initial context.
     *
     * @return array
     *   Execution result.
     */
    protected function executeWorkflow(AIWorkflow $workflow, array $context): array
    {
        $startTime = microtime(TRUE);
        $stepResults = [];
        $workflowContext = $context;

        // Validate workflow.
        $errors = $workflow->validate();
        if (!empty($errors)) {
            return [
                'success' => FALSE,
                'error' => 'Workflow validation failed: ' . implode(', ', $errors),
            ];
        }

        // Check global conditions.
        if (!$this->evaluateConditions($workflow->getConditions(), $workflowContext)) {
            return [
                'success' => FALSE,
                'error' => 'Workflow conditions not met.',
                'skipped' => TRUE,
            ];
        }

        // Get entry step.
        $currentStepId = $workflow->getEntryStep();

        $this->logger->info('Starting workflow @id with @steps steps', [
            '@id' => $workflow->id(),
            '@steps' => count($workflow->getSteps()),
        ]);

        // Execute steps in sequence.
        while ($currentStepId !== NULL) {
            $step = $workflow->getStep($currentStepId);

            if (!$step) {
                return [
                    'success' => FALSE,
                    'error' => "Step '{$currentStepId}' not found.",
                    'step_results' => $stepResults,
                ];
            }

            // Check step conditions.
            if (!$this->evaluateConditions($step['conditions'] ?? [], $workflowContext)) {
                $this->logger->info('Step @step skipped: conditions not met', ['@step' => $currentStepId]);
                $currentStepId = $step['on_success'] ?? NULL;
                continue;
            }

            // Execute step.
            $stepResult = $this->executeStep($currentStepId, $step, $workflowContext);
            $stepResults[$currentStepId] = $stepResult;

            // Update context with step output.
            if ($stepResult['success']) {
                $workflowContext['steps'][$currentStepId] = $stepResult['data'] ?? [];
                $workflowContext['last_output'] = $stepResult['data'] ?? [];
                $currentStepId = $step['on_success'] ?? NULL;
            } else {
                // Handle failure.
                $onFailure = $step['on_failure'] ?? 'abort';

                if ($onFailure === 'abort') {
                    $abortDuration = (int) ((microtime(TRUE) - $startTime) * 1000);

                    // FIX-021: Log observability for failed workflow.
                    $this->observability->log([
                        'agent_id' => 'workflow_executor',
                        'action' => 'execute_workflow',
                        'tier' => 'balanced',
                        'model_id' => 'workflow',
                        'provider_id' => 'internal',
                        'tenant_id' => $context['tenant_id'] ?? 0,
                        'vertical' => $context['vertical'] ?? 'platform',
                        'input_tokens' => 0,
                        'output_tokens' => 0,
                        'duration_ms' => $abortDuration,
                        'success' => FALSE,
                    ]);

                    return [
                        'success' => FALSE,
                        'error' => "Step '{$currentStepId}' failed: " . ($stepResult['error'] ?? 'Unknown error'),
                        'failed_step' => $currentStepId,
                        'step_results' => $stepResults,
                        'duration_ms' => $abortDuration,
                    ];
                }

                // Continue to failure handler step.
                $currentStepId = $onFailure;
            }
        }

        $duration = (int) ((microtime(TRUE) - $startTime) * 1000);

        $this->logger->info('Workflow @id completed in @duration ms', [
            '@id' => $workflow->id(),
            '@duration' => $duration,
        ]);

        // FIX-021: Log observability for workflow execution.
        $this->observability->log([
            'agent_id' => 'workflow_executor',
            'action' => 'execute_workflow',
            'tier' => 'balanced',
            'model_id' => 'workflow',
            'provider_id' => 'internal',
            'tenant_id' => $context['tenant_id'] ?? 0,
            'vertical' => $context['vertical'] ?? 'platform',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'duration_ms' => $duration,
            'success' => TRUE,
        ]);

        return [
            'success' => TRUE,
            'workflow_id' => $workflow->id(),
            'step_results' => $stepResults,
            'final_output' => $workflowContext['last_output'] ?? [],
            'duration_ms' => $duration,
        ];
    }

    /**
     * Executes a single workflow step.
     *
     * Supports two execution modes:
     * - Agent mode: Uses agent_id + action to invoke an agent via orchestrator.
     * - Tool mode: Uses tool_id to invoke a registered tool directly.
     *
     * @param string $stepId
     *   The step ID.
     * @param array $step
     *   Step configuration.
     * @param array $context
     *   Current workflow context.
     *
     * @return array
     *   Step execution result.
     */
    protected function executeStep(string $stepId, array $step, array $context): array
    {
        $inputMapping = $step['input_mapping'] ?? [];

        // Build step inputs from context using mapping.
        $stepInputs = $this->mapInputs($inputMapping, $context);

        // Determine execution mode: Tool or Agent.
        if (!empty($step['tool_id'])) {
            return $this->executeToolStep($stepId, $step, $stepInputs, $context);
        }

        return $this->executeAgentStep($stepId, $step, $stepInputs);
    }

    /**
     * Executes a tool-based step.
     *
     * Si la herramienta requiere aprobación, crea una entrada en la cola
     * de aprobaciones en lugar de ejecutar directamente.
     */
    protected function executeToolStep(string $stepId, array $step, array $params, array $context): array
    {
        $toolId = $step['tool_id'];
        $workflowId = $context['workflow_id'] ?? 'unknown';

        $this->logger->info('Executing tool step @step: @tool', [
            '@step' => $stepId,
            '@tool' => $toolId,
        ]);

        // Check if tool requires approval.
        $tool = $this->toolRegistry->get($toolId);
        if ($tool && $tool->requiresApproval() && $this->pendingApprovalService) {
            // Create pending approval instead of executing.
            $approval = $this->pendingApprovalService->create(
                $workflowId,
                $stepId,
                $toolId,
                $params,
                $context
            );

            $this->logger->info('Tool @tool requires approval, created pending approval @id', [
                '@tool' => $toolId,
                '@id' => $approval->id(),
            ]);

            return [
                'success' => TRUE,
                'pending_approval' => TRUE,
                'approval_id' => $approval->id(),
                'message' => 'Action requires approval. Approval ID: ' . $approval->id(),
            ];
        }

        // Execute directly.
        return $this->toolRegistry->execute($toolId, $params, $context);
    }

    /**
     * Executes an agent-based step with retry logic.
     */
    protected function executeAgentStep(string $stepId, array $step, array $stepInputs): array
    {
        $agentId = $step['agent_id'] ?? '';
        $action = $step['action'] ?? '';

        if (empty($agentId) || empty($action)) {
            return [
                'success' => FALSE,
                'error' => "Step '{$stepId}' missing agent_id or action.",
            ];
        }

        $this->logger->info('Executing agent step @step: @agent.@action', [
            '@step' => $stepId,
            '@agent' => $agentId,
            '@action' => $action,
        ]);

        // Retry logic.
        $retryCount = $step['retry_count'] ?? 0;
        $lastError = NULL;

        for ($attempt = 0; $attempt <= $retryCount; $attempt++) {
            try {
                $result = $this->orchestrator->execute($agentId, $action, $stepInputs);

                if ($result['success']) {
                    return $result;
                }

                $lastError = $result['error'] ?? 'Unknown error';
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }

            if ($attempt < $retryCount) {
                $this->logger->warning('Step @step attempt @attempt failed, retrying...', [
                    '@step' => $stepId,
                    '@attempt' => $attempt + 1,
                ]);
                usleep(500000); // 500ms delay between retries.
            }
        }

        return [
            'success' => FALSE,
            'error' => $lastError,
        ];
    }

    /**
     * Maps inputs from context using defined mappings.
     *
     * @param array $inputMapping
     *   Input mapping configuration.
     * @param array $context
     *   Current context.
     *
     * @return array
     *   Mapped inputs.
     */
    protected function mapInputs(array $inputMapping, array $context): array
    {
        $inputs = [];

        foreach ($inputMapping as $targetKey => $sourcePath) {
            $inputs[$targetKey] = $this->getValueFromPath($sourcePath, $context);
        }

        return $inputs;
    }

    /**
     * Gets a value from a context path like "steps.step1.data.content".
     */
    protected function getValueFromPath(string $path, array $context): mixed
    {
        $keys = explode('.', $path);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return NULL;
            }
        }

        return $value;
    }

    /**
     * Evaluates workflow conditions.
     *
     * @param array $conditions
     *   Conditions to evaluate.
     * @param array $context
     *   Current context.
     *
     * @return bool
     *   TRUE if all conditions pass.
     */
    protected function evaluateConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $path = $condition['path'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $expectedValue = $condition['value'] ?? NULL;

            $actualValue = $this->getValueFromPath($path, $context);

            $passes = match ($operator) {
                'equals' => $actualValue === $expectedValue,
                'not_equals' => $actualValue !== $expectedValue,
                'exists' => $actualValue !== NULL,
                'not_exists' => $actualValue === NULL,
                'contains' => is_string($actualValue) && str_contains($actualValue, (string) $expectedValue),
                'greater_than' => is_numeric($actualValue) && $actualValue > $expectedValue,
                'less_than' => is_numeric($actualValue) && $actualValue < $expectedValue,
                default => FALSE,
            };

            if (!$passes) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Loads a workflow by ID.
     */
    protected function loadWorkflow(string $workflowId): ?AIWorkflow
    {
        try {
            /** @var \Drupal\jaraba_ai_agents\Entity\AIWorkflow|null $workflow */
            $workflow = $this->entityTypeManager
                ->getStorage('ai_workflow')
                ->load($workflowId);
            return $workflow;
        } catch (\Exception $e) {
            $this->logger->error('Error loading workflow: @msg', ['@msg' => $e->getMessage()]);
            return NULL;
        }
    }

}
