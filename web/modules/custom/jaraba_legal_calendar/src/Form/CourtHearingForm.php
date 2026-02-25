<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing court hearings.
 */
class CourtHearingForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'hearing' => [
        'label' => $this->t('Hearing'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Hearing title, type, and schedule.'),
        'fields' => ['title', 'hearing_type', 'case_id', 'scheduled_at', 'estimated_duration_minutes'],
      ],
      'location' => [
        'label' => $this->t('Location'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'description' => $this->t('Court, courtroom, and virtual link.'),
        'fields' => ['court', 'courtroom', 'address', 'is_virtual', 'virtual_url'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Hearing status and outcome.'),
        'fields' => ['status', 'outcome', 'notes', 'tenant_id'],
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
