<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Psr\Log\LoggerInterface;

/**
 * Smart Base Agent with Model Routing.
 *
 * Extends BaseAgent with intelligent model selection based on task complexity.
 * Reduces costs by up to 40% by routing simple tasks to cheaper models.
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
     * Sets the current action for routing context.
     *
     * @param string $action
     *   The action being executed.
     */
    protected function setCurrentAction(string $action): void
    {
        $this->currentAction = $action;
    }

    /**
     * Calls the AI provider with intelligent model routing.
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
        try {
            // Get routing decision.
            $routingConfig = $this->getRoutingConfig($prompt, $options);

            $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
            $systemPrompt = $this->getBrandVoicePrompt();

            $chatInput = new ChatInput([
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $prompt),
            ]);

            $configuration = [
                'temperature' => $options['temperature'] ?? 0.7,
            ];

            $response = $provider->chat($chatInput, $routingConfig['model_id'], $configuration);
            $text = $response->getNormalized()->getText();

            $this->logger->info('Smart Agent @agent executed: tier=@tier, model=@model, tenant=@tenant', [
                '@agent' => $this->getAgentId(),
                '@tier' => $routingConfig['tier'],
                '@model' => $routingConfig['model_id'],
                '@tenant' => $this->tenantId ?? 'global',
            ]);

            return [
                'success' => TRUE,
                'data' => ['text' => $text],
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'agent_id' => $this->getAgentId(),
                'routing' => [
                    'tier' => $routingConfig['tier'],
                    'model' => $routingConfig['model_id'],
                    'estimated_cost' => $routingConfig['estimated_cost'],
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Smart AI Agent error: @msg', ['@msg' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
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
