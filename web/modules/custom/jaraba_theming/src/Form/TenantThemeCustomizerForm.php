<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\UnifiedThemeResolverService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Drupal\jaraba_theming\Service\ThemeTokenService;
use Drupal\jaraba_theming\Service\IndustryPresetService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form para personalizar el tema del tenant.
 *
 * Implementa una experiencia de usuario con pestañas verticales y
 * visual pickers similar a la configuración del tema en admin.
 */
class TenantThemeCustomizerForm extends FormBase
{

    use TenantFormHeroPremiumTrait;

    /**
     * El servicio de tokens.
     */
    protected ThemeTokenService $tokenService;

    /**
     * El servicio de Industry Presets.
     */
    protected IndustryPresetService $presetService;

    /**
     * Servicio de contexto de tenant.
     */
    protected ?TenantContextService $tenantContext;

    /**
     * Resolucion unificada de tema (hostname-aware).
     */
    protected ?UnifiedThemeResolverService $themeResolver;

    /**
     * Constructor.
     */
    public function __construct(
        ThemeTokenService $token_service,
        IndustryPresetService $preset_service,
        ?TenantContextService $tenant_context = NULL,
        ?UnifiedThemeResolverService $theme_resolver = NULL,
    ) {
        $this->tokenService = $token_service;
        $this->presetService = $preset_service;
        $this->tenantContext = $tenant_context;
        $this->themeResolver = $theme_resolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_theming.token_service'),
            $container->get('jaraba_theming.industry_preset_service'),
            $container->has('ecosistema_jaraba_core.tenant_context')
            ? $container->get('ecosistema_jaraba_core.tenant_context')
            : NULL,
            $container->has('ecosistema_jaraba_core.unified_theme_resolver')
            ? $container->get('ecosistema_jaraba_core.unified_theme_resolver')
            : NULL,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'tenant_theme_customizer_form';
    }

