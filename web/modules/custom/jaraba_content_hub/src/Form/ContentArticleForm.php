<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
        'fields' => ['title', 'slug', 'excerpt', 'layout_mode', 'body', 'answer_capsule', 'featured_image'],
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
   *
   * GAP-AUD-009: Enable Inline AI sparkle buttons for key content fields.
   */
  protected function getInlineAiFields(): array {
    return ['title', 'excerpt', 'answer_capsule', 'seo_title', 'seo_description'];
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
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $entity */
    $entity = $this->getEntity();

    // Enlace al Canvas Editor (solo en edit form con artículo existente).
    if (!$entity->isNew() && $entity->isCanvasMode()) {
      $canvasUrl = Url::fromRoute('jaraba_content_hub.article.canvas_editor', [
        'content_article' => $entity->id(),
      ]);

      $form['canvas_editor_link'] = [
        '#type' => 'markup',
        '#markup' => '<div class="article-canvas-editor-link" style="margin-bottom: 1rem;">'
          . '<a href="' . $canvasUrl->toString() . '" class="button button--primary" target="_blank">'
          . '✦ ' . $this->t('Open Canvas Editor')
          . '</a>'
          . '<p class="description">' . $this->t('This article uses the visual Canvas Editor. Click to open the drag-and-drop editor.') . '</p>'
          . '</div>',
        '#weight' => -5,
      ];
    }

    // Cuando layout_mode = 'canvas', el body no es estrictamente necesario
    // porque el contenido se gestiona desde el Canvas Editor.
    // Eliminamos la restricción required para evitar errores de validación.
    if (isset($form['body']) && $entity->isCanvasMode()) {
      $form['body']['widget'][0]['#required'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $entity */
    $entity = $this->getEntity();

    // En modo canvas, si body está vacío, establecer un placeholder
    // para evitar errores con el campo required en baseFieldDefinitions.
    if ($entity->isCanvasMode() && $entity->get('body')->isEmpty()) {
      $entity->set('body', $this->t('[Content managed by Canvas Editor]'));
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
