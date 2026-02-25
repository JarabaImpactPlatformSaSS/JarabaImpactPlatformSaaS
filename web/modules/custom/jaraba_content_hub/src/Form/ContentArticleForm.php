<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form controller for the ContentArticle entity.
 *
 * Extends PremiumEntityFormBase to get glassmorphism sections, pill
 * navigation, character counters, dirty-state tracking, and progress bar.
 */
class ContentArticleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Title, body, and featured image.'),
        'fields' => ['title', 'slug', 'excerpt', 'body', 'answer_capsule', 'featured_image'],
      ],
      'taxonomy' => [
        'label' => $this->t('Taxonomy'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Categories and tags.'),
        'fields' => ['category'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Status, publish date, and author.'),
        'fields' => ['status', 'publish_date', 'author'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Search engine optimization fields.'),
        'fields' => ['seo_title', 'seo_description'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'icon' => ['category' => 'business', 'name' => 'clipboard'],
        'description' => $this->t('AI generation flag, reading time, and engagement.'),
        'fields' => ['ai_generated', 'reading_time', 'engagement_score'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'article'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'seo_title' => 70,
      'seo_description' => 160,
      'answer_capsule' => 200,
      'excerpt' => 300,
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
