<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_page_builder\PageContentInterface;
use Drupal\jaraba_site_builder\Service\HeaderVariantService;
use Drupal\jaraba_site_builder\Service\FooterVariantService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el Canvas Editor visual del Page Builder.
 *
 * PROPÓSITO:
 * Proporciona una experiencia de edición visual lado a lado (side-by-side)
 * con un sidebar de secciones arrastrables y un canvas con preview en vivo.
 *
 * ARQUITECTURA:
 * - Sidebar izquierda: Lista de secciones con SortableJS para drag-and-drop
 * - Canvas derecha: Iframe con preview de la página en tiempo real
 * - Toolbar superior: Viewport toggle (desktop/tablet/mobile) + acciones
 *
 * MULTI-TENANT:
 * Hereda contexto del TenantContextService para:
 * - Filtrar templates disponibles según plan del tenant
 * - Aplicar design tokens (colores, fonts) automáticamente
 * - Aislar preview iframe para evitar cross-tenant leaks
 *
 * LAYOUT FULL-VIEWPORT:
 * Este controller retorna un HtmlResponse directo, bypassing completamente
 * el sistema de page templates de Drupal para evitar header/footer/regiones.
 */
class CanvasEditorController extends ControllerBase
{

    /**
     * Tenant context service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Twig environment.
     *
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * Header variant service.
     *
     * @var \Drupal\jaraba_site_builder\Service\HeaderVariantService
     */
    protected HeaderVariantService $headerVariantService;

