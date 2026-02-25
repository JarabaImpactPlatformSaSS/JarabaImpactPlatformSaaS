<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar experimentos A/B.
 */
class ABExperimentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Experiment name and machine name.'),
        'fields' => ['label', 'machine_name'],
      ],
      'experiment_config' => [
        'label' => $this->t('Experiment Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Type, hypothesis and metrics for the experiment.'),
        'fields' => ['experiment_type', 'hypothesis', 'primary_metric', 'secondary_metrics'],
      ],
      'targeting' => [
        'label' => $this->t('Targeting'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Audience segmentation and traffic allocation.'),
        'fields' => ['target_audience', 'audience_segment', 'traffic_percentage'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Experiment lifecycle status and winner.'),
        'fields' => ['status', 'winner_variant'],
      ],
      'statistical_config' => [
        'label' => $this->t('Statistical Configuration'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Confidence threshold, sample size and runtime requirements.'),
        'fields' => ['confidence_threshold', 'minimum_sample_size', 'minimum_runtime_days'],
      ],
      'scheduling' => [
        'label' => $this->t('Scheduling'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Start and end dates, auto-completion settings.'),
        'fields' => ['start_date', 'end_date', 'auto_complete'],
      ],
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Multi-tenant assignment.'),
        'fields' => ['tenant_id'],
      ],
      'results_cache' => [
        'label' => $this->t('Results Cache'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Aggregated visitor and conversion counts.'),
        'fields' => ['total_visitors', 'total_conversions'],
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
    $entity = $this->entity;

    // Auto-generar machine_name desde el label si esta vacio.
    if (empty($entity->get('machine_name')->value) && !empty($entity->label())) {
      $machine_name = $this->generateMachineName($entity->label());
      $entity->set('machine_name', $machine_name);
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

  /**
   * Genera un machine_name a partir de un label.
   *
   * @param string $label
   *   El nombre del experimento.
   *
   * @return string
   *   El machine_name generado.
   */
  protected function generateMachineName(string $label): string {
    $name = mb_strtolower($label);
    $name = preg_replace('/[áàäâ]/u', 'a', $name);
    $name = preg_replace('/[éèëê]/u', 'e', $name);
    $name = preg_replace('/[íìïî]/u', 'i', $name);
    $name = preg_replace('/[óòöô]/u', 'o', $name);
    $name = preg_replace('/[úùüû]/u', 'u', $name);
    $name = preg_replace('/ñ/u', 'n', $name);
    $name = preg_replace('/[^a-z0-9\s_-]/', '', $name);
    $name = preg_replace('/[\s-]+/', '_', $name);
    return trim($name, '_');
  }

}
