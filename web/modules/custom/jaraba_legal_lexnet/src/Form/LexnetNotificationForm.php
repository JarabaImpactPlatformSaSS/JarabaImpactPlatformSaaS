<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing LexNET notifications.
 */
class LexnetNotificationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'notification' => [
        'label' => $this->t('Notification'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'description' => $this->t('Notification type, court, and subject.'),
        'fields' => ['notification_type', 'external_id', 'court', 'procedure_number', 'subject', 'case_id'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Reception, acknowledgement, and deadlines.'),
        'fields' => ['received_at', 'acknowledged_at', 'deadline_days', 'computed_deadline'],
      ],
      'attachments' => [
        'label' => $this->t('Attachments'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Attached documents and raw data.'),
        'fields' => ['attachments', 'raw_data'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Notification status and tenant.'),
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'bell'];
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
