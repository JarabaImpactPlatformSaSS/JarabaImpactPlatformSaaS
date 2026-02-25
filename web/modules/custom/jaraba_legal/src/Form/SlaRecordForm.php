<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para SlaRecord.
 *
 * En producción, los registros SLA se generan automáticamente
 * vía SlaCalculatorService. Este formulario permite la gestión manual.
 */
class SlaRecordForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant this SLA record belongs to.'),
        'fields' => ['tenant_id'],
      ],
      'period' => [
        'label' => $this->t('Measurement Period'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Start and end timestamps of the measurement period.'),
        'fields' => ['period_start', 'period_end'],
      ],
      'metrics' => [
        'label' => $this->t('SLA Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Uptime percentage, target, and downtime metrics.'),
        'fields' => ['uptime_percentage', 'target_percentage', 'downtime_minutes'],
      ],
      'credits' => [
        'label' => $this->t('Credits'),
        'icon' => ['category' => 'commerce', 'name' => 'commerce'],
        'description' => $this->t('Credit percentage and application status for SLA breaches.'),
        'fields' => ['credit_percentage', 'credit_applied'],
      ],
      'incidents' => [
        'label' => $this->t('Incidents'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Number of availability incidents during the period.'),
        'fields' => ['incident_count'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'scale'];
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
