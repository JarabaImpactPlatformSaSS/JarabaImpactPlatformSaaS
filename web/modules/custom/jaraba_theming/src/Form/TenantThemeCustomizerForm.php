<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
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

    /**
     * El servicio de tokens.
     */
    protected ThemeTokenService $tokenService;

    /**
     * El servicio de Industry Presets.
     */
    protected IndustryPresetService $presetService;

    /**
     * Constructor.
     */
    public function __construct(ThemeTokenService $token_service, IndustryPresetService $preset_service)
    {
        $this->tokenService = $token_service;
        $this->presetService = $preset_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_theming.token_service'),
            $container->get('jaraba_theming.industry_preset_service')
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
     * Genera el markup de un icono SVG.
     *
     * @param string $category
     *   Categoría del icono (ui, business, analytics, etc.).
     * @param string $name
     *   Nombre del icono.
     * @param string $size
     *   Tamaño: 'sm' (16px), 'md' (20px), 'lg' (24px).
     *
     * @return string
     *   Markup del icono como img tag.
     */
    protected function getIcon(string $category, string $name, string $size = 'md'): string
    {
        $sizes = ['sm' => '16', 'md' => '20', 'lg' => '24'];
        $px = $sizes[$size] ?? '20';
        $basePath = '/' . \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');
        $iconPath = "{$basePath}/images/icons/{$category}/{$name}.svg";

        return '<img src="' . $iconPath . '" alt="" width="' . $px . '" height="' . $px . '" class="jaraba-icon jaraba-icon--' . $size . '" style="vertical-align: middle; margin-right: 0.5rem;" />';
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
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->tokenService->getActiveConfig();
        $themePath = \Drupal::service('extension.list.theme')->getPath('ecosistema_jaraba_theme');

        // Attach libraries
        $form['#attached']['library'][] = 'jaraba_theming/visual_customizer';
        $form['#attached']['library'][] = 'ecosistema_jaraba_theme/admin-settings';
        $form['#attributes']['class'][] = 'jaraba-theme-customizer';
        $form['#attributes']['class'][] = 'jaraba-settings';

        // Premium Header with Screenshot
        $screenshotUrl = '/' . $themePath . '/screenshot.png';
        $form['header'] = [
            '#markup' => '
            <div class="jaraba-settings-header">
                <div class="jaraba-settings-header__preview">
                    <img src="' . $screenshotUrl . '" alt="' . $this->t('Vista previa del tema') . '" />
                </div>
                <div class="jaraba-settings-header__info">
                    <h2>' . $this->t('Personaliza tu Sitio') . '</h2>
                    <p>' . $this->t('Configura la apariencia visual de tu plataforma. Los cambios se aplican al guardar.') . '</p>
                </div>
            </div>',
        ];

        // Vertical tabs container
        $form['settings'] = [
            '#type' => 'vertical_tabs',
            '#default_tab' => 'edit-identity',
        ];

        // === TAB 1: INDUSTRY PRESETS ===
        $form['industry_preset'] = [
            '#type' => 'details',
            '#title' => Markup::create($this->getIcon('ui', 'sparkles') . $this->t('Preset por Sector')),
            '#group' => 'settings',
            '#weight' => -10,
            '#attributes' => ['class' => ['customizer-tab', 'customizer-tab--presets']],
        ];

        $form['industry_preset']['intro'] = [
            '#markup' => '<p>' . $this->t('Selecciona un preset predefinido para tu sector. Esto aplicará una combinación de colores, tipografía y estilos optimizados para tu industria.') . '</p>',
        ];

        // Build visual preset selector
        $presets = $this->presetService->getAllPresets();
        $presetOptions = [];
        foreach ($presets as $id => $preset) {
            $colors = implode('', array_map(fn($c) => '<span class="preset-color" style="background:' . $c . '"></span>', $preset['colors']));
            $presetOptions[$id] = '<div class="jaraba-preset-card">
                <div class="jaraba-preset-colors">' . $colors . '</div>
                <div class="jaraba-preset-info">
                    <span class="jaraba-preset-title">' . $preset['label'] . '</span>
                    <span class="jaraba-preset-vertical">' . ucfirst($preset['vertical']) . '</span>
                    <span class="jaraba-preset-fonts">' . $preset['typography']['headings'] . ' + ' . $preset['typography']['body'] . '</span>
                </div>
            </div>';
        }

        $form['industry_preset']['preset_id'] = [
            '#type' => 'radios',
            '#title' => $this->t('Elige tu sector'),
            '#options' => ['' => '<div class="jaraba-preset-card jaraba-preset-card--none"><span>' . $this->t('Sin preset - Configuración manual') . '</span></div>'] + $presetOptions,
            '#default_value' => $this->getConfigValue($config, 'industry_preset', ''),
            '#attributes' => ['class' => ['jaraba-preset-picker']],
        ];

        $form['industry_preset']['apply_preset'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Aplicar colores y tipografía del preset al guardar'),
            '#default_value' => FALSE,
            '#description' => $this->t('Marca esta casilla para sobrescribir tus colores actuales con los del preset seleccionado.'),
        ];

        // === TAB 2: IDENTIDAD ===
        $form['identity'] = [
            '#type' => 'details',
            '#title' => Markup::create($this->getIcon('ui', 'building') . $this->t('Identidad de Marca')),
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
            '#title' => Markup::create($this->getIcon('ui', 'star') . $this->t('Colores')),
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
            '#title' => Markup::create($this->getIcon('ui', 'book') . $this->t('Tipografía')),
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

        // === TAB 4: ENCABEZADO ===
        $form['header_options'] = [
            '#type' => 'details',
            '#title' => Markup::create($this->getIcon('ui', 'menu') . $this->t('Encabezado')),
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
        ];

        // === TAB 5: HERO ===
        $form['hero_options'] = [
            '#type' => 'details',
            '#title' => Markup::create($this->getIcon('ui', 'fire') . $this->t('Hero Section')),
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
            '#title' => Markup::create($this->getIcon('ui', 'clipboard') . $this->t('Tarjetas')),
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
            '#title' => Markup::create($this->getIcon('ui', 'bolt') . $this->t('Botones')),
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
            '#title' => Markup::create($this->getIcon('ui', 'list') . $this->t('Pie de Página')),
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
            '#title' => Markup::create($this->getIcon('ui', 'globe') . $this->t('Redes Sociales')),
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
            '#title' => Markup::create($this->getIcon('ui', 'cog') . $this->t('Opciones Avanzadas')),
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
            '#value' => $this->t('Vista Previa'),
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

        // Obtener o crear configuración activa para el tenant actual
        $config = $this->tokenService->getActiveConfig();

        if (!$config) {
            // Crear nueva configuración si no existe
            $config = \Drupal::entityTypeManager()
                ->getStorage('tenant_theme_config')
                ->create([
                    'name' => $this->t('Configuración de @user', ['@user' => \Drupal::currentUser()->getDisplayName()]),
                    'is_active' => TRUE,
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

            // Componentes
            'header_variant' => 'header_variant',
            'header_sticky' => 'header_sticky',
            'header_cta_enabled' => 'header_cta_enabled',
            'header_cta_text' => 'header_cta_text',
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
