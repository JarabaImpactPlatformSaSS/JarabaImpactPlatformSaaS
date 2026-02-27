<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Ticket Message add/edit.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class TicketMessageForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'message' => [
        'label' => $this->t('Message'),
        'icon' => ['category' => 'ui', 'name' => 'chat'],
        'description' => $this->t('Write your message.'),
        'fields' => ['body', 'is_internal_note'],
      ],
      'context' => [
        'label' => $this->t('Context'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Message metadata.'),
        'fields' => ['ticket_id', 'author_uid', 'author_type'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'chat'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    return $result;
  }

}
