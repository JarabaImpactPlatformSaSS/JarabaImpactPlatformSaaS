<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

/**
 * Agente de Marketing para generación de contenido.
 *
 * PROPÓSITO:
 * Genera contenido de marketing optimizado para diferentes canales:
 * redes sociales, email marketing y publicidad digital.
 *
 * ACCIONES DISPONIBLES:
 * - 'social_post': Crear posts para redes sociales (Instagram, Twitter, LinkedIn)
 * - 'email_promo': Generar emails promocionales efectivos
 * - 'ad_copy': Crear textos para campañas publicitarias
 * - 'product_description': Generar descripciones SEO-friendly de productos
 *
 * CARACTERÍSTICAS:
 * - Adaptación automática al tono del tenant vía Brand Voice
 * - Optimización por plataforma (límites de caracteres, hashtags)
 * - Sugerencias de visuales y horarios óptimos
 * - Variantes A/B para testing
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class MarketingAgent extends BaseAgent
{

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'marketing_multi';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Agente de Marketing';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Genera contenido de marketing: posts para redes sociales, emails promocionales y copy para anuncios.';
    }

    /**
     * {@inheritdoc}
     *
     * Define las acciones disponibles con sus parámetros requeridos y opcionales.
     */
    public function getAvailableActions(): array
    {
        return [
            'social_post' => [
                'label' => 'Crear Post para Redes Sociales',
                'description' => 'Genera contenido optimizado para redes sociales.',
                'requires' => ['product_name', 'platform', 'objective'],
                'optional' => ['tone', 'hashtags_count', 'language'],
            ],
            'email_promo' => [
                'label' => 'Email de Marketing',
                'description' => 'Crea emails promocionales efectivos.',
                'requires' => ['product_name', 'objective', 'offer_details'],
                'optional' => ['urgency', 'personalization_fields'],
            ],
            'ad_copy' => [
                'label' => 'Copy para Anuncios',
                'description' => 'Genera textos para campañas publicitarias.',
                'requires' => ['product_name', 'platform', 'audience'],
                'optional' => ['budget', 'call_to_action'],
            ],
            'product_description' => [
                'label' => 'Descripción de Producto',
                'description' => 'Crea descripciones SEO-friendly para productos.',
                'requires' => ['product_name', 'features'],
                'optional' => ['benefits', 'target_audience', 'keywords'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Enruta la ejecución al método específico según la acción solicitada.
     */
    public function execute(string $action, array $context): array
    {
        // Registrar la acción para logging de observabilidad.
        $this->setCurrentAction($action);

        return match ($action) {
            'social_post' => $this->generateSocialPost($context),
            'email_promo' => $this->generateEmailPromo($context),
            'ad_copy' => $this->generateAdCopy($context),
            'product_description' => $this->generateProductDescription($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Genera un post para redes sociales.
     *
     * Crea contenido optimizado para la plataforma especificada,
     * incluyendo texto principal, hashtags, CTA y sugerencias de
     * visuales y timing.
     *
     * @param array $context
     *   Contexto con 'product_name', 'platform', 'objective'.
     *   Opcionales: 'tone', 'hashtags_count'.
     *
     * @return array
     *   Resultado con 'content', 'hashtags', 'cta', 'visual_suggestion'.
     */
    protected function generateSocialPost(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $platform = $context['platform'] ?? 'Instagram';
        $objective = $context['objective'] ?? 'Engagement';
        $tone = $context['tone'] ?? 'profesional pero cercano';
        $hashtagsCount = $context['hashtags_count'] ?? 5;

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear un post para {$platform}.

PRODUCTO/SERVICIO: {$product}
OBJETIVO: {$objective}
TONO: {$tone}

REQUISITOS:
- Contenido optimizado para {$platform}
- Máximo {$hashtagsCount} hashtags relevantes
- Call-to-action claro
- Sugerencia de visual/imagen

FORMATO DE RESPUESTA (JSON):
{
  "content": "Texto del post",
  "hashtags": "#hashtag1 #hashtag2",
  "cta": "Call-to-action",
  "visual_suggestion": "Descripción de imagen recomendada",
  "best_posting_time": "Hora óptima de publicación"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'social_post';
                $response['data']['platform'] = $platform;
            }
        }

        return $response;
    }

    /**
     * Genera un email promocional.
     *
     * Crea todos los elementos de un email de marketing efectivo:
     * asunto, preview text, saludo, cuerpo, CTA y línea PS opcional.
     *
     * @param array $context
     *   Contexto con 'product_name', 'objective', 'offer_details'.
     *   Opcionales: 'urgency', 'personalization_fields'.
     *
     * @return array
     *   Resultado con 'subject', 'preview_text', 'body', 'cta_text'.
     */
    protected function generateEmailPromo(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $objective = $context['objective'] ?? 'Ventas';
        $offerDetails = $context['offer_details'] ?? '';
        $urgency = $context['urgency'] ?? 'media';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear un email promocional efectivo.

PRODUCTO/SERVICIO: {$product}
OBJETIVO: {$objective}
DETALLES DE OFERTA: {$offerDetails}
URGENCIA: {$urgency}

REQUISITOS:
- Asunto que maximice tasa de apertura
- Preview text atractivo
- Cuerpo del email con estructura clara
- CTA prominente
- Estructura mobile-first

FORMATO DE RESPUESTA (JSON):
{
  "subject": "Línea de asunto",
  "preview_text": "Texto de preview (50 chars)",
  "greeting": "Saludo personalizable",
  "body": "Cuerpo del email en HTML simple",
  "cta_text": "Texto del botón CTA",
  "cta_url_suggestion": "Tipo de página destino",
  "ps_line": "Línea PS opcional"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'email_promo';
            }
        }

        return $response;
    }

    /**
     * Genera copy para anuncios digitales.
     *
     * Crea múltiples variantes de headlines y descripciones
     * para testing A/B, optimizados para la plataforma de ads.
     *
     * @param array $context
     *   Contexto con 'product_name', 'platform', 'audience'.
     *   Opcionales: 'budget', 'call_to_action'.
     *
     * @return array
     *   Resultado con 'headlines', 'descriptions', 'primary_text'.
     */
    protected function generateAdCopy(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $platform = $context['platform'] ?? 'Meta Ads';
        $audience = $context['audience'] ?? 'público general';
        $cta = $context['call_to_action'] ?? 'Más información';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear copy para anuncio en {$platform}.

PRODUCTO/SERVICIO: {$product}
AUDIENCIA TARGET: {$audience}
CTA DESEADO: {$cta}

REQUISITOS:
- Headlines que capten atención
- Descripciones persuasivas
- Variantes para testing A/B
- Respeta límites de caracteres de {$platform}

FORMATO DE RESPUESTA (JSON):
{
  "headlines": ["Headline 1", "Headline 2", "Headline 3"],
  "descriptions": ["Descripción 1", "Descripción 2"],
  "primary_text": "Texto principal del anuncio",
  "cta_button": "Texto del botón",
  "targeting_suggestions": "Sugerencias de segmentación"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'ad_copy';
                $response['data']['platform'] = $platform;
            }
        }

        return $response;
    }

    /**
     * Genera una descripción de producto SEO-optimizada.
     *
     * Crea descripciones cortas y completas, meta tags y
     * bullets de características para fichas de producto.
     *
     * @param array $context
     *   Contexto con 'product_name', 'features'.
     *   Opcionales: 'benefits', 'target_audience', 'keywords'.
     *
     * @return array
     *   Resultado con 'meta_title', 'meta_description', 'full_description'.
     */
    protected function generateProductDescription(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $features = $context['features'] ?? '';
        $benefits = $context['benefits'] ?? '';
        $keywords = $context['keywords'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear descripción de producto SEO-optimizada.

PRODUCTO: {$product}
CARACTERÍSTICAS: {$features}
BENEFICIOS: {$benefits}
KEYWORDS TARGET: {$keywords}

REQUISITOS:
- Descripción corta (160 chars para meta)
- Descripción completa persuasiva
- Optimizado para SEO
- Bullet points de características

FORMATO DE RESPUESTA (JSON):
{
  "meta_title": "Título SEO (60 chars)",
  "meta_description": "Meta descripción (160 chars)",
  "short_description": "Descripción corta",
  "full_description": "Descripción completa en HTML",
  "feature_bullets": ["Feature 1", "Feature 2"],
  "suggested_tags": ["tag1", "tag2"]
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'product_description';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     *
     * Define el Brand Voice por defecto para marketing digital.
     */
    protected function getDefaultBrandVoice(): string
    {
        return <<<EOT
Eres un experto en marketing digital con amplia experiencia en estrategias de contenido.

ESTILO:
- Profesional pero cercano
- Orientado a resultados
- Persuasivo sin ser agresivo
- Adaptable al tono de cada marca

PRINCIPIOS:
- Beneficios antes que características
- Llamadas a la acción claras
- Optimización para cada plataforma
- Respeto por límites de caracteres
EOT;
    }

}
