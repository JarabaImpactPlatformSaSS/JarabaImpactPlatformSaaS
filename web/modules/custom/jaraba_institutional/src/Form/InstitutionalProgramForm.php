<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Institutional Program entities.
 */
class InstitutionalProgramForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'fields' => ['name', 'program_type', 'program_code', 'funding_entity'],
      ],
      'financing' => [
        'label' => $this->t('Financing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['start_date', 'end_date', 'budget_total', 'budget_executed'],
      ],
      'participants' => [
        'label' => $this->t('Participants'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'fields' => ['participants_target', 'participants_actual'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status', 'reporting_deadlines', 'notes', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'building'];
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
