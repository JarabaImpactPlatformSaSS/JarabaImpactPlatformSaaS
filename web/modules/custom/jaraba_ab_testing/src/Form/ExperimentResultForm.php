<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar resultados de experimento.
 */
class ExperimentResultForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'reference' => [
        'label' => $this->t('Experiment Reference'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Parent experiment assignment.'),
        'fields' => ['experiment_id'],
      ],
      'identification' => [
        'label' => $this->t('Variant & Metric'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Variant key and metric name identification.'),
        'fields' => ['variant_id', 'metric_name'],
      ],
      'statistics' => [
        'label' => $this->t('Statistical Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Sample size, mean, standard deviation and confidence interval.'),
        'fields' => ['sample_size', 'mean', 'std_dev', 'confidence_interval'],
      ],
      'significance' => [
        'label' => $this->t('Significance'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('P-value, significance flag and lift metrics.'),
        'fields' => ['p_value', 'is_significant', 'lift'],
      ],
      'calculation' => [
        'label' => $this->t('Calculation'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Timestamp of the result calculation.'),
        'fields' => ['calculated_at'],
      ],
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Multi-tenant assignment.'),
        'fields' => ['tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
