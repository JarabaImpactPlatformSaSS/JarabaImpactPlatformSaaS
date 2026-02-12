<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global de Insights Hub.
 *
 * PROPOSITO:
 * Permite configurar las credenciales de Google Search Console,
 * los parametros de monitoreo de uptime, la retencion de errores
 * y el intervalo de agregacion de Web Vitals.
 *
 * CAMPOS:
 * - search_console_client_id: Client ID de OAuth2 de Google
 * - search_console_client_secret: Client Secret de OAuth2
 * - uptime_check_interval: Intervalo de verificacion en segundos
 * - uptime_alert_threshold: Checks fallidos antes de alertar
 * - error_retention_days: Dias de retencion de error logs
 * - web_vitals_aggregation_interval: Intervalo de agregacion (hourly/daily/weekly)
 *
 * RUTA:
 * - /admin/config/services/insights-hub
 *
 * CONFIG:
 * - jaraba_insights_hub.settings
 *
 * @package Drupal\jaraba_insights_hub\Form
 */
class InsightsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jaraba_insights_hub.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_insights_hub_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jaraba_insights_hub.settings');

    // --- Seccion: Google Search Console ---
    $form['search_console'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Search Console'),
      '#open' => TRUE,
      '#description' => $this->t('Credenciales OAuth2 para conectar con la API de Google Search Console. Obtener en Google Cloud Console > APIs & Services > Credentials.'),
    ];

    $form['search_console']['search_console_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('search_console_client_id') ?? '',
      '#description' => $this->t('Client ID de la aplicacion OAuth2 de Google Cloud.'),
      '#maxlength' => 255,
      '#placeholder' => 'xxxxx.apps.googleusercontent.com',
    ];

    $form['search_console']['search_console_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('search_console_client_secret') ?? '',
      '#description' => $this->t('Client Secret de la aplicacion OAuth2 de Google Cloud. Se almacena cifrado en la configuracion.'),
      '#maxlength' => 255,
    ];

    // --- Seccion: Uptime Monitoring ---
    $form['uptime'] = [
      '#type' => 'details',
      '#title' => $this->t('Uptime Monitoring'),
      '#open' => TRUE,
      '#description' => $this->t('Parametros de configuracion del sistema de monitoreo de disponibilidad.'),
    ];

    $form['uptime']['uptime_check_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalo de verificacion (segundos)'),
      '#default_value' => $config->get('uptime_check_interval') ?? 300,
      '#min' => 60,
      '#max' => 3600,
      '#step' => 60,
      '#description' => $this->t('Cada cuantos segundos se verifica el estado de los endpoints. Minimo 60s, maximo 3600s (1 hora).'),
      '#required' => TRUE,
    ];

    $form['uptime']['uptime_alert_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de alerta (checks fallidos)'),
      '#default_value' => $config->get('uptime_alert_threshold') ?? 3,
      '#min' => 1,
      '#max' => 10,
      '#description' => $this->t('Numero de checks consecutivos fallidos antes de crear un incidente y enviar alerta. Valor bajo = mas sensible, valor alto = menos falsos positivos.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Error Tracking ---
    $form['errors'] = [
      '#type' => 'details',
      '#title' => $this->t('Error Tracking'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del sistema de captura y retencion de errores.'),
    ];

    $form['errors']['error_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Retencion de errores (dias)'),
      '#default_value' => $config->get('error_retention_days') ?? 90,
      '#min' => 7,
      '#max' => 365,
      '#description' => $this->t('Los errores resueltos o ignorados se eliminan automaticamente despues de este periodo. Los errores abiertos se conservan independientemente de este valor.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Web Vitals ---
    $form['web_vitals'] = [
      '#type' => 'details',
      '#title' => $this->t('Core Web Vitals'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion de la recopilacion y agregacion de metricas de rendimiento real (RUM).'),
    ];

    $form['web_vitals']['web_vitals_aggregation_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Intervalo de agregacion'),
      '#default_value' => $config->get('web_vitals_aggregation_interval') ?? 'daily',
      '#options' => [
        'hourly' => $this->t('Cada hora'),
        'daily' => $this->t('Diario'),
        'weekly' => $this->t('Semanal'),
      ],
      '#description' => $this->t('Frecuencia con la que se agregan las metricas individuales en promedios. Agregacion mas frecuente consume mas recursos pero da datos mas granulares.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validar que el intervalo de uptime sea multiplo de 60.
    $interval = (int) $form_state->getValue('uptime_check_interval');
    if ($interval % 60 !== 0) {
      $form_state->setErrorByName('uptime_check_interval', $this->t('El intervalo debe ser multiplo de 60 segundos.'));
    }

    // Validar Client ID si Client Secret esta presente y viceversa.
    $client_id = $form_state->getValue('search_console_client_id');
    $client_secret = $form_state->getValue('search_console_client_secret');
    if ((!empty($client_id) && empty($client_secret)) || (empty($client_id) && !empty($client_secret))) {
      $form_state->setErrorByName('search_console_client_id', $this->t('Debe proporcionar tanto el Client ID como el Client Secret, o dejar ambos vacios.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jaraba_insights_hub.settings')
      // Search Console.
      ->set('search_console_client_id', $form_state->getValue('search_console_client_id'))
      ->set('search_console_client_secret', $form_state->getValue('search_console_client_secret'))
      // Uptime.
      ->set('uptime_check_interval', (int) $form_state->getValue('uptime_check_interval'))
      ->set('uptime_alert_threshold', (int) $form_state->getValue('uptime_alert_threshold'))
      // Error Tracking.
      ->set('error_retention_days', (int) $form_state->getValue('error_retention_days'))
      // Web Vitals.
      ->set('web_vitals_aggregation_interval', $form_state->getValue('web_vitals_aggregation_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
