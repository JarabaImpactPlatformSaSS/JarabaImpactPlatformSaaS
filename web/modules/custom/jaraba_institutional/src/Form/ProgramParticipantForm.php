<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Program Participant entities.
 */
class ProgramParticipantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'enrollment' => [
        'label' => $this->t('Enrollment'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'fields' => ['program_id', 'user_id', 'enrollment_date'],
      ],
      'tracking' => [
        'label' => $this->t('Tracking'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['hours_orientation', 'hours_training', 'certifications_obtained'],
      ],
      'outcome' => [
        'label' => $this->t('Outcome'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'fields' => ['employment_outcome', 'employment_date', 'exit_date', 'exit_reason'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status', 'notes', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
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
