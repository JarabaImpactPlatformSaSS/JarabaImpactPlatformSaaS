<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing blog posts.
 */
class BlogPostForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Main content of the blog post.'),
        'fields' => ['title', 'slug', 'excerpt', 'body', 'featured_image', 'featured_image_alt'],
      ],
      'taxonomy' => [
        'label' => $this->t('Taxonomy'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Categorization and authorship.'),
        'fields' => ['category_id', 'tags', 'author_id'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Publication schedule and visibility.'),
        'fields' => ['status', 'published_at', 'scheduled_at', 'is_featured', 'reading_time'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Search engine optimization settings.'),
        'fields' => ['meta_title', 'meta_description', 'og_image', 'schema_type'],
        'charLimits' => ['meta_title' => 70, 'meta_description' => 160, 'excerpt' => 300],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'edit'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
