<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para HomepageContent.
 */
class HomepageContentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'hero' => [
        'label' => $this->t('Hero Section'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Hero eyebrow, title, subtitle, and CTAs.'),
        'fields' => [
          'title',
          'hero_eyebrow',
          'hero_title',
          'hero_subtitle',
          'hero_cta_primary_text',
          'hero_cta_primary_url',
          'hero_cta_secondary_text',
          'hero_cta_secondary_url',
          'hero_scroll_text',
        ],
      ],
      'features' => [
        'label' => $this->t('Features'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Feature cards referenced by this homepage.'),
        'fields' => ['features'],
      ],
      'stats' => [
        'label' => $this->t('Statistics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Stat items referenced by this homepage.'),
        'fields' => ['stats'],
      ],
      'intentions' => [
        'label' => $this->t('Intentions'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'description' => $this->t('Intention cards referenced by this homepage.'),
        'fields' => ['intentions'],
      ],
      'seo' => [
        'label' => $this->t('SEO / Open Graph'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Meta tags for search engines and social networks.'),
        'fields' => ['meta_title', 'meta_description', 'og_image'],
      ],
      'config' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Tenant assignment.'),
        'fields' => ['tenant_id'],
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
