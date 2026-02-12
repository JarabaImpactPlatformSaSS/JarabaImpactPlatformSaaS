<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de Customer Success.
 *
 * PROPÓSITO:
 * Permite configurar los pesos del health score, umbrales de categoría,
 * frecuencia de cálculo, parámetros de NPS, umbrales de expansión
 * y canales de alertas.
 *
 * LÓGICA:
 * - Pesos: deben sumar exactamente 100.
 * - Umbrales: healthy > neutral > at_risk, valores 0-100.
 * - Intervalos: mínimo 1 hora de cálculo.
 */
class CustomerSuccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_customer_success.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_customer_success_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_customer_success.settings');

    // Pesos del Health Score.
    $form['health_score_weights'] = [
      '#type' => 'details',
      '#title' => $this->t('Health Score Weights'),
      '#description' => $this->t('Weights must sum to 100.'),
      '#open' => TRUE,
    ];

    $weights = $config->get('health_score_weights') ?? [];
    foreach (['engagement', 'adoption', 'satisfaction', 'support', 'growth'] as $component) {
      $form['health_score_weights'][$component] = [
        '#type' => 'number',
        '#title' => $this->t('@component weight', ['@component' => ucfirst($component)]),
        '#default_value' => $weights[$component] ?? 20,
        '#min' => 0,
        '#max' => 100,
        '#required' => TRUE,
      ];
    }

    // Umbrales de categorización.
    $form['health_score_thresholds'] = [
      '#type' => 'details',
      '#title' => $this->t('Health Score Thresholds'),
      '#description' => $this->t('Score ranges: Healthy >= threshold, Neutral >= threshold, At Risk >= threshold, Critical < at_risk threshold.'),
      '#open' => TRUE,
    ];

    $thresholds = $config->get('health_score_thresholds') ?? [];
    $form['health_score_thresholds']['healthy'] = [
      '#type' => 'number',
      '#title' => $this->t('Healthy threshold'),
      '#default_value' => $thresholds['healthy'] ?? 80,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];
    $form['health_score_thresholds']['neutral'] = [
      '#type' => 'number',
      '#title' => $this->t('Neutral threshold'),
      '#default_value' => $thresholds['neutral'] ?? 60,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];
    $form['health_score_thresholds']['at_risk'] = [
      '#type' => 'number',
      '#title' => $this->t('At Risk threshold'),
      '#default_value' => $thresholds['at_risk'] ?? 40,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    // Configuración general.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['calculation_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Calculation interval (hours)'),
      '#default_value' => $config->get('calculation_interval') ?? 24,
      '#min' => 1,
      '#max' => 168,
      '#required' => TRUE,
    ];

    $form['general']['cron_batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Cron batch size'),
      '#default_value' => $config->get('cron_batch_size') ?? 50,
      '#min' => 1,
      '#max' => 500,
      '#required' => TRUE,
    ];

    $form['general']['nps_survey_cooldown'] = [
      '#type' => 'number',
      '#title' => $this->t('NPS survey cooldown (days)'),
      '#description' => $this->t('Minimum days between NPS surveys for the same tenant.'),
      '#default_value' => $config->get('nps_survey_cooldown') ?? 90,
      '#min' => 7,
      '#max' => 365,
      '#required' => TRUE,
    ];

    $form['general']['expansion_usage_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Expansion usage threshold (%)'),
      '#description' => $this->t('Usage percentage that triggers expansion signal.'),
      '#default_value' => $config->get('expansion_usage_threshold') ?? 80,
      '#min' => 50,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['general']['playbook_max_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Playbook max retries per step'),
      '#default_value' => $config->get('playbook_max_retries') ?? 3,
      '#min' => 0,
      '#max' => 10,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar que los pesos sumen 100.
    $weights = $form_state->getValue('health_score_weights');
    if (is_array($weights)) {
      $total = array_sum($weights);
      if ($total !== 100) {
        $form_state->setErrorByName('health_score_weights', $this->t('Health score weights must sum to 100. Current total: @total.', [
          '@total' => $total,
        ]));
      }
    }

    // Validar orden de umbrales.
    $thresholds = $form_state->getValue('health_score_thresholds');
    if (is_array($thresholds)) {
      if ($thresholds['healthy'] <= $thresholds['neutral']) {
        $form_state->setErrorByName('health_score_thresholds][healthy', $this->t('Healthy threshold must be greater than Neutral.'));
      }
      if ($thresholds['neutral'] <= $thresholds['at_risk']) {
        $form_state->setErrorByName('health_score_thresholds][neutral', $this->t('Neutral threshold must be greater than At Risk.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_customer_success.settings')
      ->set('health_score_weights', $form_state->getValue('health_score_weights'))
      ->set('health_score_thresholds', $form_state->getValue('health_score_thresholds'))
      ->set('calculation_interval', (int) $form_state->getValue('calculation_interval'))
      ->set('cron_batch_size', (int) $form_state->getValue('cron_batch_size'))
      ->set('nps_survey_cooldown', (int) $form_state->getValue('nps_survey_cooldown'))
      ->set('expansion_usage_threshold', (int) $form_state->getValue('expansion_usage_threshold'))
      ->set('playbook_max_retries', (int) $form_state->getValue('playbook_max_retries'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
