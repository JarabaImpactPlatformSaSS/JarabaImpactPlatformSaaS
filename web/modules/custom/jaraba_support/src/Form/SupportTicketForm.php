<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Support Ticket add/edit.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 * Sections: content, details, assignment, sla, metadata.
 */
class SupportTicketForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Ticket Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Subject and description of the issue.'),
        'fields' => ['subject', 'description', 'vertical', 'category', 'subcategory'],
      ],
      'details' => [
        'label' => $this->t('Details'),
        'icon' => ['category' => 'ui', 'name' => 'info'],
        'description' => $this->t('Status, priority, and channel information.'),
        'fields' => ['status', 'priority', 'severity', 'channel'],
      ],
      'assignment' => [
        'label' => $this->t('Assignment'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Reporter and agent assignment.'),
        'fields' => ['tenant_id', 'reporter_uid', 'assignee_uid'],
      ],
      'resolution' => [
        'label' => $this->t('Resolution'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Resolution notes and related entity.'),
        'fields' => ['resolution_notes', 'related_entity_type', 'related_entity_id', 'parent_ticket_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'ticket'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'subject' => 255,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
