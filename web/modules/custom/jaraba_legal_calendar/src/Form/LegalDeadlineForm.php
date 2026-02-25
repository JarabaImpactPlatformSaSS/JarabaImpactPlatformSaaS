<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing legal deadlines.
 */
class LegalDeadlineForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'deadline' => [
        'label' => $this->t('Deadline'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Title, type, and due date.'),
        'fields' => ['title', 'deadline_type', 'legal_basis', 'due_date', 'case_id'],
      ],
      'computation' => [
        'label' => $this->t('Computation'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Automatic date computation rules.'),
        'fields' => ['is_computed', 'base_date', 'computation_rule'],
      ],
      'alerts' => [
        'label' => $this->t('Alerts'),
        'icon' => ['category' => 'ui', 'name' => 'bell'],
        'description' => $this->t('Alert configuration and assignment.'),
        'fields' => ['alert_days_before', 'assigned_to'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Deadline status.'),
        'fields' => ['status', 'completed_at', 'notes', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
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
