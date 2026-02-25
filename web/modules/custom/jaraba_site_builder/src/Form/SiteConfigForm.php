<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for site configuration.
 */
class SiteConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Site Identity'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Site name, tagline, logo and favicon.'),
        'fields' => ['site_name', 'site_tagline', 'site_logo', 'site_favicon'],
      ],
      'pages' => [
        'label' => $this->t('Special Pages'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Homepage, blog index and error pages.'),
        'fields' => ['homepage_id', 'blog_index_id', 'error_404_id'],
      ],
      'seo' => [
        'label' => $this->t('SEO & Analytics'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Meta title suffix, OG image and tracking IDs.'),
        'fields' => ['meta_title_suffix', 'default_og_image', 'google_analytics_id', 'google_tag_manager_id'],
      ],
      'contact' => [
        'label' => $this->t('Contact Information'),
        'icon' => ['category' => 'social', 'name' => 'email'],
        'description' => $this->t('Email, phone, address and coordinates.'),
        'fields' => ['contact_email', 'contact_phone', 'contact_address', 'contact_coordinates'],
      ],
      'social' => [
        'label' => $this->t('Social Networks'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Social media links.'),
        'fields' => ['social_links'],
      ],
      'legal' => [
        'label' => $this->t('Legal Pages'),
        'icon' => ['category' => 'business', 'name' => 'document'],
        'description' => $this->t('Privacy policy, terms and cookies pages.'),
        'fields' => ['privacy_policy_id', 'terms_conditions_id', 'cookies_policy_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirect('jaraba_site_builder.settings');
    return $result;
  }

}
