<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo VeriFactu.
 *
 * Permite configurar: endpoints AEAT (produccion/pruebas), control de flujo,
 * parametros de batch de remision, reintentos, datos del software para
 * la declaracion responsable, y verificacion de integridad de cadena.
 *
 * Ruta: /admin/config/jaraba/verifactu
 */
class VeriFactuSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_verifactu.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_verifactu_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // =================================================================
    // AEAT ENDPOINTS
    // =================================================================
    $form['aeat_endpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('AEAT Endpoints'),
      '#open' => TRUE,
      '#description' => $this->t('WSDL endpoints for the AEAT VeriFactu SOAP service.'),
    ];

    $form['aeat_endpoints']['aeat_endpoint_production'] = [
      '#type' => 'url',
      '#title' => $this->t('Production WSDL'),
      '#default_value' => $config->get('aeat_endpoint_production') ?? '',
      '#required' => TRUE,
      '#maxlength' => 500,
    ];

    $form['aeat_endpoints']['aeat_endpoint_testing'] = [
      '#type' => 'url',
      '#title' => $this->t('Testing WSDL'),
      '#default_value' => $config->get('aeat_endpoint_testing') ?? '',
      '#required' => TRUE,
      '#maxlength' => 500,
    ];

    // =================================================================
    // REMISION & FLOW CONTROL
    // =================================================================
    $form['remision'] = [
      '#type' => 'details',
      '#title' => $this->t('Remision & Flow Control'),
      '#open' => TRUE,
    ];

    $form['remision']['flow_control_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Flow control interval (seconds)'),
      '#description' => $this->t('Minimum seconds between AEAT remision batches.'),
      '#default_value' => $config->get('flow_control_seconds') ?? 60,
      '#min' => 10,
      '#max' => 3600,
      '#required' => TRUE,
    ];

    $form['remision']['max_records_per_batch'] = [
      '#type' => 'number',
      '#title' => $this->t('Max records per batch'),
      '#description' => $this->t('Maximum number of records per remision batch (AEAT limit: 1000).'),
      '#default_value' => $config->get('max_records_per_batch') ?? 1000,
      '#min' => 1,
      '#max' => 1000,
      '#required' => TRUE,
    ];

    $form['remision']['max_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Max retry attempts'),
      '#description' => $this->t('Maximum retry attempts for failed AEAT communication.'),
      '#default_value' => $config->get('max_retries') ?? 5,
      '#min' => 0,
      '#max' => 20,
      '#required' => TRUE,
    ];

    $form['remision']['retry_backoff_base_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Retry backoff base (seconds)'),
      '#description' => $this->t('Base seconds for exponential backoff between retries.'),
      '#default_value' => $config->get('retry_backoff_base_seconds') ?? 30,
      '#min' => 5,
      '#max' => 600,
      '#required' => TRUE,
    ];

    // =================================================================
    // SOFTWARE IDENTIFICATION (DECLARACION RESPONSABLE)
    // =================================================================
    $form['software'] = [
      '#type' => 'details',
      '#title' => $this->t('Software Identification'),
      '#open' => TRUE,
      '#description' => $this->t('Software data for the AEAT responsible declaration (declaracion responsable).'),
    ];

    $form['software']['software_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Software ID'),
      '#default_value' => $config->get('software_id') ?? 'JarabaImpactPlatform',
      '#required' => TRUE,
      '#maxlength' => 30,
    ];

    $form['software']['software_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Software version'),
      '#default_value' => $config->get('software_version') ?? '1.0.0',
      '#required' => TRUE,
      '#maxlength' => 20,
    ];

    $form['software']['software_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Software name'),
      '#default_value' => $config->get('software_name') ?? 'Jaraba Impact Platform SaaS',
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['software']['software_developer_nif'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Developer NIF'),
      '#description' => $this->t('NIF/CIF of the software developer company.'),
      '#default_value' => $config->get('software_developer_nif') ?? '',
      '#maxlength' => 9,
    ];

    // =================================================================
    // INTEGRITY & MONITORING
    // =================================================================
    $form['integrity'] = [
      '#type' => 'details',
      '#title' => $this->t('Integrity & Monitoring'),
      '#open' => TRUE,
    ];

    $form['integrity']['enable_daily_integrity_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable daily hash chain integrity check'),
      '#description' => $this->t('Run automatic SHA-256 chain verification via cron.'),
      '#default_value' => $config->get('enable_daily_integrity_check') ?? TRUE,
    ];

    $form['integrity']['daily_integrity_check_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Integrity check time (UTC)'),
      '#description' => $this->t('HH:MM format. Recommended: off-peak hours.'),
      '#default_value' => $config->get('daily_integrity_check_time') ?? '03:00',
      '#maxlength' => 5,
    ];

    $form['integrity']['certificate_expiry_alert_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Certificate expiry alert (days)'),
      '#description' => $this->t('Alert threshold in days before certificate expiration.'),
      '#default_value' => $config->get('certificate_expiry_alert_days') ?? 30,
      '#min' => 7,
      '#max' => 180,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $time = $form_state->getValue('daily_integrity_check_time');
    if ($time && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
      $form_state->setErrorByName('daily_integrity_check_time', $this->t('Invalid time format. Use HH:MM (24-hour, e.g. 03:00).'));
    }

    $nif = $form_state->getValue('software_developer_nif');
    if ($nif && !preg_match('/^[A-Z0-9]{8,9}$/', $nif)) {
      $form_state->setErrorByName('software_developer_nif', $this->t('Invalid NIF/CIF format. Must be 8-9 alphanumeric characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    $config->set('aeat_endpoint_production', $form_state->getValue('aeat_endpoint_production'));
    $config->set('aeat_endpoint_testing', $form_state->getValue('aeat_endpoint_testing'));
    $config->set('flow_control_seconds', (int) $form_state->getValue('flow_control_seconds'));
    $config->set('max_records_per_batch', (int) $form_state->getValue('max_records_per_batch'));
    $config->set('max_retries', (int) $form_state->getValue('max_retries'));
    $config->set('retry_backoff_base_seconds', (int) $form_state->getValue('retry_backoff_base_seconds'));
    $config->set('software_id', $form_state->getValue('software_id'));
    $config->set('software_version', $form_state->getValue('software_version'));
    $config->set('software_name', $form_state->getValue('software_name'));
    $config->set('software_developer_nif', $form_state->getValue('software_developer_nif'));
    $config->set('enable_daily_integrity_check', (bool) $form_state->getValue('enable_daily_integrity_check'));
    $config->set('daily_integrity_check_time', $form_state->getValue('daily_integrity_check_time'));
    $config->set('certificate_expiry_alert_days', (int) $form_state->getValue('certificate_expiry_alert_days'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
