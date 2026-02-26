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
 * Agente de Ventas IA para consumidores AgroConecta.
 *
 * ACCIONES:
 * - recommend_products: Recomendaciones personalizadas (balanced).
 * - search_catalog: Busqueda semantica de productos (fast).
 * - handle_cart: Gestion de carrito (fast).
 * - answer_faq: Responde preguntas frecuentes (fast).
 * - chat: Conversacion libre orientada a ventas (balanced).
 * - recover_cart: Mensaje de recuperacion de carrito (fast).
 *
 * Usa Model Routing inteligente:
 * - FAQ, cart, search -> tier fast.
 * - Recommendations, chat -> tier balanced.
 */
class SalesAgent extends SmartBaseAgent {

  /**
   * Constructs a SalesAgent.
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
  public function getAgentId(): string {
    return 'sales_agent';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Sales Agent AgroConecta';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Asistente IA de ventas para consumidores AgroConecta: recomienda productos, gestiona carrito, responde FAQs y recupera carritos abandonados.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    return [
      'recommend_products' => [
        'label' => 'Recomendar Productos',
        'description' => 'Genera recomendaciones personalizadas basadas en preferencias y historial.',
        'requires' => [],
        'optional' => ['user_preferences', 'viewed_products', 'category', 'price_range'],
        'complexity' => 'medium',
      ],
      'search_catalog' => [
        'label' => 'Buscar en Catalogo',
        'description' => 'Busqueda semantica de productos por texto libre.',
        'requires' => ['query'],
        'optional' => ['category', 'price_range', 'origin'],
        'complexity' => 'low',
      ],
      'handle_cart' => [
        'label' => 'Gestionar Carrito',
        'description' => 'Anadir/eliminar productos, aplicar cupones, calcular totales.',
        'requires' => ['action_type'],
        'optional' => ['product_id', 'quantity', 'coupon_code'],
        'complexity' => 'low',
      ],
      'answer_faq' => [
        'label' => 'Responder FAQ',
        'description' => 'Responde preguntas frecuentes sobre envio, pagos, devoluciones.',
        'requires' => ['question'],
        'optional' => ['category'],
        'complexity' => 'low',
      ],
      'chat' => [
        'label' => 'Chat Ventas',
        'description' => 'Conversacion libre orientada a asistencia de compra.',
        'requires' => ['message'],
        'optional' => ['conversation_history', 'current_page', 'cart_contents'],
        'complexity' => 'medium',
      ],
      'recover_cart' => [
        'label' => 'Recuperar Carrito',
        'description' => 'Genera mensaje personalizado para recuperar carrito abandonado.',
        'requires' => ['cart_items'],
        'optional' => ['cart_total', 'hours_abandoned', 'customer_name'],
        'complexity' => 'low',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(string $action, array $context): array {
    return match ($action) {
      'recommend_products' => $this->executeRecommendProducts($context),
      'search_catalog' => $this->executeSearchCatalog($context),
      'handle_cart' => $this->executeHandleCart($context),
      'answer_faq' => $this->executeAnswerFaq($context),
      'chat' => $this->executeChat($context),
      'recover_cart' => $this->executeRecoverCart($context),
      default => [
        'success' => FALSE,
        'error' => "Accion no soportada: {$action}",
      ],
    };
  }

  /**
   * Genera recomendaciones personalizadas de productos (balanced tier).
   */
  protected function executeRecommendProducts(array $context): array {
    $preferences = $context['user_preferences'] ?? [];
    $viewed = $context['viewed_products'] ?? [];
    $category = $context['category'] ?? '';
    $priceRange = $context['price_range'] ?? '';

    $contextBlock = '';
    if (!empty($preferences)) {
      $contextBlock .= "\nPREFERENCIAS: " . json_encode($preferences, JSON_UNESCAPED_UNICODE);
    }
    if (!empty($viewed)) {
      $contextBlock .= "\nPRODUCTOS VISTOS: " . json_encode($viewed, JSON_UNESCAPED_UNICODE);
    }
    if ($category) {
      $contextBlock .= "\nCATEGORIA: {$category}";
    }
    if ($priceRange) {
      $contextBlock .= "\nRANGO PRECIO: {$priceRange}";
    }

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Recomendar productos agroalimentarios personalizados para un consumidor.
{$contextBlock}

REQUISITOS:
- 3-5 recomendaciones ordenadas por relevancia
- Justificar cada recomendacion brevemente
- Sugerir combinaciones (cross-sell) si aplica
- Tono cercano y apetitoso

FORMATO JSON:
{
  "recommendations": [
    {"product_hint": "descripcion", "reason": "por que", "category": "cat"}
  ],
  "cross_sell_suggestion": "sugerencia de combo si aplica",
  "message": "Mensaje conversacional para el consumidor"
}
EOT;

    $response = $this->callAiApi($prompt);
    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'product_recommendations';
      }
    }
    return $response;
  }

  /**
   * Busqueda semantica en catalogo de productos (fast tier).
   */
  protected function executeSearchCatalog(array $context): array {
    $query = $context['query'] ?? '';
    $category = $context['category'] ?? '';
    $origin = $context['origin'] ?? '';

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Interpretar busqueda del consumidor y generar filtros de catalogo.

CONSULTA: "{$query}"
CATEGORIA FILTRO: {$category}
ORIGEN FILTRO: {$origin}

REQUISITOS:
- Extraer entidades: categoria, tipo producto, origen, rango precio
- Sugerir terminos de busqueda alternativos
- Generar respuesta conversacional

FORMATO JSON:
{
  "search_filters": {"category": "", "origin": "", "price_min": null, "price_max": null, "keywords": []},
  "alternative_queries": ["alt1", "alt2"],
  "message": "Respuesta conversacional"
}
EOT;

    $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);
    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'catalog_search';
      }
    }
    return $response;
  }

  /**
   * Gestiona acciones del carrito de compra (fast tier).
   */
  protected function executeHandleCart(array $context): array {
    $actionType = $context['action_type'] ?? 'view';
    $productId = $context['product_id'] ?? NULL;
    $quantity = $context['quantity'] ?? 1;
    $couponCode = $context['coupon_code'] ?? '';

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Generar respuesta para gestion de carrito.

ACCION: {$actionType}
PRODUCTO ID: {$productId}
CANTIDAD: {$quantity}
CUPON: {$couponCode}

Genera un mensaje conversacional amigable confirmando la accion del carrito.

FORMATO JSON:
{
  "message": "Respuesta conversacional",
  "action_confirmed": "{$actionType}",
  "upsell_suggestion": "sugerencia opcional de producto complementario"
}
EOT;

    $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);
    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'cart_action';
      }
    }
    return $response;
  }

  /**
   * Responde preguntas frecuentes del consumidor (fast tier).
   */
  protected function executeAnswerFaq(array $context): array {
    $question = $context['question'] ?? '';

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Responder pregunta frecuente de consumidor de marketplace agroalimentario.

PREGUNTA: "{$question}"

TEMAS QUE PUEDES CUBRIR:
- Envio y plazos de entrega
- Metodos de pago
- Politica de devoluciones
- Trazabilidad y certificaciones
- Como contactar a un productor
- Funcionamiento de cupones
- Condiciones de productos frescos

REQUISITOS:
- Respuesta directa y util (max 150 palabras)
- Si no sabes la respuesta, sugiere contactar soporte
- Tono amable y profesional

FORMATO JSON:
{
  "answer": "Respuesta a la pregunta",
  "category": "shipping|payment|returns|traceability|contact|coupons|other",
  "confidence": 0.0-1.0,
  "related_questions": ["pregunta relacionada 1"]
}
EOT;

    $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);
    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'faq_answer';
      }
    }
    return $response;
  }

  /**
   * Chat libre orientado a asistencia de compra (balanced tier).
   */
  protected function executeChat(array $context): array {
    $message = $context['message'] ?? '';
    $history = $context['conversation_history'] ?? '';
    $currentPage = $context['current_page'] ?? '';
    $cartContents = $context['cart_contents'] ?? '';

    $contextBlock = '';
    if ($history) {
      $contextBlock .= "\nHISTORIAL:\n{$history}";
    }
    if ($currentPage) {
      $contextBlock .= "\nPAGINA ACTUAL: {$currentPage}";
    }
    if ($cartContents) {
      $contextBlock .= "\nCARRITO: {$cartContents}";
    }

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Responder como asistente de ventas IA de marketplace agroalimentario.
{$contextBlock}

MENSAJE DEL CONSUMIDOR: {$message}

CAPACIDADES:
- Buscar y recomendar productos
- Gestionar carrito (anadir, eliminar, cupones)
- Consultar estado de pedidos
- Responder FAQs (envio, pagos, devoluciones)
- Sugerir productos complementarios (cross-sell)

REQUISITOS:
- Conciso y orientado a la conversion (max 200 palabras)
- Si detectas intencion de compra, facilita el proceso
- Usa emojis con moderacion (max 2)
- Idioma: espanol

FORMATO JSON:
{
  "response": "Respuesta al consumidor",
  "detected_intent": "browse|search|recommend|cart|order_status|faq|complaint|greeting|farewell",
  "suggested_actions": ["accion 1"],
  "products_to_show": []
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
   * Genera mensaje de recuperacion de carrito abandonado (fast tier).
   */
  protected function executeRecoverCart(array $context): array {
    $cartItems = $context['cart_items'] ?? [];
    $cartTotal = $context['cart_total'] ?? 0;
    $hoursAbandoned = $context['hours_abandoned'] ?? 1;
    $customerName = $context['customer_name'] ?? '';

    $itemsList = is_array($cartItems) ? json_encode($cartItems, JSON_UNESCAPED_UNICODE) : $cartItems;

    $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Generar mensaje personalizado para recuperar carrito abandonado.

CLIENTE: {$customerName}
PRODUCTOS EN CARRITO: {$itemsList}
TOTAL: {$cartTotal}
HORAS ABANDONADO: {$hoursAbandoned}

REQUISITOS:
- Tono amigable, no agresivo
- Recordar los productos especificos
- Ofrecer incentivo si lleva >24h
- Call-to-action claro
- Max 100 palabras

FORMATO JSON:
{
  "message": "Mensaje de recuperacion",
  "subject": "Asunto para email",
  "incentive_type": "none|free_shipping|discount_5|discount_10",
  "urgency_level": "low|medium|high"
}
EOT;

    $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);
    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'cart_recovery';
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return "Eres el Asistente de Ventas IA de AgroConecta, un marketplace de productos agroalimentarios de proximidad. " .
      "Tu mision es ayudar a los consumidores a descubrir y comprar productos artesanales de calidad. " .
      "Tono: amigable, entusiasta sobre los productos, orientado a la conversion pero sin presionar. " .
      "Valoras la calidad artesanal, el origen local, la trazabilidad y la sostenibilidad. " .
      "Nunca mientes sobre productos ni inventas caracteristicas.";
  }

}
