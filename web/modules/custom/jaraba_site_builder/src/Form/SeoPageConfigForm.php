<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for SEO page configuration.
 */
class SeoPageConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'meta_tags' => [
        'label' => $this->t('Meta Tags'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Title, description, canonical URL, robots and keywords.'),
        'fields' => ['meta_title', 'meta_description', 'canonical_url', 'robots', 'keywords'],
      ],
      'open_graph' => [
        'label' => $this->t('Open Graph'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Open Graph title, description, image and Twitter card.'),
        'fields' => ['og_title', 'og_description', 'og_image', 'twitter_card'],
      ],
      'schema' => [
        'label' => $this->t('Schema.org / JSON-LD'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Structured data type and custom JSON-LD.'),
        'fields' => ['schema_type', 'schema_custom_json'],
      ],
      'hreflang' => [
        'label' => $this->t('Hreflang / Multi-language'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Hreflang configuration for multi-language pages.'),
        'fields' => ['hreflang_config'],
      ],
      'geo' => [
        'label' => $this->t('Geo-Targeting'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Geographic region and position targeting.'),
        'fields' => ['geo_region', 'geo_position'],
      ],
      'audit' => [
        'label' => $this->t('SEO Audit'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Last audit score and date.'),
        'fields' => ['last_audit_score', 'last_audit_date'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'search'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'meta_title' => 70,
      'meta_description' => 160,
      'og_title' => 100,
      'og_description' => 200,
    ];
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
