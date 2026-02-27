<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Psr\Log\LoggerInterface;

/**
 * Smart Content Writer Agent — Gen 2 migration with model routing.
 *
 * PROPOSITO:
 * Asistente de escritura especializado en la creacion de articulos de blog.
 * Genera desde outlines hasta articulos completos, con optimizacion SEO/GEO
 * automatica (Answer Capsules, meta tags).
 *
 * Gen 2: Extiende SmartBaseAgent con routing inteligente de modelos,
 * tool use, provider fallback, y context window management.
 *
 * ACCIONES DISPONIBLES:
 * - 'generate_outline': Genera estructura detallada del articulo (balanced)
 * - 'expand_section': Expande un encabezado en contenido completo (balanced)
 * - 'optimize_headline': Genera variantes de titulos optimizados (fast)
 * - 'improve_seo': Genera answer_capsule y meta description (fast)
 * - 'full_article': Genera articulo completo desde un tema (premium)
 *
 * TIERS DE MODELO:
 * - fast: optimize_headline, improve_seo (tareas simples, require_speed)
 * - balanced: generate_outline, expand_section (complejidad media)
 * - premium: full_article (maxima calidad requerida, require_quality)
 *
 * ESPECIFICACION: Doc 128 - Platform_AI_Content_Hub_v2
 * PATRON: AGENT-GEN2-PATTERN-001, SMART-AGENT-CONSTRUCTOR-001
 */
class SmartContentWriterAgent extends SmartBaseAgent
{

    /**
     * El gestor de tipos de entidad.
     *
     * Para logging de generaciones en ai_generation_log.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
     */
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL;

    /**
     * El usuario actual.
     *
     * Para asociar generaciones al autor.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface|null
     */
    protected ?AccountProxyInterface $currentUser = NULL;

    /**
     * Servicio de medicion de uso por tenant.
     *
     * @var \Drupal\jaraba_billing\Service\TenantMeteringService|null
     */
    protected ?TenantMeteringService $meteringService = NULL;

    /**
     * Constructs a SmartContentWriterAgent.
     *
     * SMART-AGENT-CONSTRUCTOR-001: 10 standard args + 3 domain-specific.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoria de configuracion.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     * @param \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService $brandVoice
     *   El servicio de Brand Voice.
     * @param \Drupal\jaraba_ai_agents\Service\AIObservabilityService $observability
     *   El servicio de observabilidad.
     * @param \Drupal\jaraba_ai_agents\Service\ModelRouterService $modelRouter
     *   El servicio de routing de modelos.
     * @param \Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder|null $promptBuilder
     *   El constructor de prompts unificado (opcional).
     * @param \Drupal\jaraba_ai_agents\Tool\ToolRegistry|null $toolRegistry
     *   El registro de herramientas (opcional).
     * @param \Drupal\jaraba_ai_agents\Service\ProviderFallbackService|null $providerFallback
     *   El servicio de fallback de proveedores (opcional).
     * @param \Drupal\jaraba_ai_agents\Service\ContextWindowManager|null $contextWindowManager
     *   El gestor de ventana de contexto (opcional).
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entityTypeManager
     *   El gestor de tipos de entidad (opcional).
     * @param \Drupal\Core\Session\AccountProxyInterface|null $currentUser
     *   El usuario actual (opcional).
     * @param \Drupal\jaraba_billing\Service\TenantMeteringService|null $meteringService
     *   El servicio de metering (opcional).
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
        ?EntityTypeManagerInterface $entityTypeManager = NULL,
        ?AccountProxyInterface $currentUser = NULL,
        ?TenantMeteringService $meteringService = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->meteringService = $meteringService;
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'smart_content_writer';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Smart Content Writer Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Asistente de escritura IA con routing inteligente para generacion de articulos de blog, optimizacion SEO y creacion de contenido.';
    }

    /**
     * {@inheritdoc}
     *
     * Define el Brand Voice por defecto para escritura de contenido.
     */
    protected function getDefaultBrandVoice(): string
    {
        return 'Eres un escritor de contenido experto, especializado en crear artículos claros, informativos y optimizados para SEO.';
    }

