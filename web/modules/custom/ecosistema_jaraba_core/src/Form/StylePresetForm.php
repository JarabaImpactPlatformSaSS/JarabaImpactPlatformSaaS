<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar Style Presets.
 *
 * Organiza los campos en fieldsets lógicos:
 * - Identificación (id, label, vertical, sector, mood)
 * - Tokens de diseño (color, typography, spacing, effects)
 * - Componentes y animación
 * - Contenido y fuentes
 */
class StylePresetForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);
        /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset */
        $preset = $this->entity;

        // =====================================================================
        // IDENTIFICACIÓN
        // =====================================================================

        $form['identification'] = [
            '#type' => 'details',
            '#title' => $this->t('Identificación del Preset'),
            '#open' => TRUE,
        ];

        $form['identification']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre'),
            '#maxlength' => 255,
            '#default_value' => $preset->label(),
            '#description' => $this->t('Nombre visible del preset (ej: "Gourmet Artesanal").'),
            '#required' => TRUE,
        ];

        $form['identification']['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $preset->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\StylePreset::load',
            ],
            '#disabled' => !$preset->isNew(),
        ];

        $form['identification']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción'),
            '#default_value' => $preset->getDescription(),
            '#rows' => 3,
        ];

        $form['identification']['vertical'] = [
            '#type' => 'select',
            '#title' => $this->t('Vertical'),
            '#options' => [
                '' => $this->t('- Seleccionar -'),
                'agroconecta' => 'AgroConecta',
                'comercioconecta' => 'ComercioConecta',
                'serviciosconecta' => 'ServiciosConecta',
            ],
            '#default_value' => $preset->getVertical(),
            '#required' => TRUE,
        ];

        $form['identification']['sector'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Sector'),
            '#default_value' => $preset->getSector(),
            '#description' => $this->t('Sector de negocio (ej: gourmet, legal, tech, barrio).'),
            '#required' => TRUE,
        ];

        $form['identification']['mood'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Mood tags (JSON)'),
            '#default_value' => json_encode($preset->getMood(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('Array JSON de tags emocionales: ["luxury", "craft", "premium"]'),
            '#rows' => 2,
        ];

        $form['identification']['target_audience'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Público objetivo'),
            '#default_value' => $preset->getTargetAudience(),
            '#rows' => 2,
        ];

        // =====================================================================
        // DESIGN TOKENS
        // =====================================================================

        $form['tokens'] = [
            '#type' => 'details',
            '#title' => $this->t('Design Tokens'),
            '#open' => TRUE,
        ];

        $form['tokens']['color_tokens'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Color Tokens (JSON)'),
            '#default_value' => json_encode($preset->getColorTokens(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: primary, secondary, accent, surface, surface-dark, text-primary, text-secondary, text-muted, text-inverse.'),
            '#rows' => 8,
        ];

        $form['tokens']['typography_tokens'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Typography Tokens (JSON)'),
            '#default_value' => json_encode($preset->getTypographyTokens(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: family-heading, family-body, family-mono.'),
            '#rows' => 4,
        ];

        $form['tokens']['spacing_tokens'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Spacing Tokens (JSON)'),
            '#default_value' => json_encode($preset->getSpacingTokens(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: radius-md, shadow-md, shadow-lg.'),
            '#rows' => 4,
        ];

        $form['tokens']['effect_tokens'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Effect Tokens (JSON)'),
            '#default_value' => json_encode($preset->getEffectTokens(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: glass-bg, glass-blur, gradient-primary, etc.'),
            '#rows' => 4,
        ];

        // =====================================================================
        // COMPONENTS & ANIMATION
        // =====================================================================

        $form['components'] = [
            '#type' => 'details',
            '#title' => $this->t('Componentes y Animación'),
        ];

        $form['components']['component_variants'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Component Variants (JSON)'),
            '#default_value' => json_encode($preset->getComponentVariants(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: header, hero, cards, footer.'),
            '#rows' => 4,
        ];

        $form['components']['animation_config'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Animation Config (JSON)'),
            '#default_value' => json_encode($preset->getAnimationConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: type (glow|elegant|glassmorphism|organic), speed.'),
            '#rows' => 3,
        ];

        // =====================================================================
        // CONTENT & FONTS
        // =====================================================================

        $form['content'] = [
            '#type' => 'details',
            '#title' => $this->t('Contenido y Fuentes'),
        ];

        $form['content']['content_guidelines'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Content Guidelines (JSON)'),
            '#default_value' => json_encode($preset->getContentGuidelines(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('JSON con claves: photography, copywriting, iconography.'),
            '#rows' => 4,
        ];

        $form['content']['font_imports'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Font Import URLs (JSON)'),
            '#default_value' => json_encode($preset->getFontImports(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#description' => $this->t('Array JSON de URLs Google Fonts para inyectar en <link>.'),
            '#rows' => 3,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Validar campos JSON.
        $jsonFields = [
            'mood',
            'color_tokens',
            'typography_tokens',
            'spacing_tokens',
            'effect_tokens',
            'component_variants',
            'animation_config',
            'content_guidelines',
            'font_imports',
        ];

        foreach ($jsonFields as $field) {
            $value = $form_state->getValue($field);
            if (!empty($value) && json_decode($value) === NULL && json_last_error() !== JSON_ERROR_NONE) {
                $form_state->setErrorByName($field, $this->t('El campo @field contiene JSON inválido.', ['@field' => $field]));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset */
        $preset = $this->entity;

        // Procesar campos JSON que vienen como strings del formulario.
        $jsonFields = [
            'mood' => 'setMood',
            'color_tokens' => 'setColorTokens',
            'typography_tokens' => 'setTypographyTokens',
            'spacing_tokens' => 'setSpacingTokens',
            'effect_tokens' => 'setEffectTokens',
            'component_variants' => 'setComponentVariants',
            'animation_config' => 'setAnimationConfig',
            'content_guidelines' => 'setContentGuidelines',
            'font_imports' => 'setFontImports',
        ];

        foreach ($jsonFields as $field => $setter) {
            $value = $form_state->getValue($field);
            if (!empty($value)) {
                $decoded = json_decode($value, TRUE);
                if ($decoded !== NULL) {
                    $preset->{$setter}($decoded);
                }
            }
        }

        $status = $preset->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Style Preset %label creado.', [
                '%label' => $preset->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Style Preset %label actualizado.', [
                '%label' => $preset->label(),
            ]));
        }

        $form_state->setRedirectUrl($preset->toUrl('collection'));
        return $status;
    }

}
