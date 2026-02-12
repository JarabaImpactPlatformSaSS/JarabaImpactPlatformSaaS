<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Psr\Log\LoggerInterface;

/**
 * Agente Copiloto Merchant para ComercioConecta.
 *
 * ACCIONES:
 * - generate_description: Descripcion atractiva para productos (balanced).
 * - suggest_price: Precio basado en mercado local (fast).
 * - social_post: Post para Instagram/Facebook con hashtags locales (balanced).
 * - flash_offer: Oferta flash para stock lento (balanced).
 * - respond_review: Respuesta profesional a resenas (fast).
 * - email_promo: Email promocional para campana (balanced).
 *
 * System prompt especializado para comercios locales con contexto de
 * nombre, sector, ubicacion, productos y valoracion media.
 *
 * F8 — Doc 184.
 */
class MerchantCopilotAgent extends SmartBaseAgent {

  /**
   * Constructs a MerchantCopilotAgent.
   */
  public function __construct(
    AiProviderPluginManager $aiProvider,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    TenantBrandVoiceService $brandVoice,
    AIObservabilityService $observability,
    ModelRouterService $modelRouter,
    ?UnifiedPromptBuilder $promptBuilder = NULL,
  ) {
    parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
    $this->setModelRouter($modelRouter);
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return 'merchant_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Copiloto del Comerciante';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Asistente IA para comercios locales ComercioConecta: genera descripciones, sugiere precios, crea posts sociales, ofertas flash, responde resenas y crea emails promocionales.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    return [
      'generate_description' => [
        'label' => 'Generar Descripcion Atractiva',
        'description' => 'Genera descripciones de producto optimizadas para venta online en comercio local.',
        'requires' => ['product_name'],
        'optional' => ['product_category', 'product_price', 'product_features', 'target_audience'],
        'complexity' => 'medium',
      ],
      'suggest_price' => [
        'label' => 'Sugerir Precio Local',
        'description' => 'Analiza el mercado local y sugiere precio competitivo con estrategia de posicionamiento.',
        'requires' => ['product_name', 'current_price'],
        'optional' => ['competitor_prices', 'product_category', 'is_handmade', 'margin_target'],
        'complexity' => 'low',
      ],
      'social_post' => [
        'label' => 'Crear Post Social',
        'description' => 'Genera contenido para Instagram/Facebook con hashtags locales y call-to-action.',
        'requires' => ['product_name', 'platform'],
        'optional' => ['objective', 'tone', 'hashtags_count', 'city', 'promotion'],
        'complexity' => 'medium',
      ],
      'flash_offer' => [
        'label' => 'Sugerir Oferta Flash',
        'description' => 'Analiza stock y ventas para sugerir oferta relampago con descuento optimo.',
        'requires' => ['product_name', 'current_price', 'stock_level'],
        'optional' => ['sales_last_30d', 'product_category', 'max_discount', 'duration_hours'],
        'complexity' => 'medium',
      ],
      'respond_review' => [
        'label' => 'Responder Resena',
        'description' => 'Genera respuesta profesional y cercana a una resena de cliente.',
        'requires' => ['review_rating', 'review_comment'],
        'optional' => ['product_name', 'reviewer_name', 'business_name'],
        'complexity' => 'low',
      ],
      'email_promo' => [
        'label' => 'Crear Email Promocional',
        'description' => 'Genera email promocional para campana de comercio local.',
        'requires' => ['product_name', 'objective', 'offer_details'],
        'optional' => ['urgency', 'personalization_fields', 'business_name', 'city'],
        'complexity' => 'medium',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(string $action, array $context): array {
    return match ($action) {
      'generate_description' => $this->executeGenerateDescription($context),
      'suggest_price' => $this->executeSuggestPrice($context),
      'social_post' => $this->executeSocialPost($context),
      'flash_offer' => $this->executeFlashOffer($context),
      'respond_review' => $this->executeRespondReview($context),
      'email_promo' => $this->executeEmailPromo($context),
      default => [
        'success' => FALSE,
        'error' => "Accion no soportada: {$action}",
      ],
    };
  }

  /**
   * Genera descripcion atractiva para producto (balanced tier).
   */
  protected function executeGenerateDescription(array $context): array {
    $productName = $context['product_name'] ?? 'Producto';
    $category = $context['product_category'] ?? '';
    $price = $context['product_price'] ?? '';
    $features = $context['product_features'] ?? '';
    $audience = $context['target_audience'] ?? '';

    $contextBlock = "PRODUCTO: {$productName}";
    if ($category) {
      $contextBlock .= "\nCATEGORIA: {$category}";
    }
    if ($price) {
      $contextBlock .= "\nPRECIO: {$price}€";
    }
    if ($features) {
      $contextBlock .= "\nCARACTERISTICAS: {$features}";
    }
    if ($audience) {
      $contextBlock .= "\nPUBLICO OBJETIVO: {$audience}";
    }

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Generar una descripcion atractiva para un producto de comercio local.

{$contextBlock}

REQUISITOS:
- 2-3 parrafos, maximo 150 palabras
- Destacar calidad, proximidad y servicio del comercio local
- Tono cercano y local (no corporativo)
- Incluir call-to-action hacia la tienda
- Keywords naturales para SEO local
- Idioma: espanol

FORMATO JSON:
{
  "title_seo": "Titulo optimizado (max 60 chars)",
  "meta_description": "Meta description (max 155 chars)",
  "description": "Descripcion completa con formato markdown",
  "keywords": ["keyword1", "keyword2"],
  "highlights": ["Punto destacado 1", "Punto destacado 2"],
  "cta": "Call-to-action sugerido"
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
   * Sugiere precio basado en mercado local (fast tier).
   */
  protected function executeSuggestPrice(array $context): array {
    $productName = $context['product_name'] ?? 'Producto';
    $currentPrice = $context['current_price'] ?? 0;
    $competitorPrices = $context['competitor_prices'] ?? [];
    $category = $context['product_category'] ?? '';
    $isHandmade = $context['is_handmade'] ?? FALSE;
    $marginTarget = $context['margin_target'] ?? '';

    $competitorInfo = !empty($competitorPrices)
      ? 'PRECIOS COMPETIDORES LOCALES: ' . implode('€, ', $competitorPrices) . '€'
      : 'Sin datos de competidores';

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Analizar y sugerir precio competitivo para un producto de comercio local.

PRODUCTO: {$productName}
CATEGORIA: {$category}
PRECIO ACTUAL: {$currentPrice}€
{$competitorInfo}
ARTESANAL: {($isHandmade ? 'Si (valor anadido)' : 'No')}
OBJETIVO MARGEN: {$marginTarget}

REGLAS:
- El precio sugerido debe estar en el rango ±20% del actual
- Considerar valor de proximidad y servicio local
- No recomendar precios depredadores

FORMATO JSON:
{
  "suggested_price": 0.00,
  "strategy": "premium|value|competitive",
  "reasoning": "Explicacion en 2-3 oraciones",
  "price_range": {"min": 0.00, "max": 0.00},
  "market_position": "Descripcion del posicionamiento local",
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
   * Crea post para redes sociales con hashtags locales (balanced tier).
   */
  protected function executeSocialPost(array $context): array {
    $productName = $context['product_name'] ?? 'Producto';
    $platform = $context['platform'] ?? 'instagram';
    $objective = $context['objective'] ?? 'engagement';
    $tone = $context['tone'] ?? 'cercano';
    $hashtagsCount = (int) ($context['hashtags_count'] ?? 7);
    $city = $context['city'] ?? '';
    $promotion = $context['promotion'] ?? '';

    $cityContext = $city ? "\nCIUDAD: {$city}" : '';
    $promoContext = $promotion ? "\nPROMOCION ACTIVA: {$promotion}" : '';

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Crear post para {$platform} de un comercio local.

PRODUCTO: {$productName}
PLATAFORMA: {$platform}
OBJETIVO: {$objective}
TONO: {$tone}{$cityContext}{$promoContext}

REQUISITOS:
- 1 parrafo principal (max 150 palabras para Instagram, 250 para Facebook)
- {$hashtagsCount} hashtags locales y de nicho (no genericos como #love o #instagood)
- Si hay ciudad, incluir hashtag local (#CompraEn[Ciudad])
- Call-to-action hacia la tienda o enlace bio
- Emojis con moderacion (3-5)
- Idioma: espanol

FORMATO JSON:
{
  "content": "Texto del post",
  "hashtags": "#hashtag1 #hashtag2 ...",
  "cta": "Call-to-action",
  "visual_suggestion": "Descripcion de imagen recomendada",
  "best_posting_time": "Hora optima de publicacion",
  "platform_tips": ["Tip especifico de la plataforma"]
}
EOT;

    $response = $this->callAiApi($prompt);

    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'social_post';
        $response['data']['platform'] = $platform;
        $response['data']['product_name'] = $productName;
      }
    }

    return $response;
  }

  /**
   * Sugiere oferta flash para producto con stock lento (balanced tier).
   *
   * Analiza stock y ventas de los ultimos 30 dias para sugerir
   * descuento optimo y duracion de la oferta.
   */
  protected function executeFlashOffer(array $context): array {
    $productName = $context['product_name'] ?? 'Producto';
    $currentPrice = $context['current_price'] ?? 0;
    $stockLevel = (int) ($context['stock_level'] ?? 0);
    $salesLast30d = (int) ($context['sales_last_30d'] ?? 0);
    $category = $context['product_category'] ?? '';
    $maxDiscount = (int) ($context['max_discount'] ?? 50);
    $durationHours = (int) ($context['duration_hours'] ?? 48);

    // Stock velocity analysis.
    $daysOfStock = $salesLast30d > 0
      ? round($stockLevel / ($salesLast30d / 30), 0)
      : 999;

    $stockStatus = match (TRUE) {
      $daysOfStock > 90 => 'MUY ALTO — stock para +3 meses, descuento agresivo recomendado',
      $daysOfStock > 45 => 'ALTO — stock para 1.5-3 meses, descuento moderado',
      $daysOfStock > 15 => 'NORMAL — stock saludable, descuento suave',
      default => 'BAJO — no recomendable hacer flash offer',
    };

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Sugerir oferta flash optima para un producto con stock acumulado.

PRODUCTO: {$productName}
CATEGORIA: {$category}
PRECIO ACTUAL: {$currentPrice}€
STOCK ACTUAL: {$stockLevel} unidades
VENTAS ULTIMOS 30 DIAS: {$salesLast30d} unidades
DIAS DE STOCK ESTIMADOS: {$daysOfStock}
ESTADO STOCK: {$stockStatus}
DESCUENTO MAXIMO PERMITIDO: {$maxDiscount}%
DURACION PREFERIDA: {$durationHours} horas

ANALISIS REQUERIDO:
- Porcentaje de descuento optimo (no superar el maximo)
- Duracion de la oferta para generar urgencia
- Copy de la oferta (titulo + descripcion corta)
- Canal recomendado (email, redes, web, todos)
- Estimacion de unidades que se venderian

REGLAS:
- Si stock es bajo, desaconsejar flash offer
- El descuento debe ser justo (no depredador)
- Incluir urgencia pero sin ser agresivo

FORMATO JSON:
{
  "recommended": true,
  "discount_percent": 0,
  "flash_price": 0.00,
  "original_price": 0.00,
  "duration_hours": 48,
  "title": "Titulo de la oferta",
  "description": "Descripcion corta con urgencia",
  "channel": "email|social|web|all",
  "estimated_sales": 0,
  "reasoning": "Justificacion de la estrategia",
  "stock_analysis": "Analisis del estado del stock",
  "urgency_copy": "Frase de urgencia para el anuncio"
}
EOT;

    $response = $this->callAiApi($prompt);

    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'flash_offer';
        $response['data']['product_name'] = $productName;
        $response['data']['current_price'] = $currentPrice;
        $response['data']['stock_level'] = $stockLevel;
        $response['data']['days_of_stock'] = $daysOfStock;
      }
    }

    return $response;
  }

  /**
   * Genera respuesta profesional a resena (fast tier).
   */
  protected function executeRespondReview(array $context): array {
    $rating = (int) ($context['review_rating'] ?? 3);
    $comment = $context['review_comment'] ?? '';
    $productName = $context['product_name'] ?? '';
    $reviewerName = $context['reviewer_name'] ?? '';
    $businessName = $context['business_name'] ?? 'tu comercio';

    $tone = match (TRUE) {
      $rating >= 4 => 'TONO: Agradecimiento genuino, calidez de comercio de barrio',
      $rating >= 3 => 'TONO: Profesional, constructivo, invitar a volver',
      default => 'TONO: Empatico, resolutivo, ofrecer solucion concreta',
    };

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Generar respuesta a resena de cliente de un comercio local.

RESENA:
- Rating: {$rating}/5 estrellas
- Comentario: "{$comment}"
- Producto: {$productName}
- Cliente: {$reviewerName}
- Negocio: {$businessName}

{$tone}

REQUISITOS:
- Respuesta personalizada (no generica)
- 2-4 oraciones maximo
- Tono cercano y local (como un comercio de barrio)
- Si es negativa, ofrecer solucion concreta
- Nunca ser condescendiente
- Mantener la marca del comercio

FORMATO JSON:
{
  "response": "Texto de la respuesta",
  "tone_used": "agradecimiento|profesional|empatico",
  "follow_up_needed": true,
  "internal_note": "Nota interna si es necesario"
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
   * Crea email promocional para campana de comercio local (balanced tier).
   */
  protected function executeEmailPromo(array $context): array {
    $productName = $context['product_name'] ?? 'Producto';
    $objective = $context['objective'] ?? 'venta';
    $offerDetails = $context['offer_details'] ?? '';
    $urgency = $context['urgency'] ?? 'media';
    $businessName = $context['business_name'] ?? 'Tu Comercio';
    $city = $context['city'] ?? '';

    $cityContext = $city ? "\nCIUDAD: {$city}" : '';

    $prompt = <<<EOT
VERTICAL: ComercioConecta — Comercio local online
TAREA: Crear email promocional para una campana de comercio local.

PRODUCTO: {$productName}
OBJETIVO: {$objective}
DETALLES OFERTA: {$offerDetails}
URGENCIA: {$urgency}
NOMBRE NEGOCIO: {$businessName}{$cityContext}

REQUISITOS:
- Subject line atractivo (max 50 chars, sin clickbait)
- Preview text que complementa el subject (max 80 chars)
- Saludo personalizable con [NOMBRE]
- Cuerpo: 3-4 parrafos cortos, HTML simple
- Call-to-action claro y unico
- Tono cercano y local
- Incluir sentido de urgencia sin ser agresivo
- P.S. line opciconal para reforzar la oferta

FORMATO JSON:
{
  "subject": "Linea de asunto",
  "preview_text": "Texto de preview (50 chars)",
  "greeting": "Saludo personalizable",
  "body": "Cuerpo del email en HTML simple",
  "cta_text": "Texto del boton CTA",
  "cta_url_suggestion": "Tipo de pagina destino",
  "ps_line": "Linea PS opcional",
  "send_time_suggestion": "Mejor hora para enviar"
}
EOT;

    $response = $this->callAiApi($prompt);

    if ($response['success']) {
      $parsed = $this->parseJsonResponse($response['data']['text']);
      if ($parsed) {
        $response['data'] = $parsed;
        $response['data']['content_type'] = 'email_promo';
        $response['data']['product_name'] = $productName;
        $response['data']['objective'] = $objective;
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return "Eres el Merchant Copilot de ComercioConecta, un asistente especializado en " .
      "ayudar a comercios locales a vender mas online. " .
      "Tu mision es impulsar las ventas con estrategias adaptadas al comercio de proximidad. " .
      "Tono: cercano, local, orientado a resultados practicos. " .
      "Valoras el servicio personalizado, la confianza del comercio de barrio y la comunidad local. " .
      "Solo puedes hablar de productos que existen en el catalogo del comercio. " .
      "Los precios que sugieras deben estar en el rango ±20% del actual. " .
      "No inventes caracteristicas que el producto no tenga. " .
      "Mantén el tono cercano y local (no corporativo). " .
      "Incluye siempre call-to-action hacia la tienda.";
  }

}