    /**
     * {@inheritdoc}
     *
     * Define las acciones disponibles con sus tiers de modelo asignados.
     */
    public function getAvailableActions(): array
    {
        return [
            'generate_outline' => [
                'label' => 'Generar estructura',
                'description' => 'Genera un outline detallado para un artículo.',
                'tier' => 'balanced',
            ],
            'expand_section' => [
                'label' => 'Expandir sección',
                'description' => 'Convierte un título de sección en contenido completo.',
                'tier' => 'balanced',
            ],
            'optimize_headline' => [
                'label' => 'Optimizar título',
                'description' => 'Genera variantes de títulos optimizados para SEO y engagement.',
                'tier' => 'fast',
            ],
            'improve_seo' => [
                'label' => 'Mejorar SEO',
                'description' => 'Genera answer_capsule, meta title y meta description.',
                'tier' => 'fast',
            ],
            'full_article' => [
                'label' => 'Artículo completo',
                'description' => 'Genera un artículo completo desde un tema.',
                'tier' => 'premium',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * AGENT-GEN2-PATTERN-001: Enruta la ejecucion al metodo de accion
     * correspondiente. Registra cada generacion en el log de auditoria.
     */
    protected function doExecute(string $action, array $context): array
    {
        $result = match ($action) {
            'generate_outline' => $this->actionGenerateOutline($context),
            'expand_section' => $this->actionExpandSection($context),
            'optimize_headline' => $this->actionOptimizeHeadline($context),
            'improve_seo' => $this->actionImproveSeo($context),
            'full_article' => $this->actionFullArticle($context),
            default => ['success' => FALSE, 'error' => "Accion no soportada: {$action}"],
        };

        // Registrar generacion para auditoria.
        $this->logGeneration($action, $context, $result);

        return $result;
    }

    /**
     * Genera un outline/estructura para un articulo.
     *
     * Crea una estructura detallada con secciones, puntos clave,
     * estimacion de palabras y keywords SEO sugeridas.
     *
     * @param array $context
     *   Contexto con 'topic' (requerido), 'audience', 'length'.
     *
     * @return array
     *   Resultado con 'title', 'hook', 'sections', 'seo_keywords'.
     */
    protected function actionGenerateOutline(array $context): array
    {
        $topic = $context['topic'] ?? '';
        $audience = $context['audience'] ?? 'general';
        $length = $context['length'] ?? 'medium';

        if (empty($topic)) {
            return ['success' => FALSE, 'error' => 'El tema es requerido.'];
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Genera un outline detallado para un artículo de blog sobre: "{$topic}"

Audiencia objetivo: {$audience}
Longitud deseada: {$length} (short=500 palabras, medium=1000, long=2000+)

Responde ÚNICAMENTE en formato JSON válido:
{
  "title": "Título propuesto del artículo",
  "hook": "Frase de apertura que capte la atención",
  "sections": [
    {
      "heading": "Título de la sección",
      "key_points": ["Punto 1", "Punto 2"],
      "estimated_words": 150
    }
  ],
  "conclusion_angle": "Enfoque para la conclusión",
  "seo_keywords": ["keyword1", "keyword2", "keyword3"]
}
PROMPT;

        $result = $this->callAiApi($prompt, ['temperature' => 0.7]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        if (!$parsed) {
            return ['success' => FALSE, 'error' => 'No se pudo parsear la respuesta de IA.'];
        }

        return [
            'success' => TRUE,
            'data' => $parsed,
        ];
    }

    /**
     * Expande una seccion en contenido completo.
     *
     * Toma un encabezado y puntos clave para generar
     * parrafos desarrollados con formato HTML.
     *
     * @param array $context
     *   Contexto con 'heading' (requerido), 'key_points', 'article_context'.
     *
     * @return array
     *   Resultado con 'content_html', 'word_count', 'internal_link_suggestions'.
     */
    protected function actionExpandSection(array $context): array
    {
        $heading = $context['heading'] ?? '';
        $keyPoints = $context['key_points'] ?? [];
        $articleContext = $context['article_context'] ?? '';

        if (empty($heading)) {
            return ['success' => FALSE, 'error' => 'El encabezado de sección es requerido.'];
        }

        $pointsList = implode("\n- ", $keyPoints);

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Expande la siguiente sección de un artículo:

Título de la sección: "{$heading}"

Puntos clave a cubrir:
- {$pointsList}

Contexto del artículo: {$articleContext}

Escribe el contenido completo de esta sección (200-400 palabras).
Usa párrafos cortos, bullets donde sea apropiado, y mantén un tono profesional pero accesible.

Responde en formato JSON:
{
  "content_html": "<p>Contenido con formato HTML básico...</p>",
  "word_count": 250,
  "internal_link_suggestions": ["Tema relacionado 1", "Tema relacionado 2"]
}
PROMPT;

        $result = $this->callAiApi($prompt, ['temperature' => 0.7]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta.'];
    }

    /**
     * Genera variantes de titulares optimizados.
     *
     * Propone 5 titulos con diferentes estilos (pregunta, how-to,
     * listicle, statement, emotional) para testing.
     *
     * @param array $context
     *   Contexto con 'topic' o 'current_title'.
     *
     * @return array
     *   Resultado con 'variants', 'recommended', 'reasoning'.
     */
    protected function actionOptimizeHeadline(array $context): array
    {
        $topic = $context['topic'] ?? '';
        $currentTitle = $context['current_title'] ?? '';

        if (empty($topic) && empty($currentTitle)) {
            return ['success' => FALSE, 'error' => 'Se requiere tema o título actual.'];
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

Genera 5 variantes de títulos optimizados para un artículo sobre:
Tema: "{$topic}"
Título actual (si existe): "{$currentTitle}"

Criterios:
1. SEO-friendly (incluir keyword principal)
2. Menos de 60 caracteres
3. Generar curiosidad
4. Variety: question, how-to, listicle, statement, emotional

Responde en JSON:
{
  "variants": [
    {"title": "Título 1", "type": "question", "char_count": 45},
    {"title": "Título 2", "type": "how-to", "char_count": 52}
  ],
  "recommended": 0,
  "reasoning": "El título X es mejor porque..."
}
PROMPT;

        $result = $this->callAiApi($prompt, ['require_speed' => TRUE, 'temperature' => 0.8]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta.'];
    }

    /**
     * Mejora elementos SEO de un articulo.
     *
     * Genera Answer Capsule (para GEO/AI search), meta title
     * y meta description basandose en el contenido existente.
     *
     * @param array $context
     *   Contexto con 'title' y 'body' (requeridos).
     *
     * @return array
     *   Resultado con 'answer_capsule', 'seo_title', 'seo_description'.
     */
    protected function actionImproveSeo(array $context): array
    {
        $title = $context['title'] ?? '';
        $body = $context['body'] ?? '';

        if (empty($title) || empty($body)) {
            return ['success' => FALSE, 'error' => 'Título y cuerpo son requeridos.'];
        }

        // Truncar body para eficiencia del prompt.
        $bodyTruncated = mb_substr(strip_tags($body), 0, 2000);

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

Analiza el siguiente artículo y genera elementos SEO optimizados:

Título: "{$title}"

Contenido (extracto):
{$bodyTruncated}

Genera:
1. Answer Capsule: Primera oración que responde directamente a la pregunta del usuario (para GEO/AI search)
2. Meta Title: Optimizado para buscadores (max 60 chars)
3. Meta Description: Compelling y con CTA implícito (max 160 chars)

Responde en JSON:
{
  "answer_capsule": "Respuesta directa en 1-2 oraciones...",
  "seo_title": "Título SEO optimizado",
  "seo_description": "Meta description compelling...",
  "primary_keyword": "keyword principal detectada",
  "seo_score": 85,
  "suggestions": ["Sugerencia de mejora 1", "Sugerencia 2"]
}
PROMPT;

        $result = $this->callAiApi($prompt, ['require_speed' => TRUE, 'temperature' => 0.8]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta.'];
    }

    /**
     * Genera un articulo completo desde un tema.
     *
     * Crea todos los componentes: titulo, excerpt, answer_capsule,
     * body HTML estructurado, meta tags y sugerencias de categoria/tags.
     *
     * @param array $context
     *   Contexto con 'topic' (requerido), 'audience', 'length', 'style'.
     *
     * @return array
     *   Resultado completo con todos los campos del articulo.
     */
    protected function actionFullArticle(array $context): array
    {
        $topic = $context['topic'] ?? '';
        $audience = $context['audience'] ?? 'general';
        $length = $context['length'] ?? 'medium';
        $style = $context['style'] ?? 'informative';

        if (empty($topic)) {
            return ['success' => FALSE, 'error' => 'El tema es requerido.'];
        }

        $wordTarget = match ($length) {
            'short' => 500,
            'long' => 2000,
            default => 1000,
        };

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Escribe un artículo completo de blog sobre: "{$topic}"

Especificaciones:
- Audiencia: {$audience}
- Longitud objetivo: ~{$wordTarget} palabras
- Estilo: {$style}

Estructura requerida:
1. Título atractivo con keyword principal
2. Introducción con hook y preview del contenido
3. 3-5 secciones con H2/H3
4. Bullets y listas donde mejore la lectura
5. Conclusión con call-to-action

Responde en JSON:
{
  "title": "Título del artículo",
  "excerpt": "Resumen de 2-3 oraciones para preview",
  "answer_capsule": "Respuesta directa para GEO",
  "body_html": "<h2>...</h2><p>...</p>",
  "word_count": 1050,
  "reading_time": 5,
  "seo_title": "Título SEO",
  "seo_description": "Meta description",
  "suggested_category": "Categoría recomendada",
  "tags": ["tag1", "tag2"]
}
PROMPT;

        $result = $this->callAiApi($prompt, [
            'require_quality' => TRUE,
            'temperature' => 0.7,
        ]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta.'];
    }

    /**
     * Registra la generacion IA en el log de auditoria.
     *
     * Crea una entidad ai_generation_log para tracking de uso,
     * costos y auditoria de contenido generado por IA.
     *
     * @param string $action
     *   La accion ejecutada.
     * @param array $context
     *   El contexto de la solicitud.
     * @param array $result
     *   El resultado de la ejecucion.
     */
    protected function logGeneration(string $action, array $context, array $result): void
    {
        if (!$this->entityTypeManager || !$this->currentUser) {
            return;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('ai_generation_log');
            $log = $storage->create([
                'agent_id' => $this->getAgentId(),
                'action' => $action,
                'context_summary' => json_encode(array_slice($context, 0, 5)),
                'success' => $result['success'] ?? FALSE,
                'user_id' => $this->currentUser->id(),
            ]);
            $log->save();
        } catch (\Exception $e) {
            $this->logger->warning('Error al registrar generación IA: @error', ['@error' => $e->getMessage()]);
        }
    }

}
