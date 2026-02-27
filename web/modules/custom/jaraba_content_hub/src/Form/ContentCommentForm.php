<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para la entidad ContentComment.
 *
 * PREMIUM-FORMS-PATTERN-001: Patron A (Simple).
 */
class ContentCommentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'comment' => [
        'label' => $this->t('Comentario'),
        'icon' => ['category' => 'ui', 'name' => 'message-circle'],
        'description' => $this->t('Contenido del comentario.'),
        'fields' => [
          'article_id',
          'parent_id',
          'body',
          'author_name',
          'author_email',
        ],
      ],
      'moderation' => [
        'label' => $this->t('Moderacion'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Estado de moderacion y tenant.'),
        'fields' => [
          'review_status',
          'tenant_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'message-circle'];
  }

}
