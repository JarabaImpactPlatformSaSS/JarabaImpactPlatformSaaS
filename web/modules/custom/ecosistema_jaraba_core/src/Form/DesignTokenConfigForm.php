<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar configuraciones de Design Tokens.
 *
 * Permite definir tokens de color, tipografía, espaciado y efectos
 * para cada scope de la cascada (Platform, Vertical, Plan, Tenant).
 * Los tokens usan el namespace --ej-* para inyección CSS en runtime.
 */
class DesignTokenConfigForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $entity */
        $entity = $this->entity;

        // =========================================================================
        // INFORMACIÓN GENERAL
        // =========================================================================
        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre'),
            '#maxlength' => 255,
            '#default_value' => $entity->label(),
            '#description' => $this->t('Nombre descriptivo (ej: "AgroConecta Defaults", "Tenant Acme Overrides").'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $entity->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfig::load',
            ],
            '#disabled' => !$entity->isNew(),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción'),
            '#default_value' => $entity->getDescription(),
            '#description' => $this->t('Describe el propósito de esta configuración de tokens.'),
            '#rows' => 2,
        ];

        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activa'),
            '#default_value' => $entity->status(),
            '#description' => $this->t('Solo las configuraciones activas se aplican en la cascada de tokens.'),
        ];

        // =========================================================================
        // SCOPE Y REFERENCIAS
        // =========================================================================
        $form['scope_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Alcance y Asociación'),
            '#open' => TRUE,
            '#description' => $this->t('Define a qué nivel de la cascada pertenece esta configuración: Platform → Vertical → Plan → Tenant.'),
        ];

        $form['scope_details']['scope'] = [
            '#type' => 'select',
            '#title' => $this->t('Nivel de cascada'),
            '#options' => [
                'platform' => $this->t('🌐 Platform (defaults globales)'),
                'vertical' => $this->t('📂 Vertical (personalización por vertical)'),
                'plan' => $this->t('💎 Plan (personalización por plan SaaS)'),
                'tenant' => $this->t('🏢 Tenant (personalización individual)'),
            ],
            '#default_value' => $entity->getScope(),
            '#required' => TRUE,
            '#description' => $this->t('Los tokens de un scope inferior sobrescriben los del scope superior.'),
        ];

        $form['scope_details']['vertical_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID de Vertical'),
            '#default_value' => $entity->getVerticalId(),
            '#description' => $this->t('Machine name de la vertical (ej: agroconecta, empleabilidad). Dejar vacío para scope platform.'),
            '#states' => [
                'visible' => [
                    [':input[name="scope"]' => ['value' => 'vertical']],
                    [':input[name="scope"]' => ['value' => 'plan']],
                    [':input[name="scope"]' => ['value' => 'tenant']],
                ],
            ],
        ];

        $form['scope_details']['plan_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID de Plan SaaS'),
            '#default_value' => $entity->getPlanId(),
            '#description' => $this->t('Machine name del plan (ej: starter, professional, enterprise).'),
            '#states' => [
                'visible' => [
                    [':input[name="scope"]' => ['value' => 'plan']],
                    [':input[name="scope"]' => ['value' => 'tenant']],
                ],
            ],
        ];

        $form['scope_details']['tenant_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID de Tenant'),
            '#default_value' => $entity->getTenantId(),
            '#description' => $this->t('Machine name o ID del tenant específico.'),
            '#states' => [
                'visible' => [
                    ':input[name="scope"]' => ['value' => 'tenant'],
                ],
            ],
        ];

        $form['scope_details']['preset_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Preset de Estilo'),
            '#default_value' => $entity->getPresetId(),
            '#description' => $this->t('ID del Industry Style Preset aplicado (ej: tech_startup_bold, gourmet_premium).'),
        ];

        // =========================================================================
        // COLOR TOKENS
        // =========================================================================
        $form['colors'] = [
            '#type' => 'details',
            '#title' => $this->t('🎨 Tokens de Color'),
            '#open' => FALSE,
            '#description' => $this->t('Variables CSS: --ej-color-{key}. Usar formato HEX (#FF8C42) o HSL.'),
        ];

        $colorTokens = $entity->getColorTokens();
        $colorFields = [
            'primary' => ['label' => 'Primary', 'desc' => 'Color principal de marca', 'default' => '#FF8C42'],
            'secondary' => ['label' => 'Secondary', 'desc' => 'Color secundario', 'default' => '#2D3436'],
            'accent' => ['label' => 'Accent', 'desc' => 'Color de acento/CTA', 'default' => '#00B894'],
            'background' => ['label' => 'Background', 'desc' => 'Fondo principal', 'default' => '#FFFFFF'],
            'surface' => ['label' => 'Surface', 'desc' => 'Fondo de tarjetas/paneles', 'default' => '#F8FAFC'],
            'text' => ['label' => 'Text', 'desc' => 'Color de texto principal', 'default' => '#1F2937'],
            'text-muted' => ['label' => 'Text Muted', 'desc' => 'Texto secundario', 'default' => '#6B7280'],
            'border' => ['label' => 'Border', 'desc' => 'Color de bordes', 'default' => '#E5E7EB'],
            'success' => ['label' => 'Success', 'desc' => 'Éxito/confirmación', 'default' => '#10B981'],
            'warning' => ['label' => 'Warning', 'desc' => 'Advertencia', 'default' => '#F59E0B'],
            'error' => ['label' => 'Error', 'desc' => 'Error/peligro', 'default' => '#EF4444'],
        ];

        foreach ($colorFields as $key => $config) {
            $form['colors']['color_' . str_replace('-', '_', $key)] = [
                '#type' => 'color',
                '#title' => $this->t('@label', ['@label' => $config['label']]),
                '#default_value' => $colorTokens[$key] ?? $config['default'],
                '#description' => $this->t('@desc → --ej-color-@key', ['@desc' => $config['desc'], '@key' => $key]),
            ];
        }

        // =========================================================================
        // TYPOGRAPHY TOKENS
        // =========================================================================
        $form['typography'] = [
            '#type' => 'details',
            '#title' => $this->t('🔤 Tokens de Tipografía'),
            '#open' => FALSE,
            '#description' => $this->t('Variables CSS: --ej-font-{key}.'),
        ];

        $typoTokens = $entity->getTypographyTokens();

        $form['typography']['font_family_heading'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Font Family Headings'),
            '#default_value' => $typoTokens['family-heading'] ?? 'Inter, system-ui, sans-serif',
            '#description' => $this->t('Tipografía para títulos → --ej-font-family-heading'),
        ];

        $form['typography']['font_family_body'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Font Family Body'),
            '#default_value' => $typoTokens['family-body'] ?? 'Inter, system-ui, sans-serif',
            '#description' => $this->t('Tipografía para texto → --ej-font-family-body'),
        ];

        $form['typography']['font_size_base'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Font Size Base'),
            '#default_value' => $typoTokens['size-base'] ?? '1rem',
            '#description' => $this->t('Tamaño base → --ej-font-size-base'),
        ];

        $form['typography']['font_weight_heading'] = [
            '#type' => 'select',
            '#title' => $this->t('Font Weight Headings'),
            '#options' => [
                '400' => '400 (Normal)',
                '500' => '500 (Medium)',
                '600' => '600 (Semibold)',
                '700' => '700 (Bold)',
                '800' => '800 (Extrabold)',
            ],
            '#default_value' => $typoTokens['weight-heading'] ?? '700',
            '#description' => $this->t('Peso de títulos → --ej-font-weight-heading'),
        ];

        // =========================================================================
        // SPACING TOKENS
        // =========================================================================
        $form['spacing'] = [
            '#type' => 'details',
            '#title' => $this->t('📐 Tokens de Espaciado'),
            '#open' => FALSE,
        ];

        $spacingTokens = $entity->getSpacingTokens();

        $form['spacing']['spacing_unit'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Unidad Base'),
            '#default_value' => $spacingTokens['unit'] ?? '0.25rem',
            '#description' => $this->t('Unidad base de espaciado → --ej-spacing-unit'),
        ];

        $form['spacing']['radius_base'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Border Radius Base'),
            '#default_value' => $spacingTokens['radius-base'] ?? '8px',
            '#description' => $this->t('Radio de bordes → --ej-spacing-radius-base'),
        ];

        $form['spacing']['radius_xl'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Border Radius XL'),
            '#default_value' => $spacingTokens['radius-xl'] ?? '16px',
            '#description' => $this->t('Radio XL (cards premium) → --ej-spacing-radius-xl'),
        ];

        // =========================================================================
        // EFFECT TOKENS (Glassmorphism, Shadows, Animations)
        // =========================================================================
        $form['effects'] = [
            '#type' => 'details',
            '#title' => $this->t('✨ Tokens de Efectos'),
            '#open' => FALSE,
            '#description' => $this->t('Glassmorphism, sombras, gradientes y animaciones premium.'),
        ];

        $effectTokens = $entity->getEffectTokens();

        $form['effects']['glass_bg'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Glass Background'),
            '#default_value' => $effectTokens['glass-bg'] ?? 'rgba(255, 255, 255, 0.95)',
            '#description' => $this->t('Fondo glassmorphism → --ej-glass-bg'),
        ];

        $form['effects']['glass_blur'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Glass Blur'),
            '#default_value' => $effectTokens['glass-blur'] ?? '10px',
            '#description' => $this->t('Intensidad blur (mínimo 10px per workflow) → --ej-glass-blur'),
        ];

        $form['effects']['shadow_card'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Card Shadow'),
            '#default_value' => $effectTokens['shadow-card'] ?? '0 4px 24px rgba(0, 0, 0, 0.04)',
            '#description' => $this->t('Sombra de tarjetas → --ej-shadow-card'),
        ];

        $form['effects']['gradient_primary'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Gradient Primary'),
            '#default_value' => $effectTokens['gradient-primary'] ?? 'linear-gradient(135deg, #FF8C42, #FF6B6B)',
            '#description' => $this->t('Gradiente principal → --ej-gradient-primary'),
        ];

        $form['effects']['animation_speed'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Animation Speed'),
            '#default_value' => $effectTokens['animation-speed'] ?? '0.3s',
            '#description' => $this->t('Velocidad de animaciones → --ej-animation-speed'),
        ];

        // =========================================================================
        // COMPONENT VARIANTS
        // =========================================================================
        $form['variants'] = [
            '#type' => 'details',
            '#title' => $this->t('🧩 Variantes de Componentes'),
            '#open' => FALSE,
            '#description' => $this->t('Selección de variantes SDC para los componentes principales.'),
        ];

        $variants = $entity->getComponentVariants();

        $form['variants']['variant_header'] = [
            '#type' => 'select',
            '#title' => $this->t('Header'),
            '#options' => [
                '' => $this->t('— Default —'),
                'minimal' => $this->t('Minimal'),
                'centered' => $this->t('Centered Logo'),
                'mega' => $this->t('Mega Menu'),
                'sticky_glass' => $this->t('Sticky Glassmorphism'),
            ],
            '#default_value' => $variants['header'] ?? '',
        ];

        $form['variants']['variant_card'] = [
            '#type' => 'select',
            '#title' => $this->t('Card'),
            '#options' => [
                '' => $this->t('— Default —'),
                'elevated' => $this->t('Elevated'),
                'outlined' => $this->t('Outlined'),
                'glass' => $this->t('Glassmorphism'),
                'product' => $this->t('Product Card'),
            ],
            '#default_value' => $variants['card'] ?? '',
        ];

        $form['variants']['variant_hero'] = [
            '#type' => 'select',
            '#title' => $this->t('Hero'),
            '#options' => [
                '' => $this->t('— Default —'),
                'split' => $this->t('Split (Texto + Imagen)'),
                'fullscreen' => $this->t('Fullscreen Background'),
                'parallax' => $this->t('Parallax'),
                'particles' => $this->t('Particles Animation'),
            ],
            '#default_value' => $variants['hero'] ?? '',
        ];

        $form['variants']['variant_footer'] = [
            '#type' => 'select',
            '#title' => $this->t('Footer'),
            '#options' => [
                '' => $this->t('— Default —'),
                'minimal' => $this->t('Minimal'),
                'columns' => $this->t('Multi-Column'),
                'dark' => $this->t('Dark Premium'),
            ],
            '#default_value' => $variants['footer'] ?? '',
        ];

        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Peso'),
            '#default_value' => $entity->get('weight') ?? 0,
            '#description' => $this->t('Orden de aplicación (menor = primero).'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $entity */
        $entity = $this->entity;

        // Construir arrays de tokens desde form values.
        $colorTokens = [];
        $colorKeys = ['primary', 'secondary', 'accent', 'background', 'surface', 'text', 'text-muted', 'border', 'success', 'warning', 'error'];
        foreach ($colorKeys as $key) {
            $formKey = 'color_' . str_replace('-', '_', $key);
            $value = $form_state->getValue($formKey);
            if (!empty($value)) {
                $colorTokens[$key] = $value;
            }
        }
        $entity->setColorTokens($colorTokens);

        $typographyTokens = [
            'family-heading' => $form_state->getValue('font_family_heading'),
            'family-body' => $form_state->getValue('font_family_body'),
            'size-base' => $form_state->getValue('font_size_base'),
            'weight-heading' => $form_state->getValue('font_weight_heading'),
        ];
        $entity->setTypographyTokens(array_filter($typographyTokens));

        $spacingTokens = [
            'unit' => $form_state->getValue('spacing_unit'),
            'radius-base' => $form_state->getValue('radius_base'),
            'radius-xl' => $form_state->getValue('radius_xl'),
        ];
        $entity->setSpacingTokens(array_filter($spacingTokens));

        $effectTokens = [
            'glass-bg' => $form_state->getValue('glass_bg'),
            'glass-blur' => $form_state->getValue('glass_blur'),
            'shadow-card' => $form_state->getValue('shadow_card'),
            'gradient-primary' => $form_state->getValue('gradient_primary'),
            'animation-speed' => $form_state->getValue('animation_speed'),
        ];
        $entity->setEffectTokens(array_filter($effectTokens));

        $componentVariants = [
            'header' => $form_state->getValue('variant_header'),
            'card' => $form_state->getValue('variant_card'),
            'hero' => $form_state->getValue('variant_hero'),
            'footer' => $form_state->getValue('variant_footer'),
        ];
        $entity->setComponentVariants(array_filter($componentVariants));

        $status = $entity->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Configuración de Design Tokens "%label" creada.', [
                '%label' => $entity->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Configuración de Design Tokens "%label" actualizada.', [
                '%label' => $entity->label(),
            ]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
    }

    /**
     * {@inheritdoc}
     */
    protected function actions(array $form, FormStateInterface $form_state)
    {
        $actions = parent::actions($form, $form_state);

        // URL de la colección para botones de cancelar/eliminar.
        $collectionUrl = $this->entity->isNew()
            ? \Drupal\Core\Url::fromRoute('entity.design_token_config.collection')
            : $this->entity->toUrl('collection');

        $actions['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancelar'),
            '#url' => $collectionUrl,
            '#attributes' => [
                'class' => ['button'],
            ],
            '#weight' => 10,
        ];

        if (!$this->entity->isNew()) {
            $actions['delete'] = [
                '#type' => 'link',
                '#title' => $this->t('Eliminar'),
                '#url' => $this->entity->toUrl('delete-form'),
                '#attributes' => [
                    'class' => ['button', 'button--danger'],
                ],
                '#weight' => 20,
            ];
        }

        return $actions;
    }

}
