<?php

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_page_builder\PageTemplateInterface;
use Drupal\jaraba_page_builder\Service\TenantResolverService;

/**
 * Controlador para el selector de plantillas.
 *
 * PROPÓSITO:
 * Muestra la galería de plantillas disponibles según el plan del tenant,
 * permitiendo al usuario elegir una plantilla para crear una nueva página.
 */
class TemplatePickerController extends ControllerBase
{

    /**
     * El entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * El servicio de resolución de tenant.
     *
     * @var \Drupal\jaraba_page_builder\Service\TenantResolverService
     */
    protected TenantResolverService $tenantResolver;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user,
        TenantResolverService $tenant_resolver
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->tenantResolver = $tenant_resolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('current_user'),
            $container->get('jaraba_page_builder.tenant_resolver')
        );
    }

    /**
     * Lista las plantillas disponibles para el usuario.
     *
     * @return array
     *   Render array con la galería de plantillas.
     */
    public function listTemplates(): array
    {
        $templates = $this->entityTypeManager
            ->getStorage('page_template')
            ->loadMultiple();

        // Agrupar por categoría.
        $categories = [];
        foreach ($templates as $template) {
            /** @var \Drupal\jaraba_page_builder\PageTemplateInterface $template */
            $category = $template->getCategory();

            // Verificar si el usuario tiene acceso a esta plantilla.
            $has_access = $this->userHasAccessToTemplate($template);

            $categories[$category][] = [
                'template' => $template,
                'has_access' => $has_access,
            ];
        }

        // Etiquetas de categorías traducibles para el template.
        // @see docs/00_DIRECTRICES_PROYECTO.md (Internacionalización)
        $category_labels = [
            'hero' => $this->t('Hero'),
            'content' => $this->t('Content'),
            'cta' => $this->t('Call to Action'),
            'features' => $this->t('Features'),
            'testimonials' => $this->t('Testimonials'),
            'pricing' => $this->t('Pricing'),
            'contact' => $this->t('Contact'),
            'gallery' => $this->t('Gallery'),
            'commerce' => $this->t('Commerce'),
            'social' => $this->t('Social'),
            'advanced' => $this->t('Advanced'),
            'premium' => $this->t('Premium'),
            'layout' => $this->t('Layout'),
            'forms' => $this->t('Forms'),
            'conversion' => $this->t('Conversion'),
            'events' => $this->t('Events'),
            'media' => $this->t('Media'),
            'trust' => $this->t('Trust'),
            'maps' => $this->t('Maps'),
            'social_proof' => $this->t('Social Proof'),
            'stats' => $this->t('Statistics'),
            'team' => $this->t('Team'),
            'timeline' => $this->t('Timeline'),
            // Categorías verticales.
            'agroconecta' => $this->t('AgroConecta'),
            'comercioconecta' => $this->t('ComercioConecta'),
            'serviciosconecta' => $this->t('ServiciosConecta'),
            'empleabilidad' => $this->t('Empleabilidad'),
            'emprendimiento' => $this->t('Emprendimiento'),
        ];

        $build = [
            '#theme' => 'page_builder_template_picker',
            '#categories' => $categories,
            '#category_labels' => $category_labels,
            '#attached' => [
                'library' => [
                    'jaraba_page_builder/template-picker',
                    'ecosistema_jaraba_theme/content-hub',
                ],
            ],
            '#cache' => [
                'contexts' => ['user', 'user.permissions'],
                'tags' => ['config:jaraba_page_builder.template_list'],
            ],
        ];

        return $build;
    }

    /**
     * Lista las plantillas en formato AJAX para slide-panel.
     *
     * Este método devuelve SOLO el fragmento HTML del template picker,
     * sin el wrapper de página completa. Esto permite cargar el contenido
     * directamente en un slide-panel sin duplicar headers/sidebars.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTML con solo el contenido del picker.
     *
     * @see .agent/workflows/slide-panel-modales.md
     */
    public function listTemplatesAjax(): \Symfony\Component\HttpFoundation\Response
    {
        // Reutilizar la lógica existente.
        $build = $this->listTemplates();

        // Renderizar a HTML.
        $renderer = \Drupal::service('renderer');
        $html = $renderer->renderRoot($build);

        // Devolver solo el fragmento, no página completa.
        return new \Symfony\Component\HttpFoundation\Response(
            $html,
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Muestra el preview premium de una plantilla.
     *
     * CARACTERÍSTICAS PREMIUM:
     * - Live Preview con iframe de plantilla renderizada
     * - Viewport Switcher (Desktop/Tablet/Mobile)
     * - Métricas de uso de la plantilla
     * - Glassmorphism frame con partículas animadas
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $page_template
     *   La plantilla a previsualizar.
     *
     * @return array
     *   Render array con el preview premium.
     */
    public function previewTemplate(PageTemplateInterface $page_template): array
    {
        // Obtener métricas de uso de la plantilla.
        $usage_count = $this->getTemplateUsageCount($page_template->id());

        // Generar URL para el iframe de preview.
        $preview_iframe_url = \Drupal\Core\Url::fromRoute(
            'jaraba_page_builder.template_iframe_preview',
            ['page_template' => $page_template->id()]
        )->toString();

        return [
            '#theme' => 'page_template_preview',
            '#template' => $page_template,
            '#preview_data' => $this->getPreviewData($page_template),
            '#usage_count' => $usage_count,
            '#avg_engagement' => $usage_count > 0 ? $this->calculateAvgEngagement($page_template->id()) : NULL,
            '#preview_iframe_url' => $preview_iframe_url,
            '#attached' => [
                'library' => ['jaraba_page_builder/preview'],
            ],
        ];
    }

    /**
     * Renderiza una plantilla de forma aislada para iframe.
     *
     * Este endpoint devuelve SOLO el contenido renderizado del template
     * sin cabeceras, menús ni admin toolbar. Ideal para previews en iframe.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $page_template
     *   La plantilla a renderizar.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   HTML del template renderizado de forma aislada.
     */
    public function renderIframe(PageTemplateInterface $page_template): \Symfony\Component\HttpFoundation\Response
    {
        // Leer viewport de la request (desktop: 1440, tablet: 768, mobile: 375).
        $request = \Drupal::request();
        $viewport = (int) $request->query->get('viewport', 1440);

        // Obtener datos de ejemplo para el preview.
        $preview_data = $this->getPreviewData($page_template);

        // Renderizar el template Twig del bloque directamente.
        $content = $this->renderBlockTemplate($page_template, $preview_data);

        // Construir HTML completo para el iframe con viewport específico.
        $html = $this->buildIframeHtml($page_template, $content, $viewport);

        return new \Symfony\Component\HttpFoundation\Response(
            $html,
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Renderiza el template Twig del bloque.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla.
     * @param array $preview_data
     *   Datos de ejemplo para el preview.
     *
     * @return string
     *   HTML renderizado del bloque.
     */
    protected function renderBlockTemplate(PageTemplateInterface $template, array $preview_data): string
    {
        // AUTO-DESCUBRIMIENTO: Obtener path del template directamente de la entidad.
        $template_path = $this->getTemplateBlockPath($template);

        if (!$template_path) {
            // Fallback: mostrar placeholder si no hay template.
            return '<div class="preview-placeholder" style="padding: 4rem; text-align: center; color: #666;">
                <p>Vista previa no disponible para este template.</p>
            </div>';
        }

        // Renderizar con Twig.
        try {
            /** @var \Twig\Environment $twig */
            $twig = \Drupal::service('twig');
            // FIX C3/C4: Pasar datos como 'content' (genéricos) Y planos (verticales).
            $twigVars = array_merge($preview_data, ['content' => $preview_data]);
            return $twig->render($template_path, $twigVars);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->error('Error renderizando iframe preview: @message', [
                '@message' => $e->getMessage(),
            ]);
            return '<div class="preview-error" style="padding: 2rem; text-align: center; color: #c00;">
                <p>Error al renderizar la vista previa.</p>
            </div>';
        }
    }

    /**
     * Obtiene el path del template Twig para un bloque.
     *
     * AUTO-DESCUBRIMIENTO: Lee directamente de la entidad PageTemplate
     * sin necesidad de mantener un mapeo manual.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La entidad plantilla.
     *
     * @return string|null
     *   Path al template Twig o null si no existe.
     */
    protected function getTemplateBlockPath(PageTemplateInterface $template): ?string
    {
        // Obtener la ruta del template directamente de la configuración YAML.
        $twig_template = $template->getTwigTemplate();

        if (empty($twig_template)) {
            return NULL;
        }

        // Verificar que el template existe en el sistema de archivos.
        try {
            /** @var \Twig\Environment $twig */
            $twig = \Drupal::service('twig');
            $loader = $twig->getLoader();
            if ($loader->exists($twig_template)) {
                return $twig_template;
            }
        } catch (\Exception $e) {
            // El template no existe o hay error.
            \Drupal::logger('jaraba_page_builder')->warning('Template not found: @template', [
                '@template' => $twig_template,
            ]);
        }

        return NULL;
    }

    /**
     * Construye el HTML completo para el iframe con viewport específico.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla.
     * @param string $content
     *   Contenido HTML renderizado del bloque.
     * @param int $viewport
     *   Ancho del viewport (375, 768, 1440).
     *
     * @return string
     *   HTML completo.
     */
    protected function buildIframeHtml(PageTemplateInterface $template, string $content, int $viewport = 1440): string
    {
        // Obtener URL del CSS del tema.
        $theme_path = \Drupal::theme()->getActiveTheme()->getPath();
        $css_url = '/' . $theme_path . '/css/ecosistema-jaraba-theme.css';

        // Obtener URL del CSS del módulo ecosistema_jaraba_core (estilos premium).
        $core_module_path = \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');
        $core_css_url = '/' . $core_module_path . '/css/ecosistema-jaraba-core.css';

        // FIX 4: Obtener Design Tokens del tenant para inyectar en el iframe.
        $design_tokens_css = $this->buildDesignTokensCss();

        // FIX A5: CSS del Page Builder necesarios para que los bloques se rendericen
        // correctamente en el iframe de preview.
        $pb_module_path = \Drupal::service('extension.list.module')->getPath('jaraba_page_builder');
        $pb_css_url = '/' . $pb_module_path . '/css/jaraba-page-builder.css';
        $pb_core_css_url = '/' . $pb_module_path . '/css/page-builder-core.css';
        $pb_navigation_css_url = '/' . $pb_module_path . '/css/navigation.css';
        $pb_product_card_css_url = '/' . $pb_module_path . '/css/product-card.css';
        $pb_social_links_css_url = '/' . $pb_module_path . '/css/social-links.css';
        $pb_contact_form_css_url = '/' . $pb_module_path . '/css/contact-form.css';
        $pb_aceternity_css_url = '/' . $pb_module_path . '/css/premium/aceternity.css';
        $pb_magic_ui_css_url = '/' . $pb_module_path . '/css/premium/magic-ui.css';

        $label = $template->label();

        // Estilos base siempre aplicados
        $baseStyles = <<<CSS
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: var(--ej-font-sans, 'Inter', system-ui, sans-serif);
            background: #fff;
            overflow-x: hidden;
            width: 100%;
        }
        /* Override del container del tema */
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            margin: 0 auto !important;
        }
        .jaraba-hero--fullscreen {
            min-height: auto !important;
            height: auto !important;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .jaraba-hero--fullscreen .jaraba-hero__content {
            min-height: auto !important;
            text-align: center;
            width: 100%;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
CSS;

        // Estilos específicos para móvil (viewport <= 480)
        if ($viewport <= 480) {
            $additionalStyles = <<<CSS
        .jaraba-hero--fullscreen { padding: 1.5rem 1rem; }
        .jaraba-hero--fullscreen .jaraba-hero__content { padding: 1rem 0.5rem; }
        .jaraba-hero__title {
            font-size: 1.25rem !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.2 !important;
            margin: 0 0 0.5rem 0;
        }
        .jaraba-hero__subtitle {
            font-size: 0.8rem !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            margin: 0 0 0.75rem 0;
        }
        .jaraba-hero__eyebrow {
            font-size: 0.6rem !important;
            margin-bottom: 0.5rem;
        }
        .jaraba-hero__actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 0.5rem !important;
            width: 100%;
        }
        .jaraba-hero__actions a {
            padding: 0.5rem 1rem !important;
            font-size: 0.75rem !important;
            display: inline-block;
            text-decoration: none;
            border-radius: 4px;
            width: auto;
        }
CSS;
        }
        // Estilos para tablet (480 < viewport <= 900)
        elseif ($viewport <= 900) {
            $additionalStyles = <<<CSS
        .jaraba-hero--fullscreen { padding: 2rem 1.5rem; }
        .jaraba-hero--fullscreen .jaraba-hero__content { padding: 1.5rem 1rem; }
        .jaraba-hero__title {
            font-size: 1.5rem !important;
            line-height: 1.2 !important;
            margin: 0 0 0.5rem 0;
        }
        .jaraba-hero__subtitle {
            font-size: 0.9rem !important;
            margin: 0 0 1rem 0;
        }
        .jaraba-hero__eyebrow { font-size: 0.65rem !important; }
        .jaraba-hero__actions {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            gap: 0.75rem !important;
        }
        .jaraba-hero__actions a {
            padding: 0.6rem 1.25rem !important;
            font-size: 0.85rem !important;
        }
CSS;
        }
        // Estilos para desktop (viewport > 900)
        else {
            $additionalStyles = <<<CSS
        .jaraba-hero--fullscreen { padding: 3rem 2rem; }
        .jaraba-hero--fullscreen .jaraba-hero__content { padding: 2rem 1.5rem; }
        .jaraba-hero__title {
            font-size: 2rem !important;
            line-height: 1.15 !important;
            margin: 0 0 0.75rem 0;
        }
        .jaraba-hero__subtitle {
            font-size: 1rem !important;
            margin: 0 0 1.25rem 0;
        }
        .jaraba-hero__eyebrow { font-size: 0.7rem !important; }
        .jaraba-hero__actions {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            gap: 1rem !important;
        }
        .jaraba-hero__actions a {
            padding: 0.75rem 1.5rem !important;
            font-size: 0.9rem !important;
        }
CSS;
        }

        // Obtener URLs de scripts para interactividad.
        $module_path = \Drupal::service('extension.list.module')->getPath('jaraba_page_builder');
        $premium_js_url = '/' . $module_path . '/js/premium-blocks.js';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width={$viewport}, initial-scale=1.0">
    <title>{$label} - Preview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{$css_url}">
    <link rel="stylesheet" href="{$core_css_url}">
    <link rel="stylesheet" href="{$pb_css_url}">
    <link rel="stylesheet" href="{$pb_core_css_url}">
    <link rel="stylesheet" href="{$pb_navigation_css_url}">
    <link rel="stylesheet" href="{$pb_product_card_css_url}">
    <link rel="stylesheet" href="{$pb_social_links_css_url}">
    <link rel="stylesheet" href="{$pb_contact_form_css_url}">
    <link rel="stylesheet" href="{$pb_aceternity_css_url}">
    <link rel="stylesheet" href="{$pb_magic_ui_css_url}">
    <style>
        {$design_tokens_css}
        {$baseStyles}
        {$additionalStyles}
    </style>
</head>
<body>
    {$content}
    <script>
        // Minimal Drupal mock for premium-blocks.js
        window.Drupal = window.Drupal || {};
        Drupal.behaviors = Drupal.behaviors || {};
        Drupal.t = function(str) { return str; };
        // Minimal once() implementation
        window.once = function(id, selector, context) {
            context = context || document;
            var elements = context.querySelectorAll(selector);
            var result = [];
            elements.forEach(function(el) {
                if (!el.dataset['once_' + id]) {
                    el.dataset['once_' + id] = true;
                    result.push(el);
                }
            });
            return result;
        };
    </script>
    <script src="{$premium_js_url}"></script>
    <script>
        // Trigger Drupal behaviors on page load
        document.addEventListener('DOMContentLoaded', function() {
            Object.keys(Drupal.behaviors).forEach(function(key) {
                if (typeof Drupal.behaviors[key].attach === 'function') {
                    Drupal.behaviors[key].attach(document);
                }
            });
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Obtiene el número de páginas que usan esta plantilla.
     *
     * @param string $template_id
     *   El ID de la plantilla.
     *
     * @return int
     *   Número de páginas que usan la plantilla.
     */
    protected function getTemplateUsageCount(string $template_id): int
    {
        try {
            return (int) $this->entityTypeManager
                ->getStorage('page_content')
                ->getQuery()
                ->condition('template_id', $template_id)
                ->accessCheck(FALSE)
                ->count()
                ->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calcula el engagement promedio de las páginas que usan una plantilla.
     *
     * Se basa en el campo 'engagement_score' de las entidades page_content
     * que utilizan la plantilla. Si no hay datos, devuelve NULL.
     *
     * @param string $template_id
     *   El ID de la plantilla.
     *
     * @return int|null
     *   Porcentaje promedio de engagement o NULL si no hay datos.
     */
    protected function calculateAvgEngagement(string $template_id): ?int
    {
        try {
            $storage = $this->entityTypeManager->getStorage('page_content');
            $pageIds = $storage->getQuery()
                ->condition('template_id', $template_id)
                ->accessCheck(FALSE)
                ->execute();

            if (empty($pageIds)) {
                return NULL;
            }

            $pages = $storage->loadMultiple($pageIds);
            $scores = [];
            foreach ($pages as $page) {
                if ($page->hasField('engagement_score') && !$page->get('engagement_score')->isEmpty()) {
                    $scores[] = (int) $page->get('engagement_score')->value;
                }
            }

            if (empty($scores)) {
                return NULL;
            }

            return (int) round(array_sum($scores) / count($scores));
        }
        catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Título para la página de preview.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $page_template
     *   La plantilla.
     *
     * @return string
     *   El título.
     */
    public function previewTitle(PageTemplateInterface $page_template): string
    {
        return $this->t('Preview: @name', ['@name' => $page_template->label()]);
    }

    /**
     * Verifica si el usuario tiene acceso a una plantilla.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla.
     *
     * @return bool
     *   TRUE si tiene acceso.
     */
    protected function userHasAccessToTemplate(PageTemplateInterface $template): bool
    {
        // Admin tiene acceso a todo.
        if ($this->currentUser->hasPermission('administer page builder')) {
            return TRUE;
        }

        // Verificar si es premium y tiene permiso.
        if ($template->isPremium() && !$this->currentUser->hasPermission('use premium templates')) {
            return FALSE;
        }

        // Verificar plan del tenant del usuario usando TenantResolverService.
        return $this->tenantResolver->hasAccessToTemplate($template);
    }

    /**
     * Obtiene datos de ejemplo para el preview.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla.
     *
     * @return array
     *   Datos de ejemplo.
     */
    protected function getPreviewData(PageTemplateInterface $template): array
    {
        // Prioridad 1: Datos curados del YAML (preview_data).
        // Estos datos coinciden exactamente con las miniaturas PNG.
        $preview_data = $template->getPreviewData();
        if (!empty($preview_data)) {
            return $preview_data;
        }

        // Prioridad 2: Datos hardcodeados legacy (retrocompatibilidad).
        $hardcoded = $this->getHardcodedPreviewData($template->id());
        if (!empty($hardcoded)) {
            return $hardcoded;
        }

        // Fallback: generar datos basados en el schema.
        $schema = $template->getFieldsSchema();
        $data = [];

        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                $data[$key] = $this->generateSampleValue($property, $key);
            }
        }

        return $data;
    }

    /**
     * Obtiene datos hardcodeados para preview que coinciden con miniaturas.
     *
     * @param string $template_id
     *   ID del template.
     *
     * @return array
     *   Datos de ejemplo o array vacío si no hay datos específicos.
     */
    protected function getHardcodedPreviewData(string $template_id): array
    {
        // Datos que coinciden con las miniaturas del template picker.
        $preview_data = [
            'hero_fullscreen' => [
                'eyebrow' => 'VISTA TRAVEL',
                'title' => 'Explore the World. Embrace the Journey.',
                'subtitle' => 'Discover breathtaking destinations and create unforgettable memories.',
                'cta_primary_text' => 'Destinations',
                'cta_primary_url' => '#',
                'cta_secondary_text' => 'About',
                'cta_secondary_url' => '#',
                'background_image' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1600&q=80',
                'overlay_opacity' => 50,
            ],
            'features_grid' => [
                'section_title' => '¿Por qué elegirnos?',
                'section_subtitle' => 'Descubre las ventajas que nos hacen diferentes',
                'columns' => 3,
                'features' => [
                    [
                        'icon' => 'rocket',
                        'title' => 'Rápido y Eficiente',
                        'description' => 'Implementación veloz sin sacrificar calidad.',
                        'badge' => 'Nuevo',
                        'badge_variant' => 'primary',
                    ],
                    [
                        'icon' => 'shield-check',
                        'title' => 'Seguro',
                        'description' => 'Protección de datos de nivel empresarial.',
                        'badge' => '',
                        'badge_variant' => 'primary',
                    ],
                    [
                        'icon' => 'chart-line',
                        'title' => 'Escalable',
                        'description' => 'Crece con tu negocio sin límites.',
                        'badge' => 'Popular',
                        'badge_variant' => 'success',
                    ],
                ],
            ],
            // Añadir más templates según se vayan creando...
        ];

        return $preview_data[$template_id] ?? [];
    }

    /**
     * Genera un valor de ejemplo para un campo del schema.
     *
     * MEJORADO: Genera datos más realistas y visualmente atractivos
     * basándose en el nombre del campo y el tipo.
     *
     * @param array $property
     *   Definición del campo.
     * @param string $field_name
     *   Nombre del campo (para inferir contenido apropiado).
     *
     * @return mixed
     *   Valor de ejemplo.
     */
    protected function generateSampleValue(array $property, string $field_name = ''): mixed
    {
        $type = $property['type'] ?? 'string';

        // Primero revisar si hay default o placeholder.
        if (isset($property['default']) && !empty($property['default'])) {
            return $property['default'];
        }
        if (isset($property['ui:placeholder']) && !empty($property['ui:placeholder'])) {
            return $property['ui:placeholder'];
        }

        // Generar según el tipo y nombre del campo.
        switch ($type) {
            case 'string':
                return $this->generateSampleString($field_name, $property);
            case 'number':
            case 'integer':
                return $property['default'] ?? 100;
            case 'boolean':
                return TRUE;
            case 'array':
                return $this->generateSampleArray($property, $field_name);
            case 'object':
                return [];
            default:
                return '';
        }
    }

    /**
     * Genera texto de ejemplo basado en el nombre del campo.
     *
     * @param string $field_name
     *   Nombre del campo.
     * @param array $property
     *   Definición del campo.
     *
     * @return string
     *   Texto de ejemplo apropiado.
     */
    protected function generateSampleString(string $field_name, array $property): string
    {
        $name_lower = strtolower($field_name);

        // Mapeo de patrones de nombre a texto de ejemplo.
        $patterns = [
            'title' => 'Título de Ejemplo Atractivo',
            'subtitle' => 'Un subtítulo que complementa el mensaje principal',
            'description' => 'Descripción detallada que explica las características y beneficios de lo que se presenta.',
            'eyebrow' => 'DESTACADO',
            'heading' => 'Encabezado Principal',
            'text' => 'Texto de contenido para mostrar información relevante.',
            'button' => 'Acción',
            'cta' => 'Descubre Más',
            'label' => 'Etiqueta',
            'name' => 'Nombre de Ejemplo',
            'url' => '#',
            'link' => '#',
            'image' => 'https://images.unsplash.com/photo-1557683316-973673baf926?w=800&q=80',
            'background' => 'https://images.unsplash.com/photo-1557683316-973673baf926?w=1600&q=80',
            'icon' => 'star',
            'badge' => 'Nuevo',
            'quote' => 'Una cita inspiradora que motiva a la acción.',
            'author' => 'María García',
            'role' => 'Directora de Innovación',
            'company' => 'Empresa Ejemplo',
        ];

        foreach ($patterns as $pattern => $sample) {
            if (str_contains($name_lower, $pattern)) {
                return $sample;
            }
        }

        // Fallback genérico.
        return 'Contenido de ejemplo';
    }

    /**
     * Genera array de ejemplo para campos tipo array.
     *
     * @param array $property
     *   Definición del campo.
     * @param string $field_name
     *   Nombre del campo.
     *
     * @return array
     *   Array con datos de ejemplo.
     */
    protected function generateSampleArray(array $property, string $field_name): array
    {
        // Si tiene items definidos, generar 3 elementos de ejemplo.
        if (!isset($property['items'])) {
            return [];
        }

        $items = [];
        $item_schema = $property['items'];
        $count = 3; // Generar 3 elementos por defecto.

        for ($i = 0; $i < $count; $i++) {
            if ($item_schema['type'] === 'object' && isset($item_schema['properties'])) {
                $item = [];
                foreach ($item_schema['properties'] as $prop_name => $prop_def) {
                    $item[$prop_name] = $this->generateSampleValue($prop_def, $prop_name);
                }
                $items[] = $item;
            } elseif ($item_schema['type'] === 'string') {
                $items[] = "Elemento " . ($i + 1);
            }
        }

        return $items;
    }

    /**
     * Builds a CSS :root block with the tenant's design tokens.
     *
     * Mirrors the logic in CanvasEditorController::getDesignTokens() so the
     * Template Picker iframe renders with the same tenant-specific colors and
     * fonts as the GrapesJS canvas.
     *
     * @return string
     *   CSS string (may be empty if no tenant or no tokens).
     */
    protected function buildDesignTokensCss(): string
    {
        $tenant = $this->tenantResolver->getCurrentTenant();

        if (!$tenant) {
            return '';
        }

        $tokens = [];

        if (method_exists($tenant, 'hasField')) {
            if ($tenant->hasField('color_primary') && !$tenant->get('color_primary')->isEmpty()) {
                $tokens['color-primary'] = $tenant->get('color_primary')->value;
            }
            if ($tenant->hasField('color_secondary') && !$tenant->get('color_secondary')->isEmpty()) {
                $tokens['color-secondary'] = $tenant->get('color_secondary')->value;
            }
            if ($tenant->hasField('font_family') && !$tenant->get('font_family')->isEmpty()) {
                $tokens['font-family'] = $tenant->get('font_family')->value;
            }
        }

        if (empty($tokens)) {
            return '';
        }

        $vars = '';
        foreach ($tokens as $key => $value) {
            $vars .= "  --{$key}: {$value};\n";
        }

        return ":root {\n{$vars}}\n";
    }

}
