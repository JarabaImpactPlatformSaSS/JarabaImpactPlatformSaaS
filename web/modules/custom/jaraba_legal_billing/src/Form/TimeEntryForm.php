<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de Entradas de Tiempo.
 */
class TimeEntryForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant, case and professional assignment.'),
        'fields' => ['tenant_id', 'case_id', 'user_id'],
      ],
      'time_details' => [
        'label' => $this->t('Time Details'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Description, date and duration of the time entry.'),
        'fields' => ['description', 'date', 'duration_minutes'],
      ],
      'billing' => [
        'label' => $this->t('Billing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Billing rate, billable status and invoice link.'),
        'fields' => ['billing_rate', 'is_billable', 'invoice_id'],
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
