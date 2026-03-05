<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar TenantThemeConfig.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class TenantThemeConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identidad'),
        'icon' => ['category' => 'ui', 'name' => 'building'],
        'description' => $this->t('Nombre, tenant y vertical base.'),
        'fields' => ['name', 'tenant_id', 'vertical'],
      ],
      'colors' => [
        'label' => $this->t('Colores'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Colores de marca y UI.'),
        'fields' => ['color_primary', 'color_secondary', 'color_accent', 'color_dark', 'color_success', 'color_warning', 'color_error', 'color_bg_body', 'color_bg_surface', 'color_text'],
      ],
      'typography' => [
        'label' => $this->t('Tipografía'),
        'icon' => ['category' => 'ui', 'name' => 'book'],
        'description' => $this->t('Fuentes y tamaños.'),
        'fields' => ['font_headings', 'font_body', 'font_size_base'],
      ],
      'components' => [
        'label' => $this->t('Componentes'),
        'icon' => ['category' => 'ui', 'name' => 'clipboard'],
        'description' => $this->t('Header, hero, tarjetas y botones.'),
        'fields' => ['header_variant', 'header_sticky', 'header_cta_enabled', 'hero_variant', 'card_style', 'button_style', 'footer_variant'],
      ],
      'advanced' => [
        'label' => $this->t('Avanzado'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Animaciones, dark mode y CSS personalizado.'),
        'fields' => ['dark_mode_enabled', 'animations_enabled', 'custom_css', 'is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
