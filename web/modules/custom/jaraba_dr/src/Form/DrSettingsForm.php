<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo de Disaster Recovery.
 *
 * Permite configurar: frecuencia de verificacion de backups, modo de failover,
 * status page publica, programacion de tests y canales de notificacion.
 *
 * Ruta: /admin/config/jaraba/dr
 */
class DrSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_dr.settings';

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
    return 'jaraba_dr_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // =================================================================
    // BACKUPS -- Configuracion de verificacion de backups
    // =================================================================
    $form['backups'] = [
      '#type' => 'details',
      '#title' => $this->t('Verificacion de backups'),
      '#open' => TRUE,
    ];

    $form['backups']['backup_verification_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frecuencia de verificacion'),
      '#description' => $this->t('Con que frecuencia se verifican automaticamente los backups.'),
      '#options' => [
        'hourly' => $this->t('Cada hora'),
        'daily' => $this->t('Diaria'),
        'weekly' => $this->t('Semanal'),
      ],
      '#default_value' => $config->get('backup_verification_frequency') ?? 'daily',
      '#required' => TRUE,
    ];

    $form['backups']['backup_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Dias de retencion de backups'),
      '#description' => $this->t('Numero de dias que se conservan los backups antes de su eliminacion.'),
      '#default_value' => $config->get('backup_retention_days') ?? 30,
      '#min' => 7,
      '#max' => 365,
      '#required' => TRUE,
    ];

    // =================================================================
    // FAILOVER -- Configuracion de failover
    // =================================================================
    $form['failover'] = [
      '#type' => 'details',
      '#title' => $this->t('Failover'),
      '#open' => TRUE,
    ];

    $form['failover']['failover_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Modo de failover'),
      '#description' => $this->t('Manual requiere intervencion humana. Automatico ejecuta failover al detectar caida.'),
      '#options' => [
        'manual' => $this->t('Manual'),
        'automatic' => $this->t('Automatico'),
      ],
      '#default_value' => $config->get('failover_mode') ?? 'manual',
      '#required' => TRUE,
    ];

    // =================================================================
    // STATUS PAGE -- Configuracion de pagina de estado
    // =================================================================
    $form['status_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagina de estado'),
      '#open' => TRUE,
    ];

    $form['status_page']['status_page_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagina de estado publica'),
      '#description' => $this->t('Si se activa, la pagina de estado es accesible sin autenticacion.'),
      '#default_value' => $config->get('status_page_public') ?? TRUE,
    ];

    $form['status_page']['status_page_refresh_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Segundos de refresco'),
      '#description' => $this->t('Intervalo de auto-refresco de la pagina de estado en segundos.'),
      '#default_value' => $config->get('status_page_refresh_seconds') ?? 60,
      '#min' => 10,
      '#max' => 300,
      '#required' => TRUE,
    ];

    // =================================================================
    // TESTS DR -- Programacion de pruebas
    // =================================================================
    $form['tests'] = [
      '#type' => 'details',
      '#title' => $this->t('Pruebas DR'),
      '#open' => TRUE,
    ];

    $form['tests']['dr_test_schedule'] = [
      '#type' => 'select',
      '#title' => $this->t('Periodicidad de tests DR'),
      '#description' => $this->t('Con que frecuencia se programan las pruebas de Disaster Recovery.'),
      '#options' => [
        'monthly' => $this->t('Mensual'),
        'quarterly' => $this->t('Trimestral'),
        'biannual' => $this->t('Semestral'),
        'annual' => $this->t('Anual'),
      ],
      '#default_value' => $config->get('dr_test_schedule') ?? 'quarterly',
      '#required' => TRUE,
    ];

    // =================================================================
    // NOTIFICACIONES -- Canales y escalado
    // =================================================================
    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notificaciones'),
      '#open' => TRUE,
    ];

    $form['notifications']['notification_channels'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Canales de notificacion'),
      '#description' => $this->t('Canales por los que se envian notificaciones de incidentes.'),
      '#options' => [
        'email' => $this->t('Email'),
        'slack' => $this->t('Slack'),
        'sms' => $this->t('SMS'),
        'webhook' => $this->t('Webhook'),
      ],
      '#default_value' => $config->get('notification_channels') ?? ['email', 'slack'],
    ];

    $form['notifications']['escalation_timeout_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout de escalado (minutos)'),
      '#description' => $this->t('Tiempo en minutos antes de escalar un incidente sin respuesta.'),
      '#default_value' => $config->get('escalation_timeout_minutes') ?? 30,
      '#min' => 5,
      '#max' => 120,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    $config->set('backup_verification_frequency', $form_state->getValue('backup_verification_frequency'));
    $config->set('backup_retention_days', (int) $form_state->getValue('backup_retention_days'));
    $config->set('failover_mode', $form_state->getValue('failover_mode'));
    $config->set('status_page_public', (bool) $form_state->getValue('status_page_public'));
    $config->set('status_page_refresh_seconds', (int) $form_state->getValue('status_page_refresh_seconds'));
    $config->set('dr_test_schedule', $form_state->getValue('dr_test_schedule'));

    // Filtrar canales seleccionados (eliminar los no marcados).
    $channels = array_values(array_filter($form_state->getValue('notification_channels')));
    $config->set('notification_channels', $channels);

    $config->set('escalation_timeout_minutes', (int) $form_state->getValue('escalation_timeout_minutes'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