    /**
     * Genera un visual picker con tarjetas de imagen.
     *
     * @param string $pickerType
     *   Tipo de picker: 'header', 'hero', 'footer'.
     * @param array $options
     *   Array de opciones [value => [title, description]].
     * @param string $default
     *   Valor por defecto.
     *
     * @return array
     *   Render array del picker.
     */
    protected function buildVisualPicker(string $pickerType, array $options, string $default): array
    {
        $themePath = '/' . \Drupal::service('extension.list.theme')->getPath('ecosistema_jaraba_theme');
        $pickerPath = $themePath . '/images/pickers/' . $pickerType;

        $items = [];
        foreach ($options as $value => $info) {
            $imagePath = $pickerPath . '/' . $value . '.png';
            $items[$value] = '<div class="jaraba-picker-card">
                <div class="jaraba-picker-thumb">
                    <img src="' . $imagePath . '" alt="' . $info['title'] . '" />
                </div>
                <div class="jaraba-picker-text">
                    <span class="jaraba-picker-title">' . $info['title'] . '</span>
                    <span class="jaraba-picker-desc">' . $info['description'] . '</span>
                </div>
            </div>';
        }

        return [
            '#type' => 'radios',
            '#options' => $items,
            '#default_value' => $default,
            '#attributes' => ['class' => ['jaraba-visual-picker', 'jaraba-visual-picker--' . $pickerType]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    /**
     * Resuelve el tenant_id correcto priorizando hostname sobre usuario.
     *
     * THEMING-UNIFY-001: Cuando el admin gestiona multiples tenants,
     * TenantContextService devuelve el primer match por admin_user.
     * UnifiedThemeResolverService resuelve correctamente por hostname.
     */
    protected function resolveCurrentTenantId(): ?int {
        // Prioridad 1: hostname (via UnifiedThemeResolverService).
        if ($this->themeResolver !== NULL) {
            $context = $this->themeResolver->resolveForCurrentRequest();
            if ($context['tenant_id'] !== NULL) {
                return $context['tenant_id'];
            }
        }
        // Prioridad 2: usuario autenticado (fallback legacy).
        return $this->tenantContext?->getCurrentTenantId();
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->tokenService->getActiveConfig($this->resolveCurrentTenantId());
        $themePath = \Drupal::service('extension.list.theme')->getPath('ecosistema_jaraba_theme');

        // Attach libraries
        $form['#attached']['library'][] = 'jaraba_theming/visual_customizer';
        $form['#attached']['library'][] = 'ecosistema_jaraba_core/global';
        $form['#attributes']['class'][] = 'jaraba-theme-customizer';
        $form['#attributes']['class'][] = 'jaraba-settings';

        // Premium Hero Header via shared trait — ROUTE-LANGPREFIX-001 compliant.
        $this->attachTenantFormHero(
            $form,
            'palette',
            (string) $this->t('Diseno Visual'),
            (string) $this->t('Configura la apariencia visual de tu plataforma. Los cambios se aplican al guardar.'),
        );

        // Vertical tabs container
        $form['settings'] = [
            '#type' => 'vertical_tabs',
            '#default_tab' => 'edit-identity',
        ];

        // === TAB 1: AJUSTES PREDEFINIDOS POR SECTOR ===
        // PRESET-PICKER-001: Grid 2 columnas, aspect-ratio 4/3, lightbox,
        // transicion animada, opt-out span-2, sticky bar, imagenes optimizadas.
        $form['industry_preset'] = [
            '#type' => 'details',
            '#title' => $this->t('Ajuste Predefinido por Sector'),
            '#group' => 'settings',
            '#weight' => -10,
            '#attributes' => ['class' => ['customizer-tab', 'customizer-tab--presets']],
        ];

        $form['industry_preset']['intro'] = [
            '#markup' => '<p class="preset-picker-intro">' . $this->t('Selecciona un ajuste predefinido disenado por expertos para tu sector. Se aplicara una combinacion optimizada de colores, tipografia y estilos visuales.') . '</p>',
        ];

        // --- Filtros por vertical ---
        $verticalLabels = [
            'todos' => $this->t('Todos'),
            'agroconecta' => $this->t('Agroalimentario'),
            'comercio' => $this->t('Comercio'),
            'servicios' => $this->t('Servicios'),
            'empleabilidad' => $this->t('Empleabilidad'),
            'emprendimiento' => $this->t('Emprendimiento'),
        ];

        // Vertical icons for filter pills (duotone SVGs).
        $verticalIcons = [
            'todos' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'agroconecta' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></svg>',
            'comercio' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
            'servicios' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
            'empleabilidad' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>',
            'emprendimiento' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>',
        ];

        $presets = $this->presetService->getAllPresets();
        $verticalCounts = ['todos' => count($presets)];
        foreach ($presets as $preset) {
            $v = $preset['vertical'];
            $verticalCounts[$v] = ($verticalCounts[$v] ?? 0) + 1;
        }

        $filterPills = '';
        foreach ($verticalLabels as $key => $label) {
            $count = $verticalCounts[$key] ?? 0;
            $activeClass = ($key === 'todos') ? ' is-active' : '';
            $icon = $verticalIcons[$key] ?? '';
            $filterPills .= '<button type="button" class="preset-filter-pill' . $activeClass . '" data-vertical="' . $key . '" aria-label="' . $label . '">'
                . '<span class="preset-filter-pill__icon">' . $icon . '</span>'
                . '<span class="preset-filter-pill__label">' . $label . '</span>'
                . '<span class="preset-filter-count">' . $count . '</span>'
                . '</button>';
        }

        $form['industry_preset']['filters'] = [
            '#markup' => Markup::create('<div class="preset-filter-bar">' . $filterPills . '</div>'),
        ];

        // --- Build visual preset card options ---
        $presetOptions = [];
        $checkSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        $zoomSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';

        // Opcion: Sin ajuste predefinido (banner horizontal span-2).
        $customizeSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="2.5"/><path d="M17 2h4v4"/><path d="M21 2l-7 7"/><circle cx="8.5" cy="12.5" r="2.5"/><path d="M7 2H3v4"/><path d="M3 2l7 7"/><circle cx="6.5" cy="18.5" r="2.5"/></svg>';

        $presetOptions[''] = Markup::create('<div class="preset-picker-card preset-picker-card--none" data-vertical="todos">'
            . '<span class="preset-picker-check">' . $checkSvg . '</span>'
            . '<div class="preset-picker-none-content">'
            . '<div class="preset-picker-none-icon">' . $customizeSvg . '</div>'
            . '<div class="preset-picker-none-text">'
            . '<span class="preset-picker-none-title">' . $this->t('Sin ajuste predefinido') . '</span>'
            . '<span class="preset-picker-none-desc">' . $this->t('Configura manualmente todos los colores, tipografias y componentes a tu gusto.') . '</span>'
            . '</div>'
            . '</div>'
            . '</div>');

        // Vertical labels for badges (translated).
        $verticalBadgeLabels = [
            'agroconecta' => $this->t('Agro'),
            'comercio' => $this->t('Comercio'),
            'servicios' => $this->t('Servicios'),
            'empleabilidad' => $this->t('Empleo'),
            'emprendimiento' => $this->t('Emprender'),
        ];

        foreach ($presets as $id => $preset) {
            $imageBase = '/' . $themePath . '/images/pickers/presets/' . $id;
            $imagePath = $imageBase . '.png';
            $webpPath = $imageBase . '.webp';
            $colors = $preset['colors'];
            $swatches = '<span class="preset-picker-swatch" style="background:' . $colors['primary'] . '"></span>'
                . '<span class="preset-picker-swatch" style="background:' . $colors['secondary'] . '"></span>'
                . '<span class="preset-picker-swatch" style="background:' . $colors['accent'] . '"></span>'
                . '<span class="preset-picker-swatch" style="background:' . $colors['background'] . '"></span>';

            $badgeLabel = $verticalBadgeLabels[$preset['vertical']] ?? ucfirst($preset['vertical']);

            $presetOptions[$id] = Markup::create('<div class="preset-picker-card" data-vertical="' . $preset['vertical'] . '" data-preset-id="' . $id . '">'
                . '<span class="preset-picker-check">' . $checkSvg . '</span>'
                . '<div class="preset-picker-thumb">'
                . '<picture>'
                . '<source srcset="' . $webpPath . '" type="image/webp" />'
                . '<img src="' . $imagePath . '" alt="' . $this->t('Vista previa: @name', ['@name' => $preset['label']]) . '" loading="lazy" width="640" height="640" />'
                . '</picture>'
                . '<button type="button" class="preset-picker-zoom" data-src="' . $imagePath . '" data-title="' . $this->t($preset['label']) . '" aria-label="' . $this->t('Ampliar vista previa') . '">'
                . $zoomSvg
                . '</button>'
                . '</div>'
                . '<div class="preset-picker-info">'
                . '<div class="preset-picker-header">'
                . '<span class="preset-picker-title">' . $this->t($preset['label']) . '</span>'
                . '<span class="preset-picker-vertical-badge">' . $badgeLabel . '</span>'
                . '</div>'
                . '<div class="preset-picker-meta">'
                . '<div class="preset-picker-palette">' . $swatches . '</div>'
                . '<span class="preset-picker-fonts">' . $preset['typography']['headings'] . ' + ' . $preset['typography']['body'] . '</span>'
                . '</div>'
                . '</div>'
                . '</div>');
        }

        $form['industry_preset']['preset_id'] = [
            '#type' => 'radios',
            '#title' => $this->t('Elige el ajuste predefinido para tu sector'),
            '#title_display' => 'invisible',
            '#options' => $presetOptions,
            '#default_value' => $this->getConfigValue($config, 'industry_preset', ''),
            '#attributes' => ['class' => ['jaraba-preset-picker', 'preset-picker-grid']],
        ];

        // Sticky action bar — resumen de seleccion + checkbox aplicar.
        $form['industry_preset']['sticky_bar'] = [
            '#markup' => Markup::create(
                '<div class="preset-sticky-bar" data-preset-sticky hidden>'
                . '<div class="preset-sticky-bar__content">'
                . '<div class="preset-sticky-bar__preview">'
                . '<span class="preset-sticky-bar__label">' . $this->t('Seleccionado:') . '</span>'
                . '<span class="preset-sticky-bar__name" data-sticky-name>—</span>'
                . '<span class="preset-sticky-bar__swatches" data-sticky-swatches></span>'
                . '</div>'
                . '<label class="preset-sticky-bar__toggle">'
                . '<span class="preset-sticky-bar__toggle-text">' . $this->t('Aplicar al guardar') . '</span>'
                . '</label>'
                . '</div>'
                . '</div>'
            ),
        ];

        $form['industry_preset']['apply_preset'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Aplicar colores y tipografia del ajuste predefinido al guardar'),
            '#default_value' => FALSE,
            '#description' => $this->t('Marca esta casilla para sobrescribir tus colores actuales con los del ajuste predefinido seleccionado.'),
            '#attributes' => ['class' => ['preset-apply-checkbox']],
            '#wrapper_attributes' => ['class' => ['preset-apply-wrapper']],
        ];

        // Lightbox overlay container (rendered once, reused by JS).
        $form['industry_preset']['lightbox'] = [
            '#markup' => Markup::create(
                '<div class="preset-lightbox" data-preset-lightbox hidden>'
                . '<div class="preset-lightbox__backdrop"></div>'
                . '<div class="preset-lightbox__container">'
                . '<button type="button" class="preset-lightbox__close" aria-label="' . $this->t('Cerrar vista previa') . '">'
                . '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                . '</button>'
                . '<img class="preset-lightbox__img" src="" alt="" />'
                . '<div class="preset-lightbox__caption" data-lightbox-caption></div>'
                . '</div>'
                . '</div>'
            ),
        ];

        // Attach preset picker JS at form root level to ensure loading
        // (details inside vertical_tabs may not propagate #attached correctly).
        $form['#attached']['library'][] = 'jaraba_theming/preset_picker';

        // === TAB 2: IDENTIDAD ===
        $form['identity'] = [
            '#type' => 'details',
            '#title' => $this->t('Identidad de Marca'),
            '#group' => 'settings',
            '#attributes' => ['class' => ['customizer-tab']],
        ];

        $form['identity']['site_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre del Sitio'),
            '#default_value' => $this->getConfigValue($config, 'site_name', ''),
            '#maxlength' => 64,
            '#description' => $this->t('El nombre que aparecerá en el encabezado y título del sitio.'),
        ];

        $form['identity']['site_slogan'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Eslogan'),
            '#default_value' => $this->getConfigValue($config, 'site_slogan', ''),
            '#maxlength' => 128,
        ];

        $form['identity']['logo'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Logo Principal'),
            '#description' => $this->t('Formatos: PNG, JPG, SVG, WebP. Tamaño recomendado: 200x60px'),
            '#upload_location' => 'public://theming/logos',
            '#upload_validators' => [
                'file_validate_extensions' => ['png jpg jpeg svg webp'],
            ],
        ];

        $form['identity']['logo_alt'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Logo para Fondos Oscuros'),
            '#description' => $this->t('Versión del logo para usar sobre fondos oscuros.'),
            '#upload_location' => 'public://theming/logos',
            '#upload_validators' => [
                'file_validate_extensions' => ['png jpg jpeg svg webp'],
            ],
        ];

        $form['identity']['favicon'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Favicon'),
            '#description' => $this->t('Icono del navegador. Formatos: ICO, PNG. Tamaño: 32x32px'),
            '#upload_location' => 'public://theming/favicons',
            '#upload_validators' => [
                'file_validate_extensions' => ['ico png'],
            ],
        ];

        // === TAB 2: COLORES ===
        $form['colors'] = [
            '#type' => 'details',
            '#title' => $this->t('Colores'),
            '#group' => 'settings',
        ];

        $form['colors']['colors_main'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Colores de Marca'),
            '#attributes' => ['class' => ['color-fieldset']],
        ];

        $form['colors']['colors_main']['color_primary'] = [
            '#type' => 'color',
            '#title' => $this->t('Color Primario'),
            '#default_value' => $this->getConfigValue($config, 'color_primary', '#FF8C42'),
            '#description' => $this->t('Color principal de tu marca. Se usa en botones, enlaces y elementos destacados.'),
        ];

        $form['colors']['colors_main']['color_secondary'] = [
            '#type' => 'color',
            '#title' => $this->t('Color Secundario'),
            '#default_value' => $this->getConfigValue($config, 'color_secondary', '#00A9A5'),
        ];

        $form['colors']['colors_main']['color_accent'] = [
            '#type' => 'color',
            '#title' => $this->t('Color Acento'),
            '#default_value' => $this->getConfigValue($config, 'color_accent', '#233D63'),
        ];

        $form['colors']['colors_ui'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Colores de Estado'),
            '#attributes' => ['class' => ['color-fieldset']],
        ];

        $form['colors']['colors_ui']['color_success'] = [
            '#type' => 'color',
            '#title' => $this->t('Éxito'),
            '#default_value' => $this->getConfigValue($config, 'color_success', '#10B981'),
        ];

        $form['colors']['colors_ui']['color_warning'] = [
            '#type' => 'color',
            '#title' => $this->t('Advertencia'),
            '#default_value' => $this->getConfigValue($config, 'color_warning', '#F59E0B'),
        ];

        $form['colors']['colors_ui']['color_error'] = [
            '#type' => 'color',
            '#title' => $this->t('Error'),
            '#default_value' => $this->getConfigValue($config, 'color_error', '#EF4444'),
        ];

        $form['colors']['colors_bg'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Fondos'),
            '#attributes' => ['class' => ['color-fieldset']],
        ];

        $form['colors']['colors_bg']['color_bg_body'] = [
            '#type' => 'color',
            '#title' => $this->t('Fondo de Página'),
            '#default_value' => $this->getConfigValue($config, 'color_bg_body', '#F8FAFC'),
        ];

        $form['colors']['colors_bg']['color_bg_surface'] = [
            '#type' => 'color',
            '#title' => $this->t('Fondo de Tarjetas'),
            '#default_value' => $this->getConfigValue($config, 'color_bg_surface', '#FFFFFF'),
        ];

        $form['colors']['colors_bg']['color_text'] = [
            '#type' => 'color',
            '#title' => $this->t('Color de Texto'),
            '#default_value' => $this->getConfigValue($config, 'color_text', '#1A1A2E'),
        ];

        // === TAB 3: TIPOGRAFÍA ===
        $form['typography'] = [
            '#type' => 'details',
            '#title' => $this->t('Tipografía'),
            '#group' => 'settings',
        ];

        $fonts = [
            'Outfit' => 'Outfit (Jaraba Default)',
            'Inter' => 'Inter',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Poppins' => 'Poppins',
            'Montserrat' => 'Montserrat',
            'Lato' => 'Lato',
            'Playfair Display' => 'Playfair Display (Serif)',
            'Lora' => 'Lora (Serif)',
        ];

        $form['typography']['font_headings'] = [
            '#type' => 'select',
            '#title' => $this->t('Fuente de Títulos'),
            '#options' => $fonts,
            '#default_value' => $this->getConfigValue($config, 'font_headings', 'Outfit'),
        ];

        $form['typography']['font_body'] = [
            '#type' => 'select',
            '#title' => $this->t('Fuente de Cuerpo'),
            '#options' => $fonts,
            '#default_value' => $this->getConfigValue($config, 'font_body', 'Inter'),
        ];

        $form['typography']['font_size_base'] = [
            '#type' => 'select',
            '#title' => $this->t('Tamaño Base de Fuente'),
            '#options' => [
                '14' => '14px (Compacto)',
                '16' => '16px (Normal)',
                '18' => '18px (Grande)',
            ],
            '#default_value' => $this->getConfigValue($config, 'font_size_base', '16'),
        ];

        // Fuentes personalizadas (Avanzado) - G117-5
        $form['typography']['custom_fonts'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Fuentes Personalizadas (Avanzado)'),
            '#description' => $this->t('Si se configuran fuentes personalizadas, se priorizan sobre la selección anterior de Google Fonts.'),
            '#attributes' => ['class' => ['custom-fonts-fieldset']],
        ];

        $form['typography']['custom_fonts']['font_heading_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('URL Fuente de Títulos'),
            '#default_value' => $this->getConfigValue($config, 'font_heading_url', ''),
            '#maxlength' => 512,
            '#description' => $this->t('URL de un archivo .woff2 o URL de Google Fonts CSS (ej: https://fonts.googleapis.com/css2?family=...).'),
            '#placeholder' => 'https://fonts.googleapis.com/css2?family=MiFuente',
        ];

        $form['typography']['custom_fonts']['font_heading_family'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Familia Tipográfica de Títulos'),
            '#default_value' => $this->getConfigValue($config, 'font_heading_family', ''),
            '#maxlength' => 100,
            '#description' => $this->t('Nombre CSS font-family exacto de la fuente personalizada de títulos (ej: "Mi Fuente Custom").'),
        ];

        $form['typography']['custom_fonts']['font_body_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('URL Fuente de Cuerpo'),
            '#default_value' => $this->getConfigValue($config, 'font_body_url', ''),
            '#maxlength' => 512,
            '#description' => $this->t('URL de un archivo .woff2 o URL de Google Fonts CSS (ej: https://fonts.googleapis.com/css2?family=...).'),
            '#placeholder' => 'https://fonts.googleapis.com/css2?family=MiFuente',
        ];

        $form['typography']['custom_fonts']['font_body_family'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Familia Tipográfica de Cuerpo'),
            '#default_value' => $this->getConfigValue($config, 'font_body_family', ''),
            '#maxlength' => 100,
            '#description' => $this->t('Nombre CSS font-family exacto de la fuente personalizada de cuerpo (ej: "Mi Fuente Custom").'),
        ];

        // === TAB 4: ENCABEZADO ===
        $form['header_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Encabezado'),
            '#group' => 'settings',
        ];

        $form['header_options']['header_variant'] = $this->buildVisualPicker('header', [
            'classic' => ['title' => $this->t('Clásico'), 'description' => $this->t('Logo izquierda, menú derecha')],
            'centered' => ['title' => $this->t('Centrado'), 'description' => $this->t('Logo y menú centrados')],
            'hero' => ['title' => $this->t('Hero'), 'description' => $this->t('Transparente sobre imagen')],
            'split' => ['title' => $this->t('Dividido'), 'description' => $this->t('Logo centro, menú partido')],
            'minimal' => ['title' => $this->t('Minimal'), 'description' => $this->t('Solo logo y hamburguesa')],
        ], $this->getConfigValue($config, 'header_variant', 'classic'));
        $form['header_options']['header_variant']['#title'] = $this->t('Estilo de Encabezado');

        $form['header_options']['header_sticky'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Encabezado fijo al hacer scroll'),
            '#default_value' => $this->getConfigValue($config, 'header_sticky', TRUE),
        ];

        $form['header_options']['header_cta_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Mostrar botón CTA en encabezado'),
            '#default_value' => $this->getConfigValue($config, 'header_cta_enabled', TRUE),
        ];

        $form['header_options']['header_cta_text'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Texto del botón CTA'),
            '#default_value' => $this->getConfigValue($config, 'header_cta_text', 'Empezar'),
            '#maxlength' => 32,
            '#states' => ['visible' => [':input[name="header_cta_enabled"]' => ['checked' => TRUE]]],
        ];

        $form['header_options']['header_cta_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Enlace del botón CTA'),
            '#default_value' => $this->getConfigValue($config, 'header_cta_url', '/registro'),
            '#description' => $this->t('Ruta interna (ej: /registro) o URL completa (ej: https://ejemplo.com).'),
            '#placeholder' => '/registro',
            '#states' => ['visible' => [':input[name="header_cta_enabled"]' => ['checked' => TRUE]]],
        ];

