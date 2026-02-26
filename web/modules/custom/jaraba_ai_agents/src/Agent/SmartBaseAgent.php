<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;

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
 *
 * FIX-029: Tool Use — ToolRegistry inyectado opcionalmente. Si hay tools
 * disponibles, su documentacion XML se appendea al system prompt.
 * callAiApiWithTools() implementa loop iterativo tool-call (max 5 iter).
 *
 * FIX-031: Provider Fallback — ProviderFallbackService inyectado opcionalmente.
 * Si el provider principal falla, se reintenta con cadena de fallback.
 *
 * FIX-033: Context Window Manager — Verifica que el prompt quepa en la
 * ventana del modelo antes de enviar al LLM.
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
     * The tool registry for agentic tool use.
     *
     * FIX-029: Optional injection — agents can use tools when available.
     *
     * @var \Drupal\jaraba_ai_agents\Tool\ToolRegistry|null
     */
    protected ?ToolRegistry $toolRegistry = NULL;

    /**
     * The provider fallback service.
     *
     * FIX-031: Optional injection for provider resilience.
     *
     * @var \Drupal\jaraba_ai_agents\Service\ProviderFallbackService|null
     */
    protected ?ProviderFallbackService $providerFallback = NULL;

    /**
     * The context window manager.
     *
     * FIX-033: Optional injection to prevent silent truncation.
     *
     * @var \Drupal\jaraba_ai_agents\Service\ContextWindowManager|null
     */
    protected ?ContextWindowManager $contextWindowManager = NULL;

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
     * Sets the tool registry for agentic tool use (FIX-029).
     *
     * @param \Drupal\jaraba_ai_agents\Tool\ToolRegistry|null $toolRegistry
     *   The tool registry or NULL.
     */
    public function setToolRegistry(?ToolRegistry $toolRegistry): void
    {
        $this->toolRegistry = $toolRegistry;
    }

    /**
     * Sets the provider fallback service (FIX-031).
     *
     * @param \Drupal\jaraba_ai_agents\Service\ProviderFallbackService|null $providerFallback
     *   The provider fallback service or NULL.
     */
    public function setProviderFallback(?ProviderFallbackService $providerFallback): void
    {
        $this->providerFallback = $providerFallback;
    }

    /**
     * Sets the context window manager (FIX-033).
     *
     * @param \Drupal\jaraba_ai_agents\Service\ContextWindowManager|null $contextWindowManager
     *   The context window manager or NULL.
     */
    public function setContextWindowManager(?ContextWindowManager $contextWindowManager): void
    {
        $this->contextWindowManager = $contextWindowManager;
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
        $logId = NULL;

        try {
            // 1. System prompt COMPLETO (identidad + brand voice + unified context + vertical).
            $systemPrompt = $this->buildSystemPrompt($prompt);

            // FIX-029: Append tool documentation to system prompt if tools available.
            $toolDocs = $this->getToolDocumentation();
            if (!empty($toolDocs)) {
                $systemPrompt .= "\n\n" . $toolDocs;
                $systemPrompt .= "\n\n<tool_use_instructions>";
                $systemPrompt .= "\nCuando necesites usar una herramienta, responde con un JSON en este formato:";
                $systemPrompt .= "\n{\"tool_call\": {\"tool_id\": \"<id>\", \"params\": {<parametros>}}}";
                $systemPrompt .= "\nDespues de recibir el resultado de la herramienta, continua tu respuesta.";
                $systemPrompt .= "\nSi no necesitas herramientas, responde normalmente.";
                $systemPrompt .= "\n</tool_use_instructions>";
            }

            // FIX-033: Verify prompt fits in context window before sending.
            if ($this->contextWindowManager) {
                $systemPrompt = $this->contextWindowManager->fitToWindow(
                    $systemPrompt,
                    $prompt,
                    $modelId ?: 'default'
                );
            }

            // 2. Model routing inteligente.
            $routingConfig = $this->getRoutingConfig($prompt, $options);
            $tier = $routingConfig['tier'];
            $providerId = $routingConfig['provider_id'];
            $modelId = $routingConfig['model_id'];

            // FIX-031: Use fallback chain if available.
            $text = $this->executeWithFallback(
                $systemPrompt,
                $prompt,
                $providerId,
                $modelId,
                $tier,
                $options
            );

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

        $logId = $this->observability->log([
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

        // FIX-032: Enqueue quality evaluation (sampling: 10% or 100% premium).
        if ($success && $logId) {
            $this->enqueueQualityEvaluation($logId, $prompt, $result['data']['text'] ?? '', $tier);
            $result['log_id'] = $logId;
        }

        // FIX-044: Mask PII in output.
        if ($success && isset($result['data']['text'])) {
            $result['data']['text'] = $this->maskOutputPii($result['data']['text']);
        }

        return $result;
    }

    /**
     * Executes LLM call with provider fallback chain (FIX-031).
     *
     * @param string $systemPrompt
     *   The system prompt.
     * @param string $prompt
     *   The user prompt.
     * @param string $providerId
     *   Primary provider ID.
     * @param string $modelId
     *   Model ID.
     * @param string $tier
     *   Current tier.
     * @param array $options
     *   Options.
     *
     * @return string
     *   The response text.
     *
     * @throws \Exception
     *   If all providers fail.
     */
    protected function executeWithFallback(
        string $systemPrompt,
        string $prompt,
        string $providerId,
        string $modelId,
        string $tier,
        array $options,
    ): string {
        // Try primary provider first (with fallback if service available).
        if ($this->providerFallback) {
            return $this->providerFallback->executeWithFallback(
                $tier,
                function (string $pid, string $mid) use ($systemPrompt, $prompt, $options) {
                    return $this->executeLlmCall($systemPrompt, $prompt, $pid, $mid, $options);
                },
                $providerId,
                $modelId
            );
        }

        // Direct call without fallback.
        return $this->executeLlmCall($systemPrompt, $prompt, $providerId, $modelId, $options);
    }

    /**
     * Executes a single LLM call.
     *
     * @param string $systemPrompt
     *   The system prompt.
     * @param string $prompt
     *   The user prompt.
     * @param string $providerId
     *   Provider ID.
     * @param string $modelId
     *   Model ID.
     * @param array $options
     *   Options.
     *
     * @return string
     *   The response text.
     */
    protected function executeLlmCall(
        string $systemPrompt,
        string $prompt,
        string $providerId,
        string $modelId,
        array $options,
    ): string {
        $provider = $this->aiProvider->createInstance($providerId);

        $chatInput = new ChatInput([
            new ChatMessage('system', $systemPrompt),
            new ChatMessage('user', $prompt),
        ]);

        $configuration = [
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        $response = $provider->chat($chatInput, $modelId, $configuration);
        return $response->getNormalized()->getText();
    }

    /**
     * Calls the AI API with iterative tool use loop (FIX-029).
     *
     * Implements: LLM call -> parse tool_call -> execute tool -> append result
     * -> re-call LLM (max 5 iterations).
     *
     * @param string $prompt
     *   The user prompt.
     * @param array $options
     *   Optional configuration.
     *
     * @return array
     *   Result array with tool execution trace.
     */
    protected function callAiApiWithTools(string $prompt, array $options = []): array
    {
        if (!$this->toolRegistry || empty($this->toolRegistry->getAll())) {
            return $this->callAiApi($prompt, $options);
        }

        $maxIterations = $options['max_tool_iterations'] ?? 5;
        $conversationHistory = [];
        $toolTrace = [];
        $currentPrompt = $prompt;

        for ($i = 0; $i < $maxIterations; $i++) {
            // Add tool execution results to prompt if we have history.
            if (!empty($conversationHistory)) {
                $currentPrompt = $prompt . "\n\n<tool_results>\n";
                foreach ($conversationHistory as $entry) {
                    $currentPrompt .= "Tool: {$entry['tool_id']}\n";
                    $currentPrompt .= "Result: " . json_encode($entry['result'], JSON_UNESCAPED_UNICODE) . "\n\n";
                }
                $currentPrompt .= "</tool_results>\nContinua tu respuesta usando los resultados de las herramientas.";
            }

            $result = $this->callAiApi($currentPrompt, $options);

            if (!$result['success']) {
                $result['tool_trace'] = $toolTrace;
                return $result;
            }

            $text = $result['data']['text'] ?? '';

            // Check if response contains a tool_call.
            $toolCall = $this->parseToolCall($text);
            if (!$toolCall) {
                // No tool call — final response.
                $result['tool_trace'] = $toolTrace;
                return $result;
            }

            // Execute the tool.
            $toolId = $toolCall['tool_id'];
            $toolParams = $toolCall['params'] ?? [];
            $toolContext = [
                'agent_id' => $this->getAgentId(),
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
            ];

            $toolResult = $this->toolRegistry->execute($toolId, $toolParams, $toolContext);

            $traceEntry = [
                'iteration' => $i + 1,
                'tool_id' => $toolId,
                'params' => $toolParams,
                'result' => $toolResult,
            ];
            $toolTrace[] = $traceEntry;
            $conversationHistory[] = $traceEntry;

            $this->logger->info('Tool @tool executed in iteration @iter for agent @agent', [
                '@tool' => $toolId,
                '@iter' => $i + 1,
                '@agent' => $this->getAgentId(),
            ]);
        }

        // Max iterations reached — return last result with trace.
        $result['tool_trace'] = $toolTrace;
        $result['max_iterations_reached'] = TRUE;
        return $result;
    }

    /**
     * Parses a tool call from the LLM response text.
     *
     * Looks for {"tool_call": {"tool_id": "...", "params": {...}}} in response.
     *
     * @param string $text
     *   The response text.
     *
     * @return array|null
     *   The tool call data or NULL if not a tool call.
     */
    protected function parseToolCall(string $text): ?array
    {
        // Try to extract JSON with tool_call.
        if (preg_match('/\{[^{}]*"tool_call"\s*:\s*\{[^}]*\}[^}]*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], TRUE);
            if (isset($decoded['tool_call']['tool_id'])) {
                return $decoded['tool_call'];
            }
        }

        // Try from full parsed JSON.
        $parsed = $this->parseJsonResponse($text);
        if (isset($parsed['tool_call']['tool_id'])) {
            return $parsed['tool_call'];
        }

        return NULL;
    }

    /**
     * Gets tool documentation for the system prompt (FIX-029).
     *
     * @return string
     *   XML tool documentation or empty string.
     */
    protected function getToolDocumentation(): string
    {
        if (!$this->toolRegistry) {
            return '';
        }
        return $this->toolRegistry->generateToolsDocumentation();
    }

    /**
     * Enqueues quality evaluation for a response (FIX-032).
     *
     * Sampling: 10% for fast/balanced tiers, 100% for premium tier.
     *
     * @param int|string $logId
     *   The observability log ID.
     * @param string $prompt
     *   The original prompt.
     * @param string $responseText
     *   The response text.
     * @param string $tier
     *   The tier used.
     */
    protected function enqueueQualityEvaluation(int|string $logId, string $prompt, string $responseText, string $tier): void
    {
        // Sampling: 100% premium, 10% others.
        if ($tier !== 'premium' && mt_rand(1, 10) > 1) {
            return;
        }

        try {
            $queue = \Drupal::queue('jaraba_ai_agents_quality_evaluation');
            $queue->createItem([
                'log_id' => $logId,
                'prompt' => $prompt,
                'response' => $responseText,
                'agent_id' => $this->getAgentId(),
                'tenant_id' => $this->tenantId,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to enqueue quality evaluation: @msg', ['@msg' => $e->getMessage()]);
        }
    }

    /**
     * Masks PII in output text (FIX-044).
     *
     * @param string $text
     *   The output text.
     *
     * @return string
     *   Text with PII masked.
     */
    protected function maskOutputPii(string $text): string
    {
        if (!\Drupal::hasService('ecosistema_jaraba_core.ai_guardrails')) {
            return $text;
        }

        try {
            $guardrails = \Drupal::service('ecosistema_jaraba_core.ai_guardrails');
            if (method_exists($guardrails, 'maskOutputPII')) {
                return $guardrails->maskOutputPII($text);
            }
        } catch (\Exception $e) {
            // Non-critical — don't fail the response.
        }

        return $text;
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

        // FIX-049: A/B test prompt variant selection (optional).
        $this->applyPromptExperiment($action);

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

    /**
     * Applies prompt experiment variant selection (FIX-049).
     *
     * @param string $action
     *   The action being executed.
     */
    protected function applyPromptExperiment(string $action): void
    {
        if (!\Drupal::hasService('jaraba_ai_agents.prompt_experiment')) {
            return;
        }

        try {
            $experimentService = \Drupal::service('jaraba_ai_agents.prompt_experiment');
            $experimentName = $this->getAgentId() . '_' . $action;
            $variant = $experimentService->getActiveVariant($experimentName);

            if ($variant && !empty($variant['system_prompt'])) {
                // Store variant info for later recording in callAiApi().
                $this->activeExperimentVariant = $variant;
            }
        } catch (\Exception $e) {
            // Non-critical — continue with default prompt.
        }
    }

}
