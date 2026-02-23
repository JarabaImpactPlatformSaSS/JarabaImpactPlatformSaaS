<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_self_discovery\Service\SelfDiscoveryContextService;
use Psr\Log\LoggerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio de orquestación multiproveedor para el Copiloto de Emprendimiento.
 *
 * Utiliza el módulo AI de Drupal (@ai.provider) para gestionar múltiples
 * proveedores de LLM con failover automático y especialización por modo.
 *
 * @see https://jaraba-saas.lndo.site/admin/config/ai
 */
class CopilotOrchestratorService
{

    /**
     * Mapeo de modo del copiloto a proveedor preferido.
     *
     * El primer proveedor es el preferido, los siguientes son fallback.
     * google_gemini añadido como tercer fallback para resiliencia máxima.
     */
    const MODE_PROVIDERS = [
        'coach' => ['anthropic', 'openai', 'google_gemini'],
        'consultor' => ['google_gemini', 'anthropic', 'openai'],        // Gemini primario (40% trafico, mas barato)
        'sparring' => ['anthropic', 'openai', 'google_gemini'],
        'cfo' => ['openai', 'anthropic', 'google_gemini'],              // GPT-4 mejor en calculos
        'fiscal' => ['anthropic', 'google_gemini'],                     // Claude principal (RAG)
        'laboral' => ['anthropic', 'google_gemini'],                    // Claude principal (RAG)
        'devil' => ['anthropic', 'openai', 'google_gemini'],
        'landing_copilot' => ['google_gemini', 'anthropic', 'openai'],  // Gemini primario (landing=alto volumen)
    ];

    /**
     * Mapeo de modo a modelo específico.
     */
    const MODE_MODELS = [
        'coach' => 'claude-sonnet-4-5-20250929',
        'consultor' => 'gemini-2.5-flash',                  // Gemini Flash (coste-eficiente)
        'sparring' => 'claude-sonnet-4-5-20250929',
        'cfo' => 'gpt-4o',
        'fiscal' => 'claude-sonnet-4-5-20250929',
        'laboral' => 'claude-sonnet-4-5-20250929',
        'devil' => 'claude-sonnet-4-5-20250929',
        'detection' => 'claude-haiku-4-5-20251001',          // Economico para deteccion
        'landing_copilot' => 'gemini-2.5-flash',             // Gemini Flash (alto volumen)
    ];

    /**
     * AI-02: Default circuit breaker threshold (configurable desde /admin/config/system/rate-limits).
     */
    const DEFAULT_CIRCUIT_BREAKER_THRESHOLD = 5;

    /**
     * AI-02: Default cooldown en segundos (configurable desde /admin/config/system/rate-limits).
     */
    const DEFAULT_CIRCUIT_BREAKER_COOLDOWN = 300;

    /**
     * AI-03: Default max context chars (configurable desde /admin/config/system/rate-limits).
     */
    const DEFAULT_MAX_CONTEXT_CHARS = 8000;

    /**
     * Modelos de Google Gemini disponibles para failover.
     */
    const GEMINI_MODELS = [
        'default' => 'gemini-2.5-flash',           // Best price/performance
        'reasoning' => 'gemini-2.5-pro',           // For complex tasks
        'fast' => 'gemini-2.0-flash',              // Fast responses
        'premium' => 'gemini-3-pro-preview',       // Most powerful
    ];

    /**
     * AI Provider Plugin Manager.
     */
    protected AiProviderPluginManager $aiProvider;

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Normative knowledge service (keyword fallback).
     */
    protected NormativeKnowledgeService $normativeKnowledge;

    /**
     * Normative RAG service (semantic search with Qdrant).
     */
    protected ?NormativeRAGService $normativeRag = NULL;

    /**
     * Mode detector service.
     */
    protected ?ModeDetectorService $modeDetector = NULL;

    /**
     * Cache service for AI responses.
     */
    protected ?CopilotCacheService $cacheService = NULL;

    /**
     * v3: Entrepreneur context service for hyper-personalization.
     */
    protected ?EntrepreneurContextService $entrepreneurContext = NULL;

