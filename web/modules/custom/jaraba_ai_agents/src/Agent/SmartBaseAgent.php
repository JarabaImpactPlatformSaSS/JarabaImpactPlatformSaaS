<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;

/**
 * Smart Base Agent with Model Routing.
 *
 * Extends BaseAgent with intelligent model selection based on task complexity.
 * Reduces costs by up to 40% by routing simple tasks to cheaper models.
 *
 * FIX-001: Restaurado contrato con BaseAgent — callAiApi() ahora invoca
 * buildSystemPrompt() (identidad + brand voice + unified context + vertical)
 * y registra observabilidad completa, preservando el contrato de BaseAgent
 * mientras añade model routing inteligente.
 */
abstract class SmartBaseAgent extends BaseAgent
{

    /**
     * The model router service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\ModelRouterService|null
     */
    protected ?ModelRouterService $modelRouter = NULL;

    /**
     * Sets the model router service.
     *
     * @param \Drupal\jaraba_ai_agents\Service\ModelRouterService $modelRouter
     *   The model router service.
     */
    public function setModelRouter(ModelRouterService $modelRouter): void
    {
        $this->modelRouter = $modelRouter;
    }

    /**
     * Calls the AI provider with intelligent model routing.
     *
     * FIX-001: Preserva el contrato completo de BaseAgent::callAiApi():
     * 1. buildSystemPrompt() — AI-IDENTITY-001 + Brand Voice + Unified Context
     * 2. observability->log() — Tracking completo de métricas
     * 3. Model routing inteligente vía ModelRouterService
     *
     * @param string $prompt
     *   The user prompt to send.
     * @param array $options
     *   Optional configuration:
     *   - temperature: float (default 0.7)
     *   - force_tier: string (fast|balanced|premium)
     *   - require_speed: bool
     *   - require_quality: bool.
     *
     * @return array
     *   Result array with success, data, routing info, and optional error.
     */
    protected function callAiApi(string $prompt, array $options = []): array
    {
        $startTime = microtime(TRUE);
        $success = FALSE;
        $inputTokens = 0;
        $outputTokens = 0;
        $modelId = '';
        $providerId = '';
        $tier = 'balanced';

        try {
            // 1. System prompt COMPLETO (identidad + brand voice + unified context + vertical).
            $systemPrompt = $this->buildSystemPrompt($prompt);

            // 2. Model routing inteligente.
            $routingConfig = $this->getRoutingConfig($prompt, $options);
            $tier = $routingConfig['tier'];
            $providerId = $routingConfig['provider_id'];
            $modelId = $routingConfig['model_id'];

            // 3. Llamada al provider via ai.provider framework.
            $provider = $this->aiProvider->createInstance($providerId);

            $chatInput = new ChatInput([
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $prompt),
            ]);

            $configuration = [
                'temperature' => $options['temperature'] ?? 0.7,
            ];

            $response = $provider->chat($chatInput, $modelId, $configuration);
            $text = $response->getNormalized()->getText();

            // Estimar tokens (aprox 4 caracteres por token).
            $inputTokens = (int) ceil((mb_strlen($systemPrompt) + mb_strlen($prompt)) / 4);
            $outputTokens = (int) ceil(mb_strlen($text) / 4);
            $success = TRUE;

            $this->logger->info('Smart Agent @agent ejecutado: tier=@tier, model=@model, tenant=@tenant', [
                '@agent' => $this->getAgentId(),
                '@tier' => $tier,
                '@model' => $modelId,
                '@tenant' => $this->tenantId ?? 'global',
            ]);

            $result = [
                'success' => TRUE,
                'data' => ['text' => $text],
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'agent_id' => $this->getAgentId(),
                'routing' => [
                    'tier' => $tier,
                    'model' => $modelId,
                    'estimated_cost' => $routingConfig['estimated_cost'],
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Smart AI Agent error: @msg', ['@msg' => $e->getMessage()]);
            $result = [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }

        // 4. Observabilidad OBLIGATORIA (siempre se registra, éxito o fallo).
        $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

        $this->observability->log([
            'agent_id' => $this->getAgentId(),
            'action' => $this->currentAction,
            'tier' => $tier,
            'model_id' => $modelId,
            'provider_id' => $providerId,
            'tenant_id' => $this->tenantId,
            'vertical' => $this->vertical,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'duration_ms' => $durationMs,
            'success' => $success,
        ]);

        return $result;
    }

    /**
     * Gets the routing configuration for the current task.
     *
     * @param string $prompt
     *   The prompt.
     * @param array $options
     *   Routing options.
     *
     * @return array
     *   Routing configuration.
     */
    protected function getRoutingConfig(string $prompt, array $options): array
    {
        // If no router or routing disabled, use default provider.
        if (!$this->modelRouter) {
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
            return [
                'provider_id' => $defaults['provider_id'] ?? 'anthropic',
                'model_id' => $defaults['model_id'] ?? 'claude-3-5-sonnet-20241022',
                'tier' => 'default',
                'estimated_cost' => 0,
            ];
        }

        // Use model router for intelligent selection.
        $taskType = $this->currentAction ?? 'general';
        return $this->modelRouter->route($taskType, $prompt, $options);
    }

    /**
     * Executes an action with automatic routing context.
     *
     * Override this in child classes to set currentAction before calling parent.
     *
     * @param string $action
     *   The action to execute.
     * @param array $context
     *   The context.
     *
     * @return array
     *   Execution result.
     */
    public function execute(string $action, array $context): array
    {
        $this->setCurrentAction($action);
        return $this->doExecute($action, $context);
    }

    /**
     * Actually executes the action.
     *
     * Child classes should implement this instead of execute().
     *
     * @param string $action
     *   The action.
     * @param array $context
     *   The context.
     *
     * @return array
     *   Result.
     */
    abstract protected function doExecute(string $action, array $context): array;

}
