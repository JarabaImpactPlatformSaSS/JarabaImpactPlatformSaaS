<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\Tools\ToolsInput;
use Drupal\jaraba_agents\Service\AgentLongTermMemoryService;
use Drupal\jaraba_ai_agents\Service\AgentSelfReflectionService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\VerifierAgentService;
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
     * Agent long-term memory service (S3-01).
     *
     * Optional: remembers context across sessions for personalized responses.
     *
     * @var \Drupal\jaraba_agents\Service\AgentLongTermMemoryService|null
     */
    protected ?AgentLongTermMemoryService $longTermMemory = NULL;

    /**
     * Pre-delivery verifier agent (GAP-L5-A).
     *
     * Optional: verifies agent output quality and constitutional compliance
     * before delivering to the user. Fail-open: verifier errors don't block.
     *
     * @var \Drupal\jaraba_ai_agents\Service\VerifierAgentService|null
     */
    protected ?VerifierAgentService $verifier = NULL;

    /**
     * Post-execution self-reflection service (GAP-L5-B).
     *
     * Optional: evaluates response quality post-delivery and proposes prompt
     * improvements when quality drops below threshold. Non-blocking.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AgentSelfReflectionService|null
     */
    protected ?AgentSelfReflectionService $selfReflection = NULL;

    /**
     * Cost alert service for real-time usage threshold enforcement.
     *
     * @var object|null
     */
    protected ?object $costAlertService = NULL;

    /**
     * Active A/B experiment variant (S3-06).
     *
     * Set by applyPromptExperiment(), consumed in callAiApi().
     * Contains: experiment_id, variant_id, system_prompt, temperature, model_tier.
     *
     * @var array|null
     */
    protected ?array $activeExperimentVariant = NULL;

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
     * Sets the agent long-term memory service (S3-01: HAL-AI-04).
     *
     * @param \Drupal\jaraba_agents\Service\AgentLongTermMemoryService|null $longTermMemory
     *   The memory service or NULL.
     */
    public function setLongTermMemory(?AgentLongTermMemoryService $longTermMemory): void
    {
        $this->longTermMemory = $longTermMemory;
    }

    /**
     * Sets the pre-delivery verifier agent (GAP-L5-A).
     *
     * @param \Drupal\jaraba_ai_agents\Service\VerifierAgentService|null $verifier
     *   The verifier service or NULL.
     */
    public function setVerifier(?VerifierAgentService $verifier): void
    {
        $this->verifier = $verifier;
    }

    /**
     * Sets the post-execution self-reflection service (GAP-L5-B).
     *
     * @param \Drupal\jaraba_ai_agents\Service\AgentSelfReflectionService|null $selfReflection
     *   The self-reflection service or NULL.
     */
    public function setSelfReflection(?AgentSelfReflectionService $selfReflection): void
    {
        $this->selfReflection = $selfReflection;
    }

    /**
     * Sets the cost alert service for usage threshold enforcement.
     *
     * @param object|null $costAlertService
     *   The cost alert service or NULL.
     */
    public function setCostAlertService(?object $costAlertService): void
    {
        $this->costAlertService = $costAlertService;
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
        // KERNEL-OPTIONAL-AI-001: Early return when AI provider unavailable.
        if (!$this->aiProvider) {
            return [
                'success' => FALSE,
                'data' => ['text' => ''],
                'error' => 'AI provider not available',
            ];
        }

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

            // S3-01: Inject long-term memory context into system prompt.
            $memoryPrompt = $this->buildMemoryContext($prompt);
            if (!empty($memoryPrompt)) {
                $systemPrompt .= "\n\n" . $memoryPrompt;
            }

            // LCIS Capa 4: Inyectar coherencia juridica si vertical=jarabalex o accion legal.
            if (\Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule::requiresCoherence(
              $options['action'] ?? '',
              $this->vertical ?? '',
            )) {
                $short = \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule::useShortVersion($options['action'] ?? '');
                $systemPrompt = \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule::apply($systemPrompt, $short);
            }

            // S3-06: Apply A/B experiment variant's system prompt override.
            if ($this->activeExperimentVariant && !empty($this->activeExperimentVariant['system_prompt'])) {
                $systemPrompt = $this->activeExperimentVariant['system_prompt'];
                // Override temperature/tier from variant if set.
                if (isset($this->activeExperimentVariant['temperature'])) {
                    $options['temperature'] = $this->activeExperimentVariant['temperature'];
                }
                if (!empty($this->activeExperimentVariant['model_tier'])) {
                    $options['force_tier'] = $this->activeExperimentVariant['model_tier'];
                }
            }

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

        // 4. Observabilidad (siempre se registra cuando el servicio está disponible).
        $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

        $logId = $this->observability?->log([
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

        // 5. Cost alert threshold enforcement (real-time).
        if ($this->costAlertService && $this->tenantId) {
            try {
                $totalTokens = $inputTokens + $outputTokens;
                $alertData = $this->costAlertService->checkThresholds($this->tenantId, $totalTokens);
                if (!empty($alertData['threshold_reached'])) {
                    $result['cost_alert'] = $alertData;
                }
            }
            catch (\Exception $e) {
                // Cost alert failure must not block agent execution.
            }
        }

        // FIX-032: Enqueue quality evaluation (sampling: 10% or 100% premium).
        if ($success && $logId) {
            $this->enqueueQualityEvaluation($logId, $prompt, $result['data']['text'] ?? '', $tier);
            $result['log_id'] = $logId;
        }

        // FIX-044: Mask PII in output.
        if ($success && isset($result['data']['text'])) {
            $result['data']['text'] = $this->maskOutputPii($result['data']['text']);
        }

        // S3-01: Remember interaction summary for long-term personalization.
        if ($success && isset($result['data']['text'])) {
            $this->rememberInteraction($prompt, $result['data']['text']);
        }

        // S3-06: Record A/B experiment result if variant was active.
        if ($success && $this->activeExperimentVariant && isset($result['data']['text'])) {
            $this->recordExperimentResult($prompt, $result['data']['text']);
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

            // GAP-03/GAP-10: Sanitizar tool output para prompt injection indirecto.
            // Los resultados de tools pueden provenir de fuentes externas (APIs, BD,
            // busquedas) que podrian contener instrucciones de injection embebidas.
            $toolResult = $this->sanitizeToolOutput($toolResult);

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
     * GAP-09: Calls the AI API with native function calling (API-level tool use).
     *
     * Usa ChatInput::setChatTools() para pasar herramientas como funciones
     * nativas al LLM en vez de inyectarlas como XML en el system prompt.
     * El LLM responde con tool_use blocks estructurados en vez de JSON en texto.
     *
     * Loop iterativo: LLM -> tool_use -> execute tool -> tool_result message
     * -> re-call LLM (max iterations).
     *
     * @param string $prompt
     *   The user prompt.
     * @param array $options
     *   Optional: max_tool_iterations (default 5), force_tier, temperature.
     *
     * @return array
     *   Result array with tool execution trace.
     */
    protected function callAiApiWithNativeTools(string $prompt, array $options = []): array
    {
        if (!$this->toolRegistry || empty($this->toolRegistry->getAll())) {
            return $this->callAiApi($prompt, $options);
        }

        $nativeTools = $this->toolRegistry->generateNativeToolsInput();
        if (!$nativeTools) {
            return $this->callAiApi($prompt, $options);
        }

        $startTime = microtime(TRUE);
        $success = FALSE;
        $inputTokens = 0;
        $outputTokens = 0;
        $modelId = '';
        $providerId = '';
        $tier = 'balanced';
        $logId = NULL;
        $toolTrace = [];
        $maxIterations = $options['max_tool_iterations'] ?? 5;

        try {
            // 1. Build system prompt (sin tool docs — las tools van nativas).
            $systemPrompt = $this->buildSystemPrompt($prompt);

            // FIX-033: Fit to context window.
            if ($this->contextWindowManager) {
                $systemPrompt = $this->contextWindowManager->fitToWindow(
                    $systemPrompt,
                    $prompt,
                    $modelId ?: 'default'
                );
            }

            // 2. Model routing.
            $routingConfig = $this->getRoutingConfig($prompt, $options);
            $tier = $routingConfig['tier'];
            $providerId = $routingConfig['provider_id'];
            $modelId = $routingConfig['model_id'];

            // 3. Build initial message list.
            $messages = [
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $prompt),
            ];

            $finalText = '';

            // 4. Iterative tool use loop.
            for ($i = 0; $i < $maxIterations; $i++) {
                $chatOutput = $this->executeLlmCallWithTools(
                    $messages,
                    $nativeTools,
                    $providerId,
                    $modelId,
                    $options
                );

                $chatMessage = $chatOutput->getNormalized();
                $text = $chatMessage->getText();
                $toolCalls = $chatMessage->getTools();

                if (empty($toolCalls)) {
                    // No tool calls — final response.
                    $finalText = $text;
                    break;
                }

                // Process each tool call.
                // Append assistant message with tool calls to conversation.
                $messages[] = $chatMessage;

                foreach ($toolCalls as $toolFnOutput) {
                    $toolId = $toolFnOutput->getName();
                    $toolArgs = [];
                    foreach ($toolFnOutput->getArguments() as $arg) {
                        $toolArgs[$arg->getName()] = $arg->getValue();
                    }

                    $toolContext = [
                        'agent_id' => $this->getAgentId(),
                        'tenant_id' => $this->tenantId,
                        'vertical' => $this->vertical,
                    ];

                    $toolResult = $this->toolRegistry->execute($toolId, $toolArgs, $toolContext);

                    // GAP-03/GAP-10: Sanitize tool output.
                    $toolResult = $this->sanitizeToolOutput($toolResult);

                    $traceEntry = [
                        'iteration' => $i + 1,
                        'tool_id' => $toolId,
                        'params' => $toolArgs,
                        'result' => $toolResult,
                        'native' => TRUE,
                    ];
                    $toolTrace[] = $traceEntry;

                    // Append tool result as a user message for the next iteration.
                    $toolResultJson = json_encode($toolResult, JSON_UNESCAPED_UNICODE);
                    $toolResultMessage = new ChatMessage('user',
                        "Tool result for {$toolId}: {$toolResultJson}"
                    );
                    if (method_exists($toolResultMessage, 'setToolsId')) {
                        $toolResultMessage->setToolsId($toolFnOutput->getToolId());
                    }
                    $messages[] = $toolResultMessage;

                    $this->logger->info('GAP-09: Native tool @tool executed in iteration @iter for @agent', [
                        '@tool' => $toolId,
                        '@iter' => $i + 1,
                        '@agent' => $this->getAgentId(),
                    ]);
                }
            }

            // Estimate tokens.
            $totalInput = $systemPrompt . $prompt;
            foreach ($toolTrace as $trace) {
                $totalInput .= json_encode($trace['result'] ?? [], JSON_UNESCAPED_UNICODE);
            }
            $inputTokens = (int) ceil(mb_strlen($totalInput) / 4);
            $outputTokens = (int) ceil(mb_strlen($finalText) / 4);
            $success = TRUE;

            $result = [
                'success' => TRUE,
                'data' => ['text' => $finalText],
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'agent_id' => $this->getAgentId(),
                'routing' => [
                    'tier' => $tier,
                    'model' => $modelId,
                    'estimated_cost' => $routingConfig['estimated_cost'],
                ],
                'tool_trace' => $toolTrace,
                'native_tools' => TRUE,
            ];

        } catch (\Exception $e) {
            $this->logger->error('GAP-09: Native tool use error: @msg', ['@msg' => $e->getMessage()]);

            // Fallback to text-based tool use.
            $this->logger->info('GAP-09: Falling back to text-based tool use.');
            return $this->callAiApiWithTools($prompt, $options);
        }

        // Observability.
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
            'operation_name' => 'callAiApiWithNativeTools',
        ]);

        if ($success && $logId) {
            $this->enqueueQualityEvaluation($logId, $prompt, $result['data']['text'] ?? '', $tier);
            $result['log_id'] = $logId;
        }

        // FIX-044: Mask PII.
        if ($success && isset($result['data']['text'])) {
            $result['data']['text'] = $this->maskOutputPii($result['data']['text']);
        }

        return $result;
    }

    /**
     * GAP-09: Executes LLM call with native tool definitions.
     *
     * @param array $messages
     *   Array of ChatMessage objects (conversation history).
     * @param \Drupal\ai\OperationType\Chat\Tools\ToolsInput $tools
     *   Native tool definitions.
     * @param string $providerId
     *   Provider ID.
     * @param string $modelId
     *   Model ID.
     * @param array $options
     *   Options (temperature, etc.).
     *
     * @return \Drupal\ai\OperationType\Chat\ChatOutput
     *   The chat output (may contain tool calls).
     */
    protected function executeLlmCallWithTools(
        array $messages,
        ToolsInput $tools,
        string $providerId,
        string $modelId,
        array $options,
    ): \Drupal\ai\OperationType\Chat\ChatOutput {
        $provider = $this->aiProvider->createInstance($providerId);

        $chatInput = new ChatInput($messages);
        $chatInput->setChatTools($tools);

        $configuration = [
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        return $provider->chat($chatInput, $modelId, $configuration);
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
     * Sanitizes tool output for indirect prompt injection (GAP-03/GAP-10).
     *
     * @param mixed $toolResult
     *   The tool execution result (array or string).
     *
     * @return mixed
     *   Sanitized result.
     */
    protected function sanitizeToolOutput(mixed $toolResult): mixed
    {
        if (!\Drupal::hasService('ecosistema_jaraba_core.ai_guardrails')) {
            return $toolResult;
        }

        try {
            $guardrails = \Drupal::service('ecosistema_jaraba_core.ai_guardrails');

            if (is_string($toolResult)) {
                return $guardrails->sanitizeToolOutput($toolResult);
            }

            if (is_array($toolResult)) {
                // Sanitizar valores string dentro del array de resultado.
                array_walk_recursive($toolResult, function (&$value) use ($guardrails) {
                    if (is_string($value) && mb_strlen($value) > 20) {
                        $value = $guardrails->sanitizeToolOutput($value);
                    }
                });
            }
        } catch (\Exception $e) {
            // Non-critical — don't fail the tool response.
        }

        return $toolResult;
    }

    /**
     * Builds memory context from long-term memory (S3-01: HAL-AI-04).
     *
     * Calls AgentLongTermMemoryService::buildMemoryPrompt() to retrieve
     * relevant memories and format them as a system prompt section.
     *
     * @param string $currentPrompt
     *   The current user prompt for semantic recall.
     *
     * @return string
     *   Memory prompt section or empty string.
     */
    protected function buildMemoryContext(string $currentPrompt): string
    {
        if (!$this->longTermMemory) {
            return '';
        }

        try {
            return $this->longTermMemory->buildMemoryPrompt(
                $this->getAgentId(),
                (string) ($this->tenantId ?? '0'),
                $currentPrompt
            );
        } catch (\Exception $e) {
            $this->logger->notice('Long-term memory recall failed: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Remembers a successful interaction for long-term context (S3-01).
     *
     * Stores an interaction summary in agent memory for future recall.
     * Uses fire-and-forget pattern — failures are silently logged.
     *
     * @param string $prompt
     *   The user prompt.
     * @param string $response
     *   The agent response text.
     */
    protected function rememberInteraction(string $prompt, string $response): void
    {
        if (!$this->longTermMemory) {
            return;
        }

        try {
            // Build a concise interaction summary (not the full response).
            $summary = mb_substr($prompt, 0, 200);
            if (mb_strlen($prompt) > 200) {
                $summary .= '...';
            }

            $this->longTermMemory->remember(
                $this->getAgentId(),
                (string) ($this->tenantId ?? '0'),
                'interaction_summary',
                $summary,
                [
                    'action' => $this->currentAction ?? 'general',
                    'vertical' => $this->vertical,
                ]
            );
        } catch (\Exception $e) {
            // Non-critical: memory storage failure should never break agent.
            $this->logger->notice('Long-term memory store failed: @msg', [
                '@msg' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Records the result of an A/B experiment execution (S3-06).
     *
     * Delegates to PromptExperimentService::recordResult() for quality
     * evaluation and conversion tracking. Fire-and-forget pattern.
     *
     * @param string $prompt
     *   The user prompt.
     * @param string $response
     *   The AI response text.
     */
    protected function recordExperimentResult(string $prompt, string $response): void
    {
        if (!$this->activeExperimentVariant) {
            return;
        }

        if (!\Drupal::hasService('jaraba_ai_agents.prompt_experiment')) {
            return;
        }

        try {
            $experimentService = \Drupal::service('jaraba_ai_agents.prompt_experiment');
            $experimentName = $this->getAgentId() . '_' . ($this->currentAction ?? 'general');

            $experimentService->recordResult($experimentName, $prompt, $response, [
                'agent_id' => $this->getAgentId(),
                'variant_id' => $this->activeExperimentVariant['variant_id'] ?? NULL,
                'experiment_id' => $this->activeExperimentVariant['experiment_id'] ?? NULL,
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
            ]);
        }
        catch (\Exception $e) {
            // Non-critical — experiment recording failure should never break agent.
            $this->logger->notice('A/B experiment result recording failed: @msg', [
                '@msg' => $e->getMessage(),
            ]);
        }
        finally {
            // Reset variant after recording to prevent double-recording.
            $this->activeExperimentVariant = NULL;
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
     * Pipeline: A/B experiment → doExecute() → Verifier (pre-delivery)
     *           → Self-reflection (post-delivery, non-blocking).
     *
     * GAP-L5-A: VerifierAgentService evaluates output before delivery.
     *           Constitutional layer always runs; LLM layer is configurable.
     *           Fail-open: verifier errors pass through.
     * GAP-L5-B: AgentSelfReflectionService evaluates quality post-delivery
     *           and proposes prompt improvements when score < threshold.
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

        $result = $this->doExecute($action, $context);

        // GAP-L5-A: Pre-delivery verification (constitutional + LLM quality).
        $result = $this->applyVerification($action, $result, $context);

        // GAP-L5-B: Post-delivery self-reflection (non-blocking).
        $this->applySelfReflection($action, $result, $context);

        return $result;
    }

    /**
     * Applies pre-delivery verification to the agent result (GAP-L5-A).
     *
     * Two layers: constitutional enforcement (always, local) + LLM quality
     * check (configurable mode: all/sample/critical_only).
     *
     * Fail-open: if verifier itself fails, the original result passes through.
     *
     * @param string $action
     *   The action executed.
     * @param array $result
     *   The agent execution result.
     * @param array $context
     *   Execution context.
     *
     * @return array
     *   The result, potentially modified by the verifier.
     */
    protected function applyVerification(string $action, array $result, array $context): array
    {
        if (!$this->verifier) {
            return $result;
        }

        if (!($result['success'] ?? FALSE) || empty($result['data']['text'] ?? '')) {
            return $result;
        }

        try {
            $userInput = $context['user_input'] ?? $context['prompt'] ?? '';
            $verification = $this->verifier->verify(
                $this->getAgentId(),
                $action,
                $userInput,
                $result['data']['text'],
                [
                    'tenant_id' => $this->tenantId ?? '',
                    'vertical' => $this->vertical ?? '',
                ],
            );

            $result['verification'] = [
                'verified' => $verification['verified'],
                'passed' => $verification['passed'],
                'score' => $verification['score'],
                'verification_id' => $verification['verification_id'],
            ];

            // If verifier blocked the response, replace output with sanitized version.
            if ($verification['verified'] && !$verification['passed']) {
                $result['data']['text'] = $verification['output']
                    ?? $this->getBlockedResponseFallback();
                $result['blocked_by_verifier'] = TRUE;
                $result['blocked_reason'] = $verification['blocked_reason'] ?? 'quality_check_failed';
            }
        }
        catch (\Throwable $e) {
            // Fail-open: verifier failure must not block response delivery.
            $this->logger->warning('GAP-L5-A: Verification failed for @agent/@action: @error', [
                '@agent' => $this->getAgentId(),
                '@action' => $action,
                '@error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Applies post-delivery self-reflection (GAP-L5-B).
     *
     * Non-blocking: evaluates agent quality and proposes prompt improvements.
     * Failures in self-reflection MUST NOT affect response delivery.
     *
     * @param string $action
     *   The action executed.
     * @param array $result
     *   The agent execution result.
     * @param array $context
     *   Execution context.
     */
    protected function applySelfReflection(string $action, array $result, array $context): void
    {
        if (!$this->selfReflection) {
            return;
        }

        if (!($result['success'] ?? FALSE) || empty($result['data']['text'] ?? '')) {
            return;
        }

        // Skip reflection for blocked responses — they need re-execution, not improvement.
        if (!empty($result['blocked_by_verifier'])) {
            return;
        }

        try {
            $userInput = $context['user_input'] ?? $context['prompt'] ?? '';
            $this->selfReflection->reflect(
                $this->getAgentId(),
                $action,
                $userInput,
                $result['data']['text'],
                [
                    'tenant_id' => $this->tenantId ?? '',
                    'vertical' => $this->vertical ?? '',
                ],
            );
        }
        catch (\Throwable $e) {
            // Non-blocking: self-reflection failure is silently logged.
            $this->logger->notice('GAP-L5-B: Self-reflection failed for @agent/@action: @error', [
                '@agent' => $this->getAgentId(),
                '@action' => $action,
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Returns a safe fallback text when the verifier blocks a response.
     *
     * @return string
     *   Generic safe response.
     */
    protected function getBlockedResponseFallback(): string
    {
        return 'Lo siento, no puedo proporcionar esa respuesta en este momento. Por favor, reformula tu consulta o contacta con soporte.';
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
