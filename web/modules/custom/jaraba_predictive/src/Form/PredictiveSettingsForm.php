<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo de predicciones.
 *
 * Estructura: ConfigFormBase con 4 fieldsets: general, churn weights,
 *   lead scoring y forecast.
 *
 * Logica: Gestiona configuracion global incluyendo umbral de alerta,
 *   version del modelo, pesos de churn, pesos de lead scoring y
 *   parametros de forecasting.
 */
class PredictiveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * Estructura: Define los nombres de configuracion editables.
   * Logica: Solo expone jaraba_predictive.settings para edicion.
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_predictive.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Retorna el ID unico del formulario.
   * Logica: Convencional para ConfigFormBase del ecosistema.
   */
  public function getFormId(): string {
    return 'jaraba_predictive_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 4 fieldsets tematicos.
   * Logica: Cada fieldset agrupa parametros relacionados con
   *   configuracion general, pesos de churn, lead scoring y forecast.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_predictive.settings');

    // --- Fieldset 1: General ---
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion general'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $form['general']['alert_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de alerta de riesgo'),
      '#description' => $this->t('Puntuacion de riesgo (0-100) a partir de la cual se genera una alerta.'),
      '#default_value' => $config->get('alert_threshold') ?? 70,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['general']['model_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version del modelo activo'),
      '#description' => $this->t('Version del modelo predictivo utilizado por defecto.'),
      '#options' => [
        'heuristic_v1' => $this->t('Heuristico v1'),
        'ml_v1' => $this->t('ML v1'),
        'ml_v2' => $this->t('ML v2'),
      ],
      '#default_value' => $config->get('model_version') ?? 'heuristic_v1',
      '#required' => TRUE,
    ];

    $form['general']['python_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar motor Python'),
      '#description' => $this->t('Activar la ejecucion de modelos ML via Python para predicciones avanzadas.'),
      '#default_value' => $config->get('python_enabled') ?? FALSE,
    ];

    $form['general']['python_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ruta al binario de Python 3'),
      '#description' => $this->t('Ruta absoluta al ejecutable de Python 3 en el servidor.'),
      '#default_value' => $config->get('python_path') ?? '/usr/bin/python3',
      '#maxlength' => 255,
    ];

    $form['general']['scripts_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ruta a los scripts de Python'),
      '#description' => $this->t('Ruta relativa a los scripts de Python del modulo.'),
      '#default_value' => $config->get('scripts_path') ?? 'modules/custom/jaraba_predictive/scripts',
      '#maxlength' => 255,
    ];

    $form['general']['feature_cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('TTL de cache de features (segundos)'),
      '#description' => $this->t('Tiempo de vida de la cache de features en segundos.'),
      '#default_value' => $config->get('feature_cache_ttl') ?? 3600,
      '#min' => 60,
      '#max' => 86400,
      '#required' => TRUE,
    ];

    // --- Fieldset 2: Churn Weights ---
    $form['churn_weights'] = [
      '#type' => 'details',
      '#title' => $this->t('Pesos del modelo de churn'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $form['churn_weights']['churn_inactivity'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de inactividad'),
      '#description' => $this->t('Peso asignado al factor de inactividad del usuario (0.00 a 1.00).'),
      '#default_value' => $config->get('churn_weights.inactivity') ?? 0.3,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['churn_weights']['churn_payment_failures'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de fallos de pago'),
      '#description' => $this->t('Peso asignado al factor de fallos de pago (0.00 a 1.00).'),
      '#default_value' => $config->get('churn_weights.payment_failures') ?? 0.25,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['churn_weights']['churn_support_tickets'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de tickets de soporte'),
      '#description' => $this->t('Peso asignado al factor de tickets de soporte (0.00 a 1.00).'),
      '#default_value' => $config->get('churn_weights.support_tickets') ?? 0.15,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['churn_weights']['churn_feature_adoption'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de adopcion de funcionalidades'),
      '#description' => $this->t('Peso asignado al factor de adopcion de funcionalidades (0.00 a 1.00).'),
      '#default_value' => $config->get('churn_weights.feature_adoption') ?? 0.2,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['churn_weights']['churn_contract_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de antiguedad del contrato'),
      '#description' => $this->t('Peso asignado al factor de antiguedad del contrato (0.00 a 1.00).'),
      '#default_value' => $config->get('churn_weights.contract_age') ?? 0.1,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    // --- Fieldset 3: Lead Scoring ---
    $form['lead_scoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Pesos del modelo de lead scoring'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $form['lead_scoring']['lead_engagement_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de engagement'),
      '#description' => $this->t('Peso asignado al factor de engagement del lead (0.00 a 1.00).'),
      '#default_value' => $config->get('lead_scoring.engagement_weight') ?? 0.4,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['lead_scoring']['lead_activation_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de activacion'),
      '#description' => $this->t('Peso asignado al factor de activacion del lead (0.00 a 1.00).'),
      '#default_value' => $config->get('lead_scoring.activation_weight') ?? 0.35,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['lead_scoring']['lead_intent_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso de intencionalidad'),
      '#description' => $this->t('Peso asignado al factor de intencionalidad del lead (0.00 a 1.00).'),
      '#default_value' => $config->get('lead_scoring.intent_weight') ?? 0.25,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    // --- Fieldset 4: Forecast ---
    $form['forecast'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion de forecasting'),
      '#open' => TRUE,
      '#weight' => -5,
    ];

    $form['forecast']['forecast_default_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Periodo por defecto'),
      '#description' => $this->t('Granularidad temporal por defecto para las previsiones.'),
      '#options' => [
        'monthly' => $this->t('Mensual'),
        'quarterly' => $this->t('Trimestral'),
        'yearly' => $this->t('Anual'),
      ],
      '#default_value' => $config->get('forecast.default_period') ?? 'monthly',
      '#required' => TRUE,
    ];

    $form['forecast']['forecast_confidence_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalo de confianza'),
      '#description' => $this->t('Nivel de confianza para los intervalos de prediccion (0.00 a 1.00).'),
      '#default_value' => $config->get('forecast.confidence_interval') ?? 0.8,
      '#min' => 0.5,
      '#max' => 0.99,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['forecast']['forecast_min_data_points'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimo de puntos de datos'),
      '#description' => $this->t('Numero minimo de puntos de datos necesarios para generar un forecast.'),
      '#default_value' => $config->get('forecast.min_data_points') ?? 12,
      '#min' => 3,
      '#max' => 365,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Persiste los valores del formulario en configuracion.
   * Logica: Guarda cada campo en jaraba_predictive.settings usando
   *   los valores del FormState.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_predictive.settings')
      ->set('alert_threshold', (int) $form_state->getValue('alert_threshold'))
      ->set('model_version', $form_state->getValue('model_version'))
      ->set('python_enabled', (bool) $form_state->getValue('python_enabled'))
      ->set('python_path', $form_state->getValue('python_path'))
      ->set('scripts_path', $form_state->getValue('scripts_path'))
      ->set('feature_cache_ttl', (int) $form_state->getValue('feature_cache_ttl'))
      ->set('churn_weights.inactivity', (float) $form_state->getValue('churn_inactivity'))
      ->set('churn_weights.payment_failures', (float) $form_state->getValue('churn_payment_failures'))
      ->set('churn_weights.support_tickets', (float) $form_state->getValue('churn_support_tickets'))
      ->set('churn_weights.feature_adoption', (float) $form_state->getValue('churn_feature_adoption'))
      ->set('churn_weights.contract_age', (float) $form_state->getValue('churn_contract_age'))
      ->set('lead_scoring.engagement_weight', (float) $form_state->getValue('lead_engagement_weight'))
      ->set('lead_scoring.activation_weight', (float) $form_state->getValue('lead_activation_weight'))
      ->set('lead_scoring.intent_weight', (float) $form_state->getValue('lead_intent_weight'))
      ->set('forecast.default_period', $form_state->getValue('forecast_default_period'))
      ->set('forecast.confidence_interval', (float) $form_state->getValue('forecast_confidence_interval'))
      ->set('forecast.min_data_points', (int) $form_state->getValue('forecast_min_data_points'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
