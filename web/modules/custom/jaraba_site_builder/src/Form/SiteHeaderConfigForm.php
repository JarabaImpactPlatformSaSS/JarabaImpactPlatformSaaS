<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site header configuration.
 */
class SiteHeaderConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'layout' => [
        'label' => $this->t('Layout & Type'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Header type, menu position and main menu.'),
        'fields' => ['header_type', 'main_menu_position', 'main_menu_id'],
      ],
      'branding' => [
        'label' => $this->t('Logo & Branding'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Logo, alt text, width and mobile variant.'),
        'fields' => ['logo_id', 'logo_alt', 'logo_width', 'logo_mobile_id'],
      ],
      'behavior' => [
        'label' => $this->t('Behavior'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Sticky, scroll and transparency settings.'),
        'fields' => ['is_sticky', 'sticky_offset', 'transparent_on_hero', 'hide_on_scroll_down'],
      ],
      'cta' => [
        'label' => $this->t('CTA Button'),
        'icon' => ['category' => 'actions', 'name' => 'click'],
        'description' => $this->t('Call to action button settings.'),
        'fields' => ['show_cta', 'cta_text', 'cta_url', 'cta_style', 'cta_icon'],
      ],
      'elements' => [
        'label' => $this->t('Optional Elements'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Search, language switcher, user menu and contact.'),
        'fields' => ['show_search', 'show_language_switcher', 'show_user_menu', 'show_phone', 'show_email'],
      ],
      'topbar' => [
        'label' => $this->t('Top Bar'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Top bar banner content and colors.'),
        'fields' => ['show_topbar', 'topbar_content', 'topbar_bg_color', 'topbar_text_color'],
      ],
      'colors' => [
        'label' => $this->t('Colors & Dimensions'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Background, text colors, height and shadow.'),
        'fields' => ['bg_color', 'text_color', 'height_desktop', 'height_mobile', 'shadow'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
