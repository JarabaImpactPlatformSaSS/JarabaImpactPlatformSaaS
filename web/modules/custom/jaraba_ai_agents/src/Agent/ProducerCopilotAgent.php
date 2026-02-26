<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Psr\Log\LoggerInterface;

/**
 * Agente Copiloto para Productores AgroConecta.
 *
 * ACCIONES:
 * - generate_description: Descripción SEO para productos (balanced).
 * - suggest_price: Sugerencia de precio con análisis de mercado (fast).
 * - respond_review: Borrador de respuesta a reseñas (fast).
 * - chat: Conversación libre con contexto agroalimentario (balanced).
 *
 * Usa Model Routing inteligente para optimizar costes:
 * - Respuestas de reseña y precios → tier fast (bajo coste).
 * - Descripciones SEO y chat → tier balanced (calidad/coste).
 */
class ProducerCopilotAgent extends SmartBaseAgent
{

    /**
     * Constructs a ProducerCopilotAgent.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ModelRouterService $modelRouter,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
        if ($toolRegistry) {
            $this->setToolRegistry($toolRegistry);
        }
        if ($providerFallback) {
            $this->setProviderFallback($providerFallback);
        }
        if ($contextWindowManager) {
            $this->setContextWindowManager($contextWindowManager);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'producer_copilot';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Copiloto del Productor';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Asistente IA para productores AgroConecta: genera descripciones SEO, sugiere precios, redacta respuestas a reseñas y ofrece asistencia general.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'generate_description' => [
                'label' => 'Generar Descripción SEO',
                'description' => 'Genera descripciones optimizadas para SEO de productos agroalimentarios.',
                'requires' => ['product_name'],
                'optional' => ['product_category', 'product_price', 'product_origin', 'product_certifications'],
                'complexity' => 'medium',
            ],
            'suggest_price' => [
                'label' => 'Sugerir Precio',
                'description' => 'Analiza el mercado y sugiere un precio competitivo con estrategia de posicionamiento.',
                'requires' => ['product_name', 'current_price'],
                'optional' => ['competitor_prices', 'product_category', 'has_traceability'],
                'complexity' => 'low',
            ],
            'respond_review' => [
                'label' => 'Responder Reseña',
                'description' => 'Genera un borrador de respuesta a una reseña del cliente con el tono adecuado.',
                'requires' => ['review_rating', 'review_comment'],
                'optional' => ['product_name', 'reviewer_name'],
                'complexity' => 'low',
            ],
            'chat' => [
                'label' => 'Chat Copiloto',
                'description' => 'Conversación libre con contexto de producción agroalimentaria.',
                'requires' => ['message'],
                'optional' => ['conversation_history'],
                'complexity' => 'medium',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        return match ($action) {
            'generate_description' => $this->executeGenerateDescription($context),
            'suggest_price' => $this->executeSuggestPrice($context),
            'respond_review' => $this->executeRespondReview($context),
            'chat' => $this->executeChat($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Genera descripción SEO para un producto (balanced tier).
     */
    protected function executeGenerateDescription(array $context): array
    {
        $productName = $context['product_name'] ?? 'Producto';
        $category = $context['product_category'] ?? '';
        $price = $context['product_price'] ?? '';
        $origin = $context['product_origin'] ?? '';
        $certifications = $context['product_certifications'] ?? '';

        $contextBlock = "PRODUCTO: {$productName}";
        if ($category) {
            $contextBlock .= "\nCATEGORÍA: {$category}";
        }
        if ($price) {
            $contextBlock .= "\nPRECIO: {$price}€";
        }
        if ($origin) {
            $contextBlock .= "\nORIGEN: {$origin}";
        }
        if ($certifications) {
            $contextBlock .= "\nCERTIFICACIONES: {$certifications}";
        }

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Generar una descripción SEO-optimizada para un producto agroalimentario.

{$contextBlock}

REQUISITOS:
- 150-300 palabras
- Incluir keywords naturales para posicionamiento
- Destacar origen, calidad y trazabilidad
- Tono profesional pero cercano
- Idioma: español

FORMATO JSON:
{
  "title_seo": "Título optimizado para SEO (max 60 chars)",
  "meta_description": "Meta description (max 155 chars)",
  "description": "Descripción completa con formato markdown",
  "keywords": ["keyword1", "keyword2", "..."],
  "highlights": ["Punto destacado 1", "Punto destacado 2", "..."]
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'product_description';
                $response['data']['product_name'] = $productName;
            }
        }