        // === TAB 5: HERO ===
        $form['hero_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Hero Section'),
            '#group' => 'settings',
        ];

        $form['hero_options']['hero_variant'] = $this->buildVisualPicker('hero', [
            'fullscreen' => ['title' => $this->t('Pantalla Completa'), 'description' => $this->t('Ocupa toda la ventana')],
            'split' => ['title' => $this->t('Dividido'), 'description' => $this->t('Texto izquierda, imagen derecha')],
            'compact' => ['title' => $this->t('Compacto'), 'description' => $this->t('Altura reducida')],
            'animated' => ['title' => $this->t('Animado'), 'description' => $this->t('Con efectos de movimiento')],
            'centered' => ['title' => $this->t('Centrado'), 'description' => $this->t('Texto centrado sobre imagen')],
        ], $this->getConfigValue($config, 'hero_variant', 'split'));
        $form['hero_options']['hero_variant']['#title'] = $this->t('Estilo de Hero');

        $form['hero_options']['hero_overlay'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Aplicar overlay oscuro sobre imagen'),
            '#default_value' => $this->getConfigValue($config, 'hero_overlay', TRUE),
        ];

        // === TAB 6: TARJETAS ===
        $form['cards_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Tarjetas'),
            '#group' => 'settings',
        ];

        $form['cards_options']['card_style'] = [
            '#type' => 'radios',
            '#title' => $this->t('Estilo de Tarjetas'),
            '#options' => [
                'elevated' => $this->t('Elevated - Con sombra'),
                'outlined' => $this->t('Outlined - Con borde'),
                'flat' => $this->t('Flat - Sin borde ni sombra'),
                'glass' => $this->t('Glassmorphism - Efecto vidrio'),
            ],
            '#default_value' => $this->getConfigValue($config, 'card_style', 'elevated'),
            '#attributes' => ['class' => ['visual-picker', 'visual-picker--cards']],
        ];

        $form['cards_options']['card_border_radius'] = [
            '#type' => 'select',
            '#title' => $this->t('Bordes Redondeados'),
            '#options' => [
                '0' => $this->t('Sin redondeo (0px)'),
                '4' => $this->t('Pequeño (4px)'),
                '8' => $this->t('Medio (8px)'),
                '12' => $this->t('Grande (12px)'),
                '16' => $this->t('Extra grande (16px)'),
                '24' => $this->t('Muy grande (24px)'),
            ],
            '#default_value' => $this->getConfigValue($config, 'card_border_radius', '12'),
        ];

        $form['cards_options']['card_hover_effect'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Efecto hover en tarjetas'),
            '#default_value' => $this->getConfigValue($config, 'card_hover_effect', TRUE),
        ];

        // === TAB 7: BOTONES ===
        $form['buttons_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Botones'),
            '#group' => 'settings',
        ];

        $form['buttons_options']['button_style'] = [
            '#type' => 'radios',
            '#title' => $this->t('Estilo de Botones'),
            '#options' => [
                'solid' => $this->t('Solid - Relleno sólido'),
                'outline' => $this->t('Outline - Solo borde'),
                'ghost' => $this->t('Ghost - Sin fondo ni borde'),
                'gradient' => $this->t('Gradient - Degradado'),
            ],
            '#default_value' => $this->getConfigValue($config, 'button_style', 'solid'),
            '#attributes' => ['class' => ['visual-picker', 'visual-picker--buttons']],
        ];

        $form['buttons_options']['button_border_radius'] = [
            '#type' => 'select',
            '#title' => $this->t('Bordes de Botón'),
            '#options' => [
                '0' => $this->t('Cuadrado (0px)'),
                '4' => $this->t('Ligeramente redondeado (4px)'),
                '8' => $this->t('Redondeado (8px)'),
                '9999' => $this->t('Pill (completamente redondo)'),
            ],
            '#default_value' => $this->getConfigValue($config, 'button_border_radius', '8'),
        ];

        // === TAB 8: FOOTER ===
        $form['footer_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Pie de Página'),
            '#group' => 'settings',
        ];

        $form['footer_options']['footer_variant'] = $this->buildVisualPicker('footer', [
            'minimal' => ['title' => $this->t('Minimal'), 'description' => $this->t('Solo copyright')],
            'standard' => ['title' => $this->t('Standard'), 'description' => $this->t('Copyright + social')],
            'mega' => ['title' => $this->t('Mega Footer'), 'description' => $this->t('4 columnas completas')],
            'split' => ['title' => $this->t('Split'), 'description' => $this->t('Logo + links')],
        ], $this->getConfigValue($config, 'footer_variant', 'standard'));
        $form['footer_options']['footer_variant']['#title'] = $this->t('Estilo de Pie de Página');

        $form['footer_options']['footer_copyright'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Texto de Copyright'),
            '#default_value' => $this->getConfigValue($config, 'footer_copyright', '© [year] Mi Empresa. Todos los derechos reservados.'),
            '#description' => $this->t('Usa [year] para el año actual automático'),
        ];

        // === TAB 9: REDES SOCIALES ===
        $form['social_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Redes Sociales'),
            '#group' => 'settings',
        ];

        $form['social_options']['social_facebook'] = [
            '#type' => 'url',
            '#title' => $this->t('Facebook'),
            '#default_value' => $this->getConfigValue($config, 'social_facebook', ''),
            '#placeholder' => 'https://facebook.com/...',
        ];

        $form['social_options']['social_twitter'] = [
            '#type' => 'url',
            '#title' => $this->t('X (Twitter)'),
            '#default_value' => $this->getConfigValue($config, 'social_twitter', ''),
            '#placeholder' => 'https://x.com/...',
        ];

        $form['social_options']['social_linkedin'] = [
            '#type' => 'url',
            '#title' => $this->t('LinkedIn'),
            '#default_value' => $this->getConfigValue($config, 'social_linkedin', ''),
            '#placeholder' => 'https://linkedin.com/...',
        ];

        $form['social_options']['social_instagram'] = [
            '#type' => 'url',
            '#title' => $this->t('Instagram'),
            '#default_value' => $this->getConfigValue($config, 'social_instagram', ''),
            '#placeholder' => 'https://instagram.com/...',
        ];

        $form['social_options']['social_youtube'] = [
            '#type' => 'url',
            '#title' => $this->t('YouTube'),
            '#default_value' => $this->getConfigValue($config, 'social_youtube', ''),
            '#placeholder' => 'https://youtube.com/...',
        ];

        // === TAB 10: AVANZADO ===
        $form['advanced_options'] = [
            '#type' => 'details',
            '#title' => $this->t('Opciones Avanzadas'),
            '#group' => 'settings',
        ];

        $form['advanced_options']['dark_mode_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar modo oscuro'),
            '#description' => $this->t('Permite a los usuarios cambiar entre modo claro y oscuro'),
            '#default_value' => $this->getConfigValue($config, 'dark_mode_enabled', FALSE),
        ];

        $form['advanced_options']['animations_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar animaciones'),
            '#description' => $this->t('Micro-animaciones en hover y transiciones'),
            '#default_value' => $this->getConfigValue($config, 'animations_enabled', TRUE),
        ];

        $form['advanced_options']['back_to_top_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Botón "Volver arriba"'),
            '#default_value' => $this->getConfigValue($config, 'back_to_top_enabled', TRUE),
        ];

        $form['advanced_options']['custom_css'] = [
            '#type' => 'textarea',
            '#title' => $this->t('CSS Personalizado'),
            '#description' => $this->t('CSS adicional para tu sitio (avanzado)'),
            '#default_value' => $this->getConfigValue($config, 'custom_css', ''),
            '#rows' => 5,
        ];

        // Actions
        $form['actions'] = [
            '#type' => 'actions',
            '#attributes' => ['class' => ['customizer-actions']],
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Guardar Cambios'),
            '#button_type' => 'primary',
        ];

        $form['actions']['preview'] = [
            '#type' => 'button',
            '#value' => $this->t('Vista Previa del Sitio'),
            '#attributes' => [
                'class' => ['button--secondary'],
                'data-action' => 'preview',
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();

        // Obtener o crear configuración activa para el tenant actual.
        // THEMING-UNIFY-001: Prioriza hostname para resolver tenant correcto.
        $tenantId = $this->resolveCurrentTenantId();
        $config = $this->tokenService->getActiveConfig($tenantId);

        if (!$config) {
            // Crear nueva configuración con tenant_id resuelto.
            $config = \Drupal::entityTypeManager()
                ->getStorage('tenant_theme_config')
                ->create([
                    'name' => $this->t('Configuracion de @user', ['@user' => \Drupal::currentUser()->getDisplayName()]),
                    'is_active' => TRUE,
                    'tenant_id' => $tenantId,
                ]);
        }

        // Aplicar preset si se seleccionó
        $presetId = $values['preset_id'] ?? '';
        $applyPreset = !empty($values['apply_preset']);

        if ($presetId && $applyPreset) {
            $preset = $this->presetService->getPreset($presetId);
            if ($preset) {
                // Sobrescribir valores con los del preset
                $values['color_primary'] = $preset['colors']['primary'];
                $values['color_secondary'] = $preset['colors']['secondary'];
                $values['color_accent'] = $preset['colors']['accent'];
                $values['color_bg_body'] = $preset['colors']['background'];
                $values['font_headings'] = $preset['typography']['headings'];
                $values['font_body'] = $preset['typography']['body'];
                $values['card_border_radius'] = str_replace('px', '', $preset['ui']['border_radius']);

                $this->messenger()->addStatus($this->t('Preset "@preset" aplicado correctamente.', ['@preset' => $preset['label']]));
            }
        }

        // Guardar el preset seleccionado
        if ($config->hasField('industry_preset')) {
            $config->set('industry_preset', $presetId);
        }

        // Mapeo de campos del form a campos de la entidad
        $fieldMapping = [
            // Identidad
            'site_name' => 'site_name',
            'site_slogan' => 'site_slogan',

            // Colores
            'color_primary' => 'color_primary',
            'color_secondary' => 'color_secondary',
            'color_accent' => 'color_accent',
            'color_success' => 'color_success',
            'color_warning' => 'color_warning',
            'color_error' => 'color_error',
            'color_bg_body' => 'color_bg_body',
            'color_bg_surface' => 'color_bg_surface',
            'color_text' => 'color_text',

            // Tipografía
            'font_headings' => 'font_headings',
            'font_body' => 'font_body',
            'font_size_base' => 'font_size_base',
            'font_heading_url' => 'font_heading_url',
            'font_heading_family' => 'font_heading_family',
            'font_body_url' => 'font_body_url',
            'font_body_family' => 'font_body_family',

            // Componentes
            'header_variant' => 'header_variant',
            'header_sticky' => 'header_sticky',
            'header_cta_enabled' => 'header_cta_enabled',
            'header_cta_text' => 'header_cta_text',
            'header_cta_url' => 'header_cta_url',
            'hero_variant' => 'hero_variant',
            'hero_overlay' => 'hero_overlay',
            'card_style' => 'card_style',
            'card_border_radius' => 'card_border_radius',
            'card_hover_effect' => 'card_hover_effect',
            'button_style' => 'button_style',
            'button_border_radius' => 'button_border_radius',
            'footer_variant' => 'footer_variant',
            'footer_copyright' => 'footer_copyright',

            // Redes sociales
            'social_facebook' => 'social_facebook',
            'social_twitter' => 'social_twitter',
            'social_linkedin' => 'social_linkedin',
            'social_instagram' => 'social_instagram',
            'social_youtube' => 'social_youtube',

            // Avanzado
            'dark_mode_enabled' => 'dark_mode_enabled',
            'animations_enabled' => 'animations_enabled',
            'back_to_top_enabled' => 'back_to_top_enabled',
            'custom_css' => 'custom_css',
        ];

        // Aplicar valores
        foreach ($fieldMapping as $formField => $entityField) {
            $value = $values[$formField] ?? NULL;

            // Solo establecer si el campo existe en la entidad
            if ($config->hasField($entityField) && $value !== NULL) {
                $config->set($entityField, $value);
            }
        }

        // Manejar archivos (logo, favicon)
        if (!empty($values['logo'][0])) {
            $file = \Drupal\file\Entity\File::load($values['logo'][0]);
            if ($file) {
                $file->setPermanent();
                $file->save();
                if ($config->hasField('logo')) {
                    $config->set('logo', ['target_id' => $file->id()]);
                }
            }
        }

        if (!empty($values['logo_alt'][0])) {
            $file = \Drupal\file\Entity\File::load($values['logo_alt'][0]);
            if ($file) {
                $file->setPermanent();
                $file->save();
                if ($config->hasField('logo_alt')) {
                    $config->set('logo_alt', ['target_id' => $file->id()]);
                }
            }
        }

        if (!empty($values['favicon'][0])) {
            $file = \Drupal\file\Entity\File::load($values['favicon'][0]);
            if ($file) {
                $file->setPermanent();
                $file->save();
                if ($config->hasField('favicon')) {
                    $config->set('favicon', ['target_id' => $file->id()]);
                }
            }
        }

        try {
            $config->save();

            // Invalidar caché para que los tokens se regeneren
            \Drupal::service('cache.render')->invalidateAll();

            $this->messenger()->addStatus($this->t('Los cambios han sido guardados correctamente.'));
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('Error al guardar: @error', ['@error' => $e->getMessage()]));
            \Drupal::logger('jaraba_theming')->error('Error saving theme config: @error', ['@error' => $e->getMessage()]);
        }
    }

    /**
     * Helper para obtener valores de configuración.
     */
    protected function getConfigValue($config, string $key, $default)
    {
        if (!$config) {
            return $default;
        }
        try {
            $field = $config->get($key);
            return $field->value ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

}