    /**
     * Footer variant service.
     *
     * @var \Drupal\jaraba_site_builder\Service\FooterVariantService
     */
    protected FooterVariantService $footerVariantService;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        TenantContextService $tenant_context,
        $twig,
        HeaderVariantService $header_variant_service,
        FooterVariantService $footer_variant_service
    ) {
        // EntityTypeManager se hereda de ControllerBase via entityTypeManager().
        $this->tenantContext = $tenant_context;
        $this->twig = $twig;
        $this->headerVariantService = $header_variant_service;
        $this->footerVariantService = $footer_variant_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('twig'),
            $container->get('jaraba_site_builder.header_variant'),
            $container->get('jaraba_site_builder.footer_variant')
        );
    }

    /**
     * Renderiza el Canvas Editor para una página.
     *
     * Usa render arrays para pasar por el sistema de templates de Drupal,
     * permitiendo que el header/footer/nav del tenant sean visibles.
     * Solo el admin toolbar se oculta via CSS.
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $page_content
     *   La entidad PageContent a editar.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     *
     * @return array
     *   Render array con el Canvas Editor.
     */
    public function editor(PageContentInterface $page_content, Request $request): array
    {

        // Obtener secciones de la página.
        $sections = $this->getSections($page_content);

        // Obtener templates disponibles según el plan del tenant.
        $available_templates = $this->getAvailableTemplates();

        // URL para el iframe preview.
        $preview_url = $page_content->toUrl('canonical', ['absolute' => TRUE])->toString();

        // Información del tenant para design tokens (con fallback).
        try {
            $tenant_info = $this->tenantContext->getCurrentTenant();
        } catch (\Exception $e) {
            $tenant_info = NULL;
        }

        // Canvas v2: Pre-renderizar variantes de header/footer.
        $header_data = $this->headerVariantService->getHeaderDataForCanvas();
        $footer_data = $this->footerVariantService->getFooterDataForCanvas();

        // Extraer URL del logo del tenant para el header del editor.
        $tenant_logo = NULL;
        $tenant_name = NULL;
        if ($tenant_info && method_exists($tenant_info, 'getThemeOverrides')) {
            // Obtener nombre del tenant.
            $tenant_name = $tenant_info->label() ?? 'Mi SaaS';

            // Obtener logo del tenant desde theme_overrides (JSON con "logo" key).
            $theme_overrides = $tenant_info->getThemeOverrides();
            if (!empty($theme_overrides['logo'])) {
                // El logo puede ser una URL relativa o absoluta.
                $logo_path = $theme_overrides['logo'];
                // Si es una ruta relativa, generar URL absoluta.
                if (str_starts_with($logo_path, '/')) {
                    $tenant_logo = \Drupal::request()->getSchemeAndHttpHost() . $logo_path;
                } else {
                    $tenant_logo = $logo_path;
                }
            }
        }

        // Bibliotecas del Editor Canvas (siempre GrapesJS).
        $libraries = [
            'jaraba_page_builder/canvas-editor',
            'ecosistema_jaraba_theme/slide-panel',
            'jaraba_page_builder/grapesjs-canvas',
            'jaraba_page_builder/grapesjs-onboarding',
            'jaraba_page_builder/grapesjs-marketplace',
            'jaraba_page_builder/grapesjs-multipage',
            'jaraba_page_builder/page-config',
        ];

        return [
            '#theme' => 'canvas_editor',
            '#page' => $page_content,
            '#sections' => $sections,
            '#available_templates' => $available_templates,
            '#preview_url' => $preview_url,
            '#tenant_info' => $tenant_info,
            '#tenant_logo' => $tenant_logo,
            '#tenant_name' => $tenant_name,
            '#header_data' => $header_data,
            '#footer_data' => $footer_data,
            '#attached' => [
                'library' => $libraries,
                'drupalSettings' => [
                    'canvasEditor' => [
                        'pageId' => $page_content->id(),
                        'previewUrl' => $preview_url,
                        'sections' => $sections,
                        'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
                        // Header/Footer variants para cambio en vivo.
                        'headerVariants' => $header_data['variants'] ?? [],
                        'headerConfig' => $header_data['config'] ?? [],
                        'footerVariants' => $footer_data['variants'] ?? [],
                        'footerConfig' => $footer_data['config'] ?? [],
                    ],
                    // Configuración de GrapesJS.
                    'jarabaCanvas' => [
                        'editorMode' => 'canvas',
                        'pageId' => $page_content->id(),
                        'tenantId' => $tenant_info?->id(),
                        'vertical' => $tenant_info?->get('vertical') ?? 'generic',
                        'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
                        'headerVariants' => $header_data['variants'] ?? [],
                        'footerVariants' => $footer_data['variants'] ?? [],
                        // Design Tokens del tenant para inyectar en canvas.
                        'designTokens' => $this->getDesignTokens($tenant_info),
                    ],
                ],
            ],
            '#cache' => [
                'contexts' => ['user', 'url'],
                'tags' => ['page_content:' . $page_content->id()],
            ],
        ];
    }

    /**
     * Obtiene los Design Tokens del tenant para inyectar en el canvas.
     *
     * @param object|null $tenant_info
     *   Entidad del tenant o NULL.
     *
     * @return array
     *   Array de tokens CSS (color-primary, color-secondary, etc).
     */
    protected function getDesignTokens(?object $tenant_info): array
    {
        if (!$tenant_info) {
            return [];
        }

        $tokens = [];

        // Verificar si el tenant tiene los campos de personalización.
        if (method_exists($tenant_info, 'hasField')) {
            if ($tenant_info->hasField('color_primary') && !$tenant_info->get('color_primary')->isEmpty()) {
                $tokens['color-primary'] = $tenant_info->get('color_primary')->value;
            }
            if ($tenant_info->hasField('color_secondary') && !$tenant_info->get('color_secondary')->isEmpty()) {
                $tokens['color-secondary'] = $tenant_info->get('color_secondary')->value;
            }
            if ($tenant_info->hasField('font_family') && !$tenant_info->get('font_family')->isEmpty()) {
                $tokens['font-family'] = $tenant_info->get('font_family')->value;
            }
        }

        return $tokens;
    }

    /**
     * Título dinámico para la página del editor.
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $page_content
     *   La página.
     *
     * @return string
     *   El título.
     */
    public function editorTitle(PageContentInterface $page_content): string
    {
        return (string) $this->t('Editar: @title', ['@title' => $page_content->label()]);
    }

    /**
     * Obtiene las secciones de una página.
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $page_content
     *   La página.
     *
     * @return array
     *   Array de secciones con uuid, template_id, content, weight.
     */
    protected function getSections(PageContentInterface $page_content): array
    {
        $sections = [];

        // Las secciones están en el campo 'sections' como JSON.
        $sections_field = $page_content->get('sections');
        if ($sections_field && !$sections_field->isEmpty()) {
            $sections_data = json_decode($sections_field->value, TRUE) ?: [];
            foreach ($sections_data as $index => $section) {
                $sections[] = [
                    'uuid' => $section['uuid'] ?? \Drupal::service('uuid')->generate(),
                    'template_id' => $section['template_id'] ?? '',
                    'content' => $section['content'] ?? [],
                    'weight' => $section['weight'] ?? $index,
                    'visible' => $section['visible'] ?? TRUE,
                ];
            }
        }

        // Ordenar por weight.
        usort($sections, fn($a, $b) => $a['weight'] <=> $b['weight']);

        return $sections;
    }

    /**
     * Obtiene templates disponibles según el plan del tenant.
     *
     * @return array
     *   Array de templates agrupados por categoría.
     */
    protected function getAvailableTemplates(): array
    {
        $template_storage = $this->entityTypeManager()->getStorage('page_template');

        // Cargar todos los templates activos.
        $templates = $template_storage->loadByProperties(['status' => TRUE]);

        // Determinar nivel de plan del tenant para filtrar templates premium.
        $tenantPlan = NULL;
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            if ($tenant) {
                $plan = $tenant->getSubscriptionPlan();
                $tenantPlan = $plan ? $plan->get('machine_name')->value : NULL;
            }
        } catch (\Exception $e) {
            // Sin contexto de tenant, solo mostrar templates gratuitos.
        }
        $premiumPlans = ['professional', 'enterprise', 'unlimited'];
        $hasPremiumAccess = $tenantPlan && in_array($tenantPlan, $premiumPlans, TRUE);

        $grouped = [];
        foreach ($templates as $template) {
            $category = $template->get('category') ?? 'general';
            $is_premium = $template->get('is_premium') ?? FALSE;

            // Filtrar templates premium según plan del tenant.
            if ($is_premium && !$hasPremiumAccess) {
                continue;
            }

            $grouped[$category][] = [
                'id' => $template->id(),
                'label' => $template->label(),
                'description' => $template->get('description') ?? '',
                'preview_image' => $template->get('preview_image') ?? '',
                'is_premium' => $is_premium,
                'icon' => $this->getCategoryIcon($category),
            ];
        }

        return $grouped;
    }

    /**
     * Obtiene el icono para una categoría.
     *
     * @param string $category
     *   La categoría.
     *
     * @return string
     *   Nombre del icono Lucide.
     */
    protected function getCategoryIcon(string $category): string
    {
        $icons = [
            'hero' => 'layout-template',
            'features' => 'grid-3x3',
            'stats' => 'bar-chart-3',
            'testimonials' => 'quote',
            'pricing' => 'credit-card',
            'cta' => 'megaphone',
            'content' => 'file-text',
            'media' => 'image',
            'forms' => 'send',
            'navigation' => 'menu',
            'footer' => 'layout-panel-bottom',
            'premium' => 'sparkles',
        ];

        return $icons[$category] ?? 'layout-grid';
    }

}