        return $response;
    }

    /**
     * Sugiere precio competitivo (fast tier).
     */
    protected function executeSuggestPrice(array $context): array
    {
        $productName = $context['product_name'] ?? 'Producto';
        $currentPrice = $context['current_price'] ?? 0;
        $competitorPrices = $context['competitor_prices'] ?? [];
        $category = $context['product_category'] ?? '';
        $hasTraceability = $context['has_traceability'] ?? FALSE;

        $competitorInfo = !empty($competitorPrices)
            ? 'PRECIOS COMPETIDORES: ' . implode('€, ', $competitorPrices) . '€'
            : 'Sin datos de competidores';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Analizar y sugerir precio competitivo para un producto agroalimentario.

PRODUCTO: {$productName}
CATEGORÍA: {$category}
PRECIO ACTUAL: {$currentPrice}€
{$competitorInfo}
TRAZABILIDAD QR: {($hasTraceability ? 'Sí (valor añadido)' : 'No')}

ANÁLISIS REQUERIDO:
- Posicionamiento respecto a competidores
- Elasticidad de precio percibida
- Valor añadido por trazabilidad y origen
- Estrategia recomendada (low-cost, premium, value)

FORMATO JSON:
{
  "suggested_price": 0.00,
  "strategy": "premium|value|competitive",
  "reasoning": "Explicación en 2-3 oraciones",
  "price_range": {"min": 0.00, "max": 0.00},
  "market_position": "Descripción del posicionamiento",
  "tips": ["Consejo 1", "Consejo 2"]
}
EOT;

        $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'price_suggestion';
                $response['data']['product_name'] = $productName;
                $response['data']['current_price'] = $currentPrice;
            }
        }

        return $response;
    }

    /**
     * Genera borrador de respuesta a reseña (fast tier).
     */
    protected function executeRespondReview(array $context): array
    {
        $rating = (int) ($context['review_rating'] ?? 3);
        $comment = $context['review_comment'] ?? '';
        $productName = $context['product_name'] ?? '';
        $reviewerName = $context['reviewer_name'] ?? '';

        $tone = match (TRUE) {
            $rating >= 4 => 'TONO: Agradecimiento genuino y calidez',
            $rating >= 3 => 'TONO: Profesional, constructivo y empático',
            default => 'TONO: Empático, resolutivo y orientado a la recuperación del cliente',
        };

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Generar respuesta profesional a una reseña de cliente.

RESEÑA:
- Rating: {$rating}/5 estrellas
- Comentario: "{$comment}"
- Producto: {$productName}
- Cliente: {$reviewerName}

{$tone}

REQUISITOS:
- Respuesta personalizada (no genérica)
- 2-4 oraciones máximo
- Reflejar valores de productor local y cercanía
- Si es negativa, ofrecer solución concreta
- No ser condescendiente ni excesivamente formal

FORMATO JSON:
{
  "response": "Texto de la respuesta",
  "tone_used": "agradecimiento|profesional|empático",
  "follow_up_needed": true/false,
  "internal_note": "Nota interna para el productor si es necesario"
}
EOT;

        $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'review_response';
                $response['data']['original_rating'] = $rating;
            }
        }

        return $response;
    }

    /**
     * Chat libre con contexto agroalimentario (balanced tier).
     */
    protected function executeChat(array $context): array
    {
        $message = $context['message'] ?? '';
        $history = $context['conversation_history'] ?? '';

        $historyBlock = $history ? "\nHISTORIAL DE CONVERSACIÓN:\n{$history}\n" : '';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Responder como copiloto IA de un productor agroalimentario.
{$historyBlock}
MENSAJE DEL PRODUCTOR: {$message}

CAPACIDADES QUE PUEDES OFRECER:
- Generar descripciones SEO para productos
- Sugerir precios competitivos
- Redactar respuestas a reseñas de clientes
- Consejos de marketing y SEO para tiendas online
- Análisis de trazabilidad y certificaciones
- Estrategias de visibilidad en marketplace

REQUISITOS:
- Sé conciso y directo (máx 200 palabras)
- Si detectas que el productor necesita una acción específica, sugiérela
- Usa emojis con moderación (máx 2-3)
- Idioma: español

FORMATO JSON:
{
  "response": "Tu respuesta al productor",
  "detected_intent": "description|pricing|review|seo|general",
  "suggested_actions": ["acción sugerida si aplica"],
  "follow_up_question": "Pregunta de seguimiento si es relevante o null"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'chat_response';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return "Eres el Copiloto IA de AgroConecta, un marketplace de productos agroalimentarios de proximidad. " .
            "Tu misión es ayudar a los productores a vender más y mejor. " .
            "Tono: cercano, profesional, orientado a resultados. " .
            "Valoras la trazabilidad, el origen local, la sostenibilidad y la calidad artesanal. " .
            "Nunca recomiendas prácticas deshonestas ni precios depredadores.";
    }
}
