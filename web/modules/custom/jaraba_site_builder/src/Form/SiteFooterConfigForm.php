<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site footer configuration.
 */
class SiteFooterConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'layout' => [
        'label' => $this->t('Layout & Type'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Footer type and column configuration.'),
        'fields' => ['footer_type', 'columns_config'],
      ],
      'branding' => [
        'label' => $this->t('Logo & Description'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Footer logo and descriptive text.'),
        'fields' => ['logo_id', 'show_logo', 'description'],
      ],
      'social' => [
        'label' => $this->t('Social Networks'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Social media icons visibility and position.'),
        'fields' => ['show_social', 'social_position'],
      ],
      'newsletter' => [
        'label' => $this->t('Newsletter'),
        'icon' => ['category' => 'social', 'name' => 'email'],
        'description' => $this->t('Newsletter subscription form settings.'),
        'fields' => ['show_newsletter', 'newsletter_title', 'newsletter_placeholder', 'newsletter_cta'],
      ],
      'footer_cta' => [
        'label' => $this->t('Call to Action'),
        'icon' => ['category' => 'actions', 'name' => 'click'],
        'description' => $this->t('CTA title, subtitle and button settings.'),
        'fields' => ['cta_title', 'cta_subtitle', 'cta_button_text', 'cta_button_url'],
      ],
      'legal' => [
        'label' => $this->t('Copyright & Legal'),
        'icon' => ['category' => 'business', 'name' => 'document'],
        'description' => $this->t('Copyright text and legal links.'),
        'fields' => ['copyright_text', 'show_legal_links'],
      ],
      'colors' => [
        'label' => $this->t('Colors'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Background, text and accent colors.'),
        'fields' => ['bg_color', 'text_color', 'accent_color'],
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