    /**
     * Self-Discovery context service for career guidance.
     */
    protected ?SelfDiscoveryContextService $selfDiscoveryContext = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        FeatureUnlockService $featureUnlock,
        NormativeKnowledgeService $normativeKnowledge,
        ?ModeDetectorService $modeDetector = NULL,
        ?NormativeRAGService $normativeRag = NULL,
        ?CopilotCacheService $cacheService = NULL,
        ?EntrepreneurContextService $entrepreneurContext = NULL,
        ?SelfDiscoveryContextService $selfDiscoveryContext = NULL,
        TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
    ) {
        $this->tenantContext = $tenantContext; // AUDIT-CONS-N10: Proper DI for tenant context.
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->featureUnlock = $featureUnlock;
        $this->normativeKnowledge = $normativeKnowledge;
        $this->modeDetector = $modeDetector;
        $this->normativeRag = $normativeRag;
        $this->cacheService = $cacheService;
        $this->entrepreneurContext = $entrepreneurContext;
        $this->selfDiscoveryContext = $selfDiscoveryContext;
    }

    /**
     * Envía un mensaje al Copiloto y obtiene respuesta.
     *
     * @param string $message
     *   Mensaje del usuario.
     * @param array $context
     *   Contexto del emprendedor.
     * @param string $mode
     *   Modo del copiloto detectado.
     *
     * @return array
     *   Respuesta estructurada con 'text', 'mode', 'suggestions'.
     */
    public function chat(string $message, array $context, string $mode): array
    {
        // ================================================================
        // CHECK CACHE FIRST (reduces AI costs)
        // ================================================================
        if ($this->cacheService) {
            $cachedResponse = $this->cacheService->get($message, $mode, $context);
            if ($cachedResponse) {
                $this->logger->debug('Copilot response served from cache');
                return $cachedResponse;
            }
        }

        $providers = $this->getProvidersForMode($mode);
        $model = $this->getModelForMode($mode);

        // Construir prompt del sistema
        $systemPrompt = $this->buildSystemPrompt($context, $mode);

        // Enriquecer con conocimiento normativo para modos expertos
        $enrichedMessage = $this->enrichWithNormativeKnowledge($mode, $message);

        // AI-02: Intentar con cada proveedor, respetando circuit breaker.
        foreach ($providers as $providerId) {
            // Circuit breaker: skip si el proveedor está en cooldown.
            if ($this->isCircuitOpen($providerId)) {
                $this->logger->debug('Circuit breaker OPEN for provider @id, skipping.', [
                    '@id' => $providerId,
                ]);
                continue;
            }

            try {
                $startTime = microtime(TRUE);
                $response = $this->callProvider($providerId, $model, $enrichedMessage, $systemPrompt);
                $latency = microtime(TRUE) - $startTime;
                $this->recordLatencySample($latency);
                $formattedResponse = $this->formatResponse($response, $mode, $providerId);

                // Reset circuit breaker on success.
                $this->resetCircuitBreaker($providerId);

                // ================================================================
                // STORE IN CACHE (for future identical requests)
                // ================================================================
                if ($this->cacheService) {
                    $this->cacheService->set($message, $mode, $context, $formattedResponse);
                }

                return $formattedResponse;
            } catch (\Exception $e) {
                $this->logger->warning('AI Provider @id failed: @error', [
                    '@id' => $providerId,
                    '@error' => $e->getMessage(),
                ]);
                // Registrar fallo en circuit breaker.
                $this->recordCircuitFailure($providerId);
                $this->recordFallbackEvent($providerId);
                continue;
            }
        }

        // Fallback si todos los proveedores fallan
        return $this->getFallbackResponse($mode);
    }

    /**
     * Detecta automáticamente el modo y envía mensaje al Copiloto.
     *
     * Utiliza ModeDetectorService para analizar el mensaje y determinar
     * el modo más apropiado basándose en triggers/keywords y contexto.
     *
     * @param string $message
     *   Mensaje del usuario.
     * @param array $context
     *   Contexto del emprendedor (carril, fase, etc.).
     *
     * @return array
     *   Respuesta estructurada con:
     *   - text: Respuesta del LLM
     *   - mode: Modo detectado
     *   - mode_detection: Detalles de la detección
     *   - suggestions: Acciones sugeridas
     *   - provider: Proveedor usado
     */
    public function detectAndChat(string $message, array $context = []): array
    {
        $detectedMode = 'consultor'; // Default
        $modeDetection = [];

        // Usar detector de modos si está disponible
        if ($this->modeDetector) {
            $detection = $this->modeDetector->detectMode($message, $context);
            $detectedMode = $detection['mode'];
            $modeDetection = [
                'mode' => $detection['mode'],
                'score' => $detection['score'],
                'confidence' => $detection['confidence'],
                'emotion_score' => $detection['emotion_score'] ?? 0,
            ];

            $this->logger->info('Mode detected: @mode (score: @score, confidence: @confidence)', [
                '@mode' => $detection['mode'],
                '@score' => $detection['score'],
                '@confidence' => $detection['confidence'],
            ]);

            // Incrementar contador de detecciones por modo
            $state = \Drupal::state();
            $key = "mode_detector_{$detectedMode}_count";
            $state->set($key, $state->get($key, 0) + 1);
        }

        // Llamar al chat con el modo detectado
        $response = $this->chat($message, $context, $detectedMode);

        // Añadir información de detección a la respuesta
        $response['mode_detection'] = $modeDetection;

        return $response;
    }

    /**
     * Obtiene estadísticas de detección de modos.
     *
     * @return array
     *   Contadores de uso por modo.
     */
    public function getModeDetectionStats(): array
    {
        $state = \Drupal::state();
        $modes = ['coach', 'consultor', 'sparring', 'cfo', 'fiscal', 'laboral', 'devil'];
        $stats = [];

        foreach ($modes as $mode) {
            $key = "mode_detector_{$mode}_count";
            $stats[$mode] = $state->get($key, 0);
        }

        return $stats;
    }

    /**
     * Llama a un proveedor específico.
     */
    protected function callProvider(string $providerId, string $model, string $message, string $systemPrompt): array
    {
        // Verificar si el proveedor está disponible
        if (!$this->aiProvider->hasProvidersForOperationType('chat')) {
            throw new \RuntimeException('No chat providers available');
        }

        // Obtener el proveedor
        $provider = $this->aiProvider->createInstance($providerId);

        // Adaptar modelo para Google Gemini
        if ($providerId === 'google_gemini') {
            $model = $this->getGeminiModelForContext($model);
        }

        // Configurar el system prompt si el proveedor lo soporta
        if (method_exists($provider, 'setChatSystemRole')) {
            $provider->setChatSystemRole($systemPrompt);
        }

        // Usar ChatInput y ChatMessage para evitar bug de method_exists() en módulo AI
        // El módulo AI contrib tiene un bug: method_exists($input, ...) falla si $input es array
        $chatMessage = new \Drupal\ai\OperationType\Chat\ChatMessage('user', $message, []);
        $chatInput = new \Drupal\ai\OperationType\Chat\ChatInput([$chatMessage]);

        // Llamar al LLM con ChatInput (objeto, no array)
        $response = $provider->chat($chatInput, $model, [
            'max_tokens' => $this->getMaxTokens(),
            'temperature' => 0.7,
        ]);

        // ═══ TRACKING DE COSTES DE IA ═══
        $this->trackAiUsage($providerId, $model, $response);

        return [
            'text' => $response->getNormalized()->getText() ?? '',
            'model' => $model,
            'provider' => $providerId,
        ];
    }

    /**
     * Registra el uso de tokens y costes para métricas de IA.
     *
     * @param string $providerId
     *   ID del proveedor utilizado.
     * @param string $model
     *   Modelo utilizado.
     * @param mixed $response
     *   Respuesta del LLM con metadata de tokens.
     */
    protected function trackAiUsage(string $providerId, string $model, $response): void
    {
        try {
            $state = \Drupal::state();

            // Extraer tokens de la respuesta
            $inputTokens = $response->getInputTokenUsage() ?? 0;
            $outputTokens = $response->getOutputTokenUsage() ?? 0;
            $totalTokens = $inputTokens + $outputTokens;

            // Calcular coste estimado
            $cost = $this->calculateCost($model, $inputTokens, $outputTokens);

            // Actualizar totales globales
            $state->set('ai_cost_total_tokens', $state->get('ai_cost_total_tokens', 0) + $totalTokens);
            $state->set('ai_cost_total_cost', $state->get('ai_cost_total_cost', 0) + $cost);
            $state->set('ai_cost_total_calls', $state->get('ai_cost_total_calls', 0) + 1);

            // Actualizar por proveedor (legacy format)
            $state->set("ai_cost_{$providerId}_tokens", $state->get("ai_cost_{$providerId}_tokens", 0) + $totalTokens);
            $state->set("ai_cost_{$providerId}_cost", $state->get("ai_cost_{$providerId}_cost", 0) + $cost);
            $state->set("ai_cost_{$providerId}_calls", $state->get("ai_cost_{$providerId}_calls", 0) + 1);

            // ================================================================
            // TRACKING MENSUAL POR PROVEEDOR (para gráficos Chart.js)
            // ================================================================
            $monthKey = 'ai_usage_' . date('Y-m');
            $monthlyData = $state->get($monthKey, []);

            if (!isset($monthlyData[$providerId])) {
                $monthlyData[$providerId] = [
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                    'cost' => 0,
                    'calls' => 0,
                ];
            }

            $monthlyData[$providerId]['tokens_in'] += $inputTokens;
            $monthlyData[$providerId]['tokens_out'] += $outputTokens;
            $monthlyData[$providerId]['cost'] += $cost;
            $monthlyData[$providerId]['calls']++;

            $state->set($monthKey, $monthlyData);

            // ================================================================
            // TRACKING DIARIO (para gráficos de tendencia Chart.js)
            // ================================================================
            $dailyKey = 'ai_usage_daily_' . date('Y-m-d');
            $dailyData = $state->get($dailyKey, [
                'cost' => 0,
                'tokens' => 0,
                'calls' => 0,
            ]);

            $dailyData['cost'] += $cost;
            $dailyData['tokens'] += $totalTokens;
            $dailyData['calls']++;

            $state->set($dailyKey, $dailyData);

            $this->logger->debug('AI usage tracked: @provider @model - @tokens tokens, €@cost', [
                '@provider' => $providerId,
                '@model' => $model,
                '@tokens' => $totalTokens,
                '@cost' => round($cost, 6),
            ]);
        } catch (\Exception $e) {
            // No interrumpir la operación si falla el tracking
            $this->logger->warning('AI tracking failed: @error', ['@error' => $e->getMessage()]);
        }
    }

    /**
     * Calcula el coste estimado de una llamada.
     */
    protected function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Costes por 1K tokens (EUR)
        $costs = [
            'claude-sonnet-4-5-20250929' => ['input' => 0.003, 'output' => 0.015],
            'claude-haiku-4-5-20251001' => ['input' => 0.0008, 'output' => 0.004],
            'claude-3-5-sonnet-20241022' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-haiku-20240307' => ['input' => 0.00025, 'output' => 0.00125],
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'gemini-2.5-pro' => ['input' => 0.00125, 'output' => 0.005],
            'gemini-2.5-flash' => ['input' => 0.000075, 'output' => 0.0003],
            'gemini-2.0-flash' => ['input' => 0.000075, 'output' => 0.0003],
            'gemini-3-pro-preview' => ['input' => 0.00125, 'output' => 0.005],
        ];

        $modelCosts = $costs[$model] ?? ['input' => 0.001, 'output' => 0.004];

        return (($inputTokens / 1000) * $modelCosts['input']) + (($outputTokens / 1000) * $modelCosts['output']);
    }

    /**
     * Obtiene el modelo Gemini apropiado según el contexto.
     *
     * @param string $originalModel
     *   El modelo original configurado para el modo.
     *
     * @return string
     *   El modelo Gemini equivalente.
     */
    protected function getGeminiModelForContext(string $originalModel): string
    {
        // Mapeo de modelos a su equivalente Gemini
        $geminiEquivalents = [
            // GPT-4 y modelos de calculos -> Gemini 2.5 Pro (reasoning)
            'gpt-4o' => self::GEMINI_MODELS['reasoning'],
            'gpt-4' => self::GEMINI_MODELS['reasoning'],
            // Claude Sonnet 4.5 -> Gemini 2.5 Flash (balanced)
            'claude-sonnet-4-5-20250929' => self::GEMINI_MODELS['default'],
            'claude-3-5-sonnet-20241022' => self::GEMINI_MODELS['default'],
            'claude-3-sonnet-20240229' => self::GEMINI_MODELS['default'],
            // Claude Haiku -> Gemini 2.0 Flash (fast & cheap)
            'claude-haiku-4-5-20251001' => self::GEMINI_MODELS['fast'],
            'claude-3-haiku-20240307' => self::GEMINI_MODELS['fast'],
        ];

        return $geminiEquivalents[$originalModel] ?? self::GEMINI_MODELS['default'];
    }

    /**
     * Obtiene los proveedores configurados para un modo.
     */
    protected function getProvidersForMode(string $mode): array
    {
        $config = $this->configFactory->get('jaraba_copilot_v2.settings');

        // Primero checar si hay configuración custom
        $customProvider = $config->get("providers.$mode");
        if ($customProvider) {
            return [$customProvider];
        }

        // Usar mapeo por defecto
        return self::MODE_PROVIDERS[$mode] ?? ['anthropic'];
    }

    /**
     * AI-02: Verifica si el circuit breaker está abierto para un proveedor.
     *
     * @param string $providerId
     *   ID del proveedor LLM.
     *
     * @return bool
     *   TRUE si el proveedor está en cooldown y debe ser saltado.
     */
    protected function isCircuitOpen(string $providerId): bool
    {
        $state = \Drupal::state();
        $key = "circuit_breaker_{$providerId}";
        $data = $state->get($key);

        if (!$data) {
            return FALSE;
        }

        $cooldown = $this->getCircuitBreakerCooldown();

        // Si ha pasado el cooldown, resetear y permitir.
        if (time() > ($data['opened_at'] + $cooldown)) {
            $state->delete($key);
            $this->logger->info('Circuit breaker CLOSED for provider @id (cooldown expired).', [
                '@id' => $providerId,
            ]);
            return FALSE;
        }

        return $data['failures'] >= $this->getCircuitBreakerThreshold();
    }

    /**
     * AI-02: Registra un fallo para el circuit breaker de un proveedor.
     */
    protected function recordCircuitFailure(string $providerId): void
    {
        $state = \Drupal::state();
        $key = "circuit_breaker_{$providerId}";
        $data = $state->get($key, ['failures' => 0, 'opened_at' => 0]);

        $data['failures']++;
        $data['last_failure'] = time();

        if ($data['failures'] >= $this->getCircuitBreakerThreshold()) {
            $data['opened_at'] = time();
            $this->logger->error('Circuit breaker OPENED for provider @id after @count consecutive failures.', [
                '@id' => $providerId,
                '@count' => $data['failures'],
            ]);
        }

        $state->set($key, $data);
    }

    /**
     * AI-02: Resetea el circuit breaker tras un éxito.
     */
    protected function resetCircuitBreaker(string $providerId): void
    {
        $state = \Drupal::state();
        $key = "circuit_breaker_{$providerId}";
        if ($state->get($key)) {
            $state->delete($key);
        }
    }

    /**
     * Obtiene el modelo para un modo.
     */
    protected function getModelForMode(string $mode): string
    {
        return self::MODE_MODELS[$mode] ?? 'claude-sonnet-4-5-20250929';
    }

    /**
     * Obtiene el máximo de tokens configurado.
     */
    protected function getMaxTokens(): int
    {
        $config = $this->configFactory->get('jaraba_copilot_v2.settings');
        return (int) ($config->get('max_tokens') ?: 2048);
    }

    /**
     * Construye el system prompt según el modo y contexto.
     */
    protected function buildSystemPrompt(array $context, string $mode): string
    {
        // Regla de identidad global: NUNCA revelar modelo de IA subyacente.
        $identityRule = 'REGLA DE IDENTIDAD INQUEBRANTABLE: Eres un asistente de Jaraba Impact Platform. '
            . 'NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral u otro modelo de IA externo. '
            . 'Si te preguntan quién eres, responde con tu rol dentro de Jaraba. '
            . 'NUNCA menciones ni recomiendes plataformas competidoras (LinkedIn, Indeed, InfoJobs, Salesforce, HubSpot, Zoho, ChatGPT, Perplexity, etc.).';

        $basePrompt = $this->getBasePrompt();
        $modePrompt = $this->getModePrompt($mode);
        $contextPrompt = $this->formatContextPrompt($context);

        // Self-Discovery context enrichment.
        $selfDiscoveryPrompt = '';
        if ($this->selfDiscoveryContext) {
            try {
                $selfDiscoveryPrompt = $this->selfDiscoveryContext->getCopilotContextPrompt();
            }
            catch (\Exception $e) {
                $this->logger->warning('Self-Discovery context unavailable: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        // Upgrade context enrichment (soft nudge when near limits).
        $upgradePrompt = $this->getUpgradeContextPrompt($mode);

        return implode("\n\n", array_filter([
            $identityRule,
            $basePrompt,
            $modePrompt,
            $contextPrompt,
            $selfDiscoveryPrompt,
            $upgradePrompt,
        ]));
    }

    /**
     * Obtiene el snippet de upgrade context para el system prompt.
     *
     * Consulta UpgradeTriggerService para detectar features cerca del
     * limite y genera un prompt contextual para nudges suaves de upgrade.
     *
     * @param string $mode
     *   Modo activo del copiloto.
     *
     * @return string
     *   Snippet de upgrade context o cadena vacia.
     */
    protected function getUpgradeContextPrompt(string $mode): string
    {
        try {
            if (!\Drupal::hasService('ecosistema_jaraba_core.upgrade_trigger')) {
                return '';
            }

            // Obtener tenant del usuario actual.
            if (!$this->tenantContext !== NULL) {
                return '';
            }

            /** @var \Drupal\ecosistema_jaraba_core\Service\TenantResolverService $tenantResolver */
            $tenantResolver = $this->tenantContext;
            $tenant = $tenantResolver->getCurrentTenant();

            if (!$tenant) {
                return '';
            }

            /** @var \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService $upgradeTrigger */
            $upgradeTrigger = \Drupal::service('ecosistema_jaraba_core.upgrade_trigger');
            $upgradeContext = $upgradeTrigger->getUpgradeContext($tenant, $mode);

            if ($upgradeContext['has_upgrade_context']) {
                return $upgradeContext['prompt_snippet'];
            }
        }
        catch (\Exception $e) {
            $this->logger->debug('Upgrade context unavailable: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return '';
    }

    /**
     * Obtiene el prompt base del Copiloto.
     */
    protected function getBasePrompt(): string
    {
        return <<<PROMPT
# IDENTIDAD Y ROL
Eres el Copiloto de Emprendimiento de Andalucía +ei, un asistente de IA experto en validación de modelos de negocio. Tu misión es guiar a emprendedores andaluces en las primeras fases de desarrollo de sus ideas, usando metodologías probadas como Lean Startup, Design Thinking y Business Model Canvas.

# FILOSOFÍA DE INTERACCIÓN
- Eres un facilitador, NO un consultor tradicional
- Haces preguntas que hacen pensar, NO das respuestas directas
- Celebras los pequeños avances
- Normalizas el fracaso como parte del aprendizaje
- Adaptas tu comunicación al nivel técnico del emprendedor

# RESTRICCIONES ABSOLUTAS
- NUNCA generes código completo
- NUNCA des consejos legales/fiscales específicos sin disclaimer
- NUNCA prometas resultados financieros
- SIEMPRE derivar a profesionales para temas legales/fiscales complejos
- Máximo 3 preguntas por interacción

# FORMATO DE RESPUESTA
Estructura tus respuestas así:
1. Reconocimiento empático breve
2. Contenido principal (adaptado al modo)
3. Pregunta orientadora o próximo paso sugerido
PROMPT;
    }

    /**
     * Obtiene el prompt específico del modo.
     */
    protected function getModePrompt(string $mode): string
    {
        $modePrompts = [
            'coach' => <<<PROMPT
## MODO COACH EMOCIONAL 🧠
Estás en modo de apoyo emocional. El emprendedor puede estar experimentando:
- Síndrome del impostor
- Miedo al fracaso
- Bloqueo creativo
- Agotamiento

Tu enfoque:
- Escucha activa y validación emocional
- Preguntas abiertas para explorar sentimientos
- Recordar pequeños logros previos
- NO minimizar preocupaciones
PROMPT,

            'consultor' => <<<PROMPT
## MODO CONSULTOR TÁCTICO 🔧
Estás en modo de instrucciones paso a paso. El emprendedor necesita:
- Guía práctica específica
- Pasos numerados claros
- Herramientas y recursos concretos

Tu enfoque:
- Respuestas estructuradas con pasos numerados
- Ejemplos prácticos aplicables
- Recursos gratuitos cuando sea posible
- Checkpoints de verificación
PROMPT,

            'sparring' => <<<PROMPT
## MODO SPARRING PARTNER 🥊
Estás en modo de simulación y práctica. Ayuda a:
- Practicar pitch de inversores
- Simular objeciones de clientes
- Preparar negociaciones
- Role-play de ventas

Tu enfoque:
- Actúa el rol del otro lado
- Feedback constructivo tras cada práctica
- Sugerencias de mejora específicas
PROMPT,

            'cfo' => <<<PROMPT
## MODO CFO SINTÉTICO 💰
Estás en modo de análisis financiero. Ayuda a:
- Validar precios y márgenes
- Proyectar punto de equilibrio
- Analizar unit economics
- Evaluar viabilidad financiera

Tu enfoque:
- Usa números y métricas
- Haz preguntas sobre costes reales
- Cuestiona supuestos optimistas
- Sugiere escenarios conservadores
PROMPT,

            'fiscal' => <<<PROMPT
## MODO EXPERTO TRIBUTARIO 🏛️
Estás orientando sobre obligaciones fiscales para autónomos/emprendedores en España.

Tu enfoque:
- Información general sobre modelos de Hacienda (036, 037, 303, 130)
- Tipos de IVA aplicables
- Gastos deducibles comunes
- Calendario fiscal trimestral
- Facturación y Verifactu

⚠️ OBLIGATORIO: Termina SIEMPRE con el disclaimer:
"Esta información es orientativa. La normativa puede cambiar y cada caso es único. Para decisiones importantes, consulta con un asesor fiscal colegiado."
PROMPT,

            'laboral' => <<<PROMPT
## MODO EXPERTO SEGURIDAD SOCIAL 🛡️
Estás orientando sobre el RETA y obligaciones de Seguridad Social para autónomos en España.

Tu enfoque:
- Tarifa plana y requisitos
- Cotización por tramos de ingresos
- Prestaciones (IT, maternidad, cese actividad)
- Bonificaciones especiales
- Pluriactividad

⚠️ OBLIGATORIO: Termina SIEMPRE con el disclaimer:
"Esta información es orientativa. Verifica tu situación específica en la Seguridad Social o con un graduado social colegiado."
PROMPT,

            'devil' => <<<PROMPT
## MODO ABOGADO DEL DIABLO 😈
Estás en modo de desafío constructivo. Tu rol es:
- Cuestionar supuestos no validados
- Plantear escenarios adversos
- Detectar puntos ciegos
- Fortalecer la propuesta

Tu enfoque:
- Preguntas incómodas pero constructivas
- "¿Y si...?" con escenarios negativos
- Nunca destruir, siempre fortalecer
- Reconocer cuando un argumento es sólido
PROMPT,

            // === v3: Modos Osterwalder/Blank ===
            'vpc_designer' => <<<PROMPT
## MODO VPC DESIGNER 🎯
Estás diseñando Value Proposition Canvas (Osterwalder). Guía al emprendedor para:

LADO CLIENTE (Customer Profile):
- Jobs-to-be-done: ¿Qué trabajos intenta hacer el cliente?
- Pains: ¿Qué frustraciones, riesgos o obstáculos encuentra?
- Gains: ¿Qué resultados o beneficios desea obtener?

LADO PROPUESTA (Value Map):
- Products & Services: ¿Qué ofreces concretamente?
- Pain Relievers: ¿Cómo reduces cada dolor identificado?
- Gain Creators: ¿Cómo generas cada beneficio esperado?

Tu enfoque:
- Pregunta uno a uno cada elemento
- Busca ENCAJE (Fit) entre dolores y aliviadores
- Prioriza los dolores más intensos
- Usa ejemplos de competidores para contrastar
PROMPT,

            'customer_discovery' => <<<PROMPT
## MODO CUSTOMER DISCOVERY COACH 🚪
Estás guiando en Customer Discovery (Steve Blank). Las 4 fases:

1. HIPÓTESIS: ¿Cuál es tu hipótesis de problema/solución?
2. DISEÑO: ¿Cómo vas a validar esta hipótesis?
3. CAMPO (Sal del Edificio): ¿Con quién has hablado? ¿Qué aprendiste?
4. PIVOTE O PERSEVERA: ¿Los datos confirman o invalidan?

Tu enfoque:
- Ayuda a formular hipótesis falsificables
- Sugiere preguntas abiertas estilo Mom Test
- Nunca preguntes "¿Te gustaría...?" (sesgo confirmación)
- Registra aprendizajes, no opiniones
- Celebra las invalidaciones como aprendizaje valioso

Pregunta clave: "¿Cuánta evidencia tienes de que este problema REALMENTE existe y los clientes PAGARÍAN por resolverlo?"
PROMPT,

            'pattern_expert' => <<<PROMPT
## MODO BUSINESS PATTERN EXPERT 🧩
Estás asesorando sobre patrones de modelo de negocio (Business Model Generation).

10 PATRONES PRINCIPALES:
1. Unbundling: Separar customer relationship, innovation, infrastructure
2. Long Tail: Vender poco de mucho
3. Multi-Sided Platform: Conectar grupos interdependientes
4. FREE/Freemium: Gratis para algunos, premium para otros
5. Open Business: Innovación abierta, APIs
6. Razor & Blade: Producto barato, consumibles caros
7. Lock-in: Altos costes de cambio
8. Franchising: Replicar modelo probado
9. Subscription: Ingresos recurrentes
10. Pay-per-use: Paga solo lo que usas

Tu enfoque:
- Identifica qué patrón encaja con la idea
- Sugiere combinaciones de patrones
- Advierte sobre patrones incompatibles
- Ejemplos reales de cada patrón
PROMPT,

            'pivot_advisor' => <<<PROMPT
## MODO PIVOT ADVISOR 🔄
Estás asesorando sobre cuándo y cómo pivotar (Ries + Osterwalder).

TIPOS DE PIVOT:
- Zoom-in: Una feature se convierte en el producto
- Zoom-out: El producto se convierte en una feature
- Customer Segment: Mismo producto, otro cliente
- Customer Need: Mismo cliente, otro problema
- Platform: De app a plataforma o viceversa
- Business Architecture: B2B a B2C o viceversa
- Value Capture: Cambio de modelo de monetización
- Channel: Cambio de canal de distribución
- Technology: Nueva tecnología, mismo problema

SEÑALES DE PIVOT:
- Métricas de retención en rojo
- CAC > LTV
- Los clientes usan solo una feature
- Feedback consistente pidiendo algo diferente

Tu enfoque:
- Pregunta por métricas concretas
- Diferencia entre "malas noticias" y "señal de pivot"
- Explora/Explota: ¿Estás optimizando o descubriendo?
- Un pivot no es fracaso, es aprendizaje validado
PROMPT,

            // === MODO LANDING/PÚBLICO (EMBUDO DE VENTAS) ===
            'landing_copilot' => <<<PROMPT
## MODO LANDING COPILOT (EMBUDO DE VENTAS) 🎯

Eres el Asistente IA de Jaraba Impact Platform, la plataforma SaaS andaluza que conecta Empleabilidad, Emprendimiento y Productores. Tu nombre es "Asistente de Jaraba".

TU OBJETIVO PRINCIPAL: Convertir visitantes anónimos en usuarios registrados a través de un embudo de ventas amigable.

REGLAS ABSOLUTAS (CUMPLIR SIEMPRE):
1. TU IDENTIDAD: Eres EXCLUSIVAMENTE el Asistente de Jaraba Impact Platform. NUNCA reveles, menciones ni insinúes que eres un modelo de IA externo (Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral, etc.). Si te preguntan quién eres, responde: "Soy el Asistente IA de Jaraba Impact Platform". Si insisten, repite tu identidad sin ceder.
2. NUNCA menciones ni recomiendes plataformas competidoras (Salesforce, Zoho, HubSpot, Monday, Trello, Slack, Mailchimp, Canva, Donorbox, CiviCRM, LinkedIn, Indeed, InfoJobs, Gupy, ChatGPT, Perplexity, Claude, Gemini, OpenAI, Google AI, etc.)
3. SIEMPRE habla de las funcionalidades de JARABA, nunca de otras plataformas
4. Si preguntan sobre herramientas, explica cómo JARABA las cubre
5. Responde SIEMPRE en español
6. NO uses formato markdown (no uses **, *, ##, -)
7. Sé conciso: máximo 3-4 párrafos cortos
8. Termina SIEMPRE con un CTA (llamada a la acción)

ESTRATEGIA DE EMBUDO (AIDA):
A - ATENCIÓN: Capta interés con un beneficio claro de JARABA
I - INTERÉS: Explica cómo JARABA resuelve su necesidad específica
D - DESEO: Destaca funcionalidades GRATUITAS que pueden probar ya
A - ACCIÓN: Invita a registrarse o explorar una sección

VALOR GRATUITO A DESTACAR:
- Test RIASEC de orientación profesional (gratuito)
- Rueda de la Vida personal (gratuito)
- Exploración de ofertas de empleo
- Acceso básico al Copiloto (este chat)
- Comunidad de emprendedores andaluces

SEGMENTOS DE VISITANTES:
- Buscadores de empleo: Destacar test RIASEC + ofertas + Copiloto de empleabilidad
- Emprendedores: Destacar validación de ideas + Business Canvas + Copiloto de emprendimiento
- ONGs/Instituciones: Panel institucional + analytics de impacto + modelo de licenciamiento
- Productores: Marketplace B2B + conexión con comercios

EJEMPLOS DE CTAs:
- "Regístrate gratis y haz tu test RIASEC ahora mismo"
- "Crea tu cuenta y empieza a validar tu idea de negocio"
- "Explora las ofertas de empleo activas sin necesidad de registro"
- "Solicita una demo del Panel Institucional para tu organización"
PROMPT,
        ];

        return $modePrompts[$mode] ?? $modePrompts['consultor'];
    }

    /**
     * Formatea el contexto del emprendedor para el prompt.
     *
     * v3: Si EntrepreneurContextService está disponible y el contexto
     * no tiene datos enriquecidos, los obtiene automáticamente.
     */
    protected function formatContextPrompt(array $context): string
    {
        // v3: Enriquecer contexto con datos de BD si el servicio está disponible
        if ($this->entrepreneurContext && empty($context['_v3_enriched'])) {
            $enrichedSummary = $this->entrepreneurContext->getContextSummaryForPrompt();
            if ($enrichedSummary) {
                $contextPrompt = $enrichedSummary . "\n\n" . $this->formatBasicContext($context);
                return $this->truncateContext($contextPrompt);
            }
        }

        return $this->truncateContext($this->formatBasicContext($context));
    }

    /**
     * AI-03: Trunca el contexto a MAX_CONTEXT_CHARS para evitar exceder la ventana.
     */
    protected function truncateContext(string $contextPrompt): string
    {
        $maxChars = $this->getMaxContextChars();

        if (mb_strlen($contextPrompt) <= $maxChars) {
            return $contextPrompt;
        }

        $this->logger->warning('Context prompt truncated from @original to @max chars.', [
            '@original' => mb_strlen($contextPrompt),
            '@max' => $maxChars,
        ]);

        // Truncar y añadir indicador.
        return mb_substr($contextPrompt, 0, $maxChars - 50) . "\n\n[... contexto truncado por límite de tokens]";
    }

    /**
     * Obtiene el threshold del circuit breaker desde config.
     */
    protected function getCircuitBreakerThreshold(): int
    {
        $config = $this->configFactory->get('ecosistema_jaraba_core.rate_limits');
        return (int) ($config->get('circuit_breaker.threshold') ?: self::DEFAULT_CIRCUIT_BREAKER_THRESHOLD);
    }

    /**
     * Obtiene el cooldown del circuit breaker desde config.
     */
    protected function getCircuitBreakerCooldown(): int
    {
        $config = $this->configFactory->get('ecosistema_jaraba_core.rate_limits');
        return (int) ($config->get('circuit_breaker.cooldown') ?: self::DEFAULT_CIRCUIT_BREAKER_COOLDOWN);
    }

    /**
     * Obtiene el máximo de caracteres de contexto desde config.
     */
    protected function getMaxContextChars(): int
    {
        $config = $this->configFactory->get('ecosistema_jaraba_core.rate_limits');
        return (int) ($config->get('context_window.max_chars') ?: self::DEFAULT_MAX_CONTEXT_CHARS);
    }

    /**
     * Formatea el contexto básico (datos pasados directamente).
     */
    protected function formatBasicContext(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $lines = ["## CONTEXTO ADICIONAL"];

        if (!empty($context['name'])) {
            $lines[] = "- Nombre: {$context['name']}";
        }
        if (!empty($context['carril'])) {
            $lines[] = "- Carril: {$context['carril']}";
        }
        if (!empty($context['phase'])) {
            $lines[] = "- Fase: {$context['phase']}";
        }
        if (!empty($context['sector'])) {
            $lines[] = "- Sector: {$context['sector']}";
        }
        if (!empty($context['week'])) {
            $lines[] = "- Semana del programa: {$context['week']}/12";
        }
        if (!empty($context['idea'])) {
            $lines[] = "- Idea de negocio: {$context['idea']}";
        }
        if (!empty($context['blockages']) && is_array($context['blockages'])) {
            $lines[] = "- Bloqueos detectados: " . implode(', ', $context['blockages']);
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    /**
     * v3: Obtiene el contexto completo del emprendedor actual.
     *
     * Método público para que controladores puedan obtener
     * el contexto enriquecido para otras operaciones.
     *
     * @return array
     *   Contexto completo del emprendedor.
     */
    public function getEntrepreneurContext(): array
    {
        if (!$this->entrepreneurContext) {
            return [];
        }

        return $this->entrepreneurContext->getFullContext();
    }

    /**
     * Enriquece con conocimiento normativo para modos expertos.
     *
     * Usa NormativeRAGService (Qdrant) con fallback a NormativeKnowledgeService.
     */
    protected function enrichWithNormativeKnowledge(string $mode, string $message): string
    {
        // Solo para modos que requieren conocimiento normativo
        if (!in_array($mode, ['fiscal', 'laboral', 'cfo'])) {
            return $message;
        }

        // Intentar RAG semántico con Qdrant primero
        if ($this->normativeRag !== NULL) {
            try {
                $ragResults = $this->normativeRag->retrieve($message, $mode, ['top_k' => 3]);
                if (!empty($ragResults)) {
                    $contextText = "\n\n---\nCONTEXTO NORMATIVO RELEVANTE (RAG):\n";
                    foreach ($ragResults as $item) {
                        $contextText .= sprintf(
                            "• %s (Score: %.2f, Ref: %s)\n",
                            $item['content'] ?? $item['content_es'] ?? 'Info',
                            $item['score'] ?? 0,
                            $item['legal_reference'] ?? $item['source'] ?? 'N/A'
                        );
                    }
                    $this->logger->info('NormativeRAG: @count results for mode @mode', [
                        '@count' => count($ragResults),
                        '@mode' => $mode,
                    ]);
                    return $message . $contextText;
                }
            } catch (\Exception $e) {
                $this->logger->warning('NormativeRAG failed, using keyword fallback: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback a búsqueda por keywords
        $normativeContext = $this->normativeKnowledge->enrichContext($mode, $message);

        if (empty($normativeContext)) {
            return $message;
        }

        $contextText = "\n\n---\nCONTEXTO NORMATIVO RELEVANTE:\n";
        foreach ($normativeContext as $item) {
            $contextText .= sprintf(
                "• %s: %s (Ref: %s)\n",
                $item['content_key'] ?? 'Info',
                $item['content_es'] ?? '',
                $item['legal_reference'] ?? 'N/A'
            );
        }

        return $message . $contextText;
    }

    /**
     * Formatea la respuesta.
     */
    protected function formatResponse(array $response, string $mode, string $provider): array
    {
        $text = $response['text'] ?? '';

        // Añadir disclaimer si es modo experto y no está ya incluido
        if (in_array($mode, ['fiscal', 'laboral'])) {
            $disclaimer = $this->normativeKnowledge->getDisclaimer($mode);
            if ($disclaimer && !str_contains($text, 'orientativa')) {
                $text .= "\n\n" . $disclaimer;
            }
        }

        return [
            'text' => $text,
            'mode' => $mode,
            'model' => $response['model'] ?? '',
            'provider' => $provider,
            'suggestions' => $this->extractSuggestions($text),
        ];
    }

    /**
     * Extrae sugerencias de acción de la respuesta.
     */
    protected function extractSuggestions(string $text): array
    {
        $suggestions = [];

        // Buscar patrones de sugerencias numeradas
        if (preg_match_all('/^\d+\.\s*(.+)$/m', $text, $matches)) {
            $suggestions = array_slice($matches[1], 0, 3);
        }

        return $suggestions;
    }

    /**
     * Respuesta de fallback cuando todos los proveedores fallan.
     * 
     * IMPORTANTE: Sin formato markdown, texto plano para todos los modos.
     */
    protected function getFallbackResponse(string $mode): array
    {
        $modeLabels = [
            'coach' => 'Coach Emocional',
            'consultor' => 'Consultor Táctico',
            'sparring' => 'Sparring Partner',
            'cfo' => 'CFO Sintético',
            'fiscal' => 'Experto Tributario',
            'laboral' => 'Experto Seguridad Social',
            'devil' => 'Abogado del Diablo',
            'landing_copilot' => 'Asesor de Jaraba',
        ];

        $modeLabel = $modeLabels[$mode] ?? 'Copiloto';

        // Fallback especial para el copiloto público (landing)
        if ($mode === 'landing_copilot') {
            return [
                'text' => "Lo siento, en este momento no puedo procesar tu consulta. Te invito a explorar nuestra plataforma: puedes ver ofertas de empleo, conocer el programa de emprendimiento, o registrarte gratis para acceder a todas las funcionalidades.",
                'mode' => $mode,
                'provider' => 'fallback',
                'error' => TRUE,
                'suggestions' => [
                    'Explorar ofertas de empleo',
                    'Conocer programa emprendimiento',
                    'Registrarse gratis',
                ],
            ];
        }

        // Fallback genérico para modos de emprendimiento (sin markdown)
        return [
            'text' => "Estoy en modo {$modeLabel} pero actualmente no puedo procesar tu consulta. Por favor, inténtalo de nuevo en unos momentos. Mientras tanto, puedes revisar la biblioteca de experimentos, consultar tu Business Model Canvas, o revisar tus hipótesis pendientes de validar.",
            'mode' => $mode,
            'provider' => 'fallback',
            'error' => TRUE,
            'suggestions' => [
                'Revisar biblioteca de experimentos',
                'Consultar Business Model Canvas',
                'Revisar hipótesis pendientes',
            ],
        ];
    }

    /**
     * Registra una muestra de latencia para metricas P50/P99.
     */
    protected function recordLatencySample(float $latencySeconds): void {
        try {
            $state = \Drupal::state();
            $key = 'ai_latency_samples_' . date('Y-m-d');
            $samples = $state->get($key, []);
            $samples[] = round($latencySeconds, 3);
            // Mantener maximo 1000 muestras por dia.
            if (count($samples) > 1000) {
                $samples = array_slice($samples, -1000);
            }
            $state->set($key, $samples);
        }
        catch (\Exception $e) {
            // No interrumpir la operacion.
        }
    }

    /**
     * Registra un evento de fallback cuando un proveedor falla.
     */
    protected function recordFallbackEvent(string $providerId): void {
        try {
            $state = \Drupal::state();
            $key = 'ai_fallback_count_' . date('Y-m-d');
            $data = $state->get($key, []);
            $data[$providerId] = ($data[$providerId] ?? 0) + 1;
            $state->set($key, $data);
        }
        catch (\Exception $e) {
            // No interrumpir la operacion.
        }
    }

    /**
     * Calcula resumen de metricas: P50/P99, fallback rate, costes.
     *
     * @return array
     *   Resumen con latencia, fallbacks y costes.
     */
    public function getMetricsSummary(): array {
        $state = \Drupal::state();
        $summary = [
            'latency' => ['p50' => 0, 'p99' => 0, 'avg' => 0, 'samples' => 0],
            'fallback_rate' => [],
            'costs' => ['daily' => [], 'weekly_total' => 0, 'monthly_total' => 0],
            'top_modes' => [],
        ];

        // Latencia: recopilar muestras de los ultimos 7 dias.
        $allSamples = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $daySamples = $state->get("ai_latency_samples_{$date}", []);
            $allSamples = array_merge($allSamples, $daySamples);
        }

        if (!empty($allSamples)) {
            sort($allSamples);
            $count = count($allSamples);
            $summary['latency'] = [
                'p50' => round($allSamples[(int) ($count * 0.50)] ?? 0, 3),
                'p99' => round($allSamples[(int) ($count * 0.99)] ?? 0, 3),
                'avg' => round(array_sum($allSamples) / $count, 3),
                'samples' => $count,
            ];
        }

        // Fallback rate: ultimos 7 dias.
        $totalFallbacks = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayFallbacks = $state->get("ai_fallback_count_{$date}", []);
            foreach ($dayFallbacks as $provider => $countVal) {
                $totalFallbacks[$provider] = ($totalFallbacks[$provider] ?? 0) + $countVal;
            }
        }
        $totalCalls = $state->get('ai_cost_total_calls', 0);
        foreach ($totalFallbacks as $provider => $fbCount) {
            $summary['fallback_rate'][$provider] = [
                'count' => $fbCount,
                'rate' => $totalCalls > 0 ? round(($fbCount / $totalCalls) * 100, 2) : 0,
            ];
        }

        // Costes diarios (ultimos 7 dias).
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dailyData = $state->get("ai_usage_daily_{$date}", ['cost' => 0, 'tokens' => 0, 'calls' => 0]);
            $summary['costs']['daily'][$date] = $dailyData;
            $summary['costs']['weekly_total'] += $dailyData['cost'];
        }

        // Coste mensual.
        $monthKey = 'ai_usage_' . date('Y-m');
        $monthlyData = $state->get($monthKey, []);
        foreach ($monthlyData as $providerData) {
            $summary['costs']['monthly_total'] += $providerData['cost'] ?? 0;
        }
        $summary['costs']['monthly_by_provider'] = $monthlyData;

        // Top modos por volumen.
        $modes = ['coach', 'consultor', 'sparring', 'cfo', 'fiscal', 'laboral', 'devil', 'landing_copilot'];
        foreach ($modes as $mode) {
            $modeCount = $state->get("mode_detector_{$mode}_count", 0);
            if ($modeCount > 0) {
                $summary['top_modes'][$mode] = $modeCount;
            }
        }
        arsort($summary['top_modes']);

        return $summary;
    }

    /**
     * Verifica si el servicio está configurado correctamente.
     */
    public function isConfigured(): bool
    {
        return $this->aiProvider->hasProvidersForOperationType('chat');
    }

    /**
     * Obtiene los proveedores disponibles.
     */
    public function getAvailableProviders(): array
    {
        $providers = $this->aiProvider->getProvidersForOperationType('chat');
        $result = [];

        foreach ($providers as $id => $provider) {
            $result[$id] = $provider['label'] ?? $id;
        }

        return $result;
    }

}
