<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion del modulo Agent Flows.
 *
 * PROPOSITO:
 * Permite configurar parametros globales de ejecucion de flujos:
 * timeout por defecto, concurrencia maxima y retry policy.
 *
 * RUTA: /admin/config/jaraba/agent-flows
 */
class AgentFlowSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_agent_flows.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_agent_flows_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_agent_flows.settings');

    $form['execution'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion de Ejecucion'),
      '#open' => TRUE,
    ];

    $form['execution']['default_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout por defecto (segundos)'),
      '#description' => $this->t('Tiempo maximo de ejecucion para cada flujo. Valor por defecto: 300 segundos.'),
      '#default_value' => $config->get('default_timeout') ?? 300,
      '#min' => 10,
      '#max' => 3600,
      '#required' => TRUE,
    ];

    $form['execution']['max_concurrent'] = [
      '#type' => 'number',
      '#title' => $this->t('Ejecuciones concurrentes maximas'),
      '#description' => $this->t('Numero maximo de flujos que pueden ejecutarse simultaneamente por tenant.'),
      '#default_value' => $config->get('max_concurrent') ?? 5,
      '#min' => 1,
      '#max' => 50,
      '#required' => TRUE,
    ];

    $form['execution']['max_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Reintentos maximos'),
      '#description' => $this->t('Numero de reintentos automaticos ante fallos transitorios.'),
      '#default_value' => $config->get('max_retries') ?? 3,
      '#min' => 0,
      '#max' => 10,
      '#required' => TRUE,
    ];

    $form['execution']['retry_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay entre reintentos (segundos)'),
      '#description' => $this->t('Tiempo de espera entre reintentos. Se aplica backoff exponencial.'),
      '#default_value' => $config->get('retry_delay') ?? 5,
      '#min' => 1,
      '#max' => 60,
      '#required' => TRUE,
    ];

    $form['logging'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion de Logging'),
      '#open' => FALSE,
    ];

    $form['logging']['log_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Retencion de logs (dias)'),
      '#description' => $this->t('Dias que se conservan los logs de ejecucion antes de purgar.'),
      '#default_value' => $config->get('log_retention_days') ?? 90,
      '#min' => 7,
      '#max' => 365,
      '#required' => TRUE,
    ];

    $form['logging']['enable_step_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar logging detallado de pasos'),
      '#description' => $this->t('Registra input/output de cada paso individual. Puede aumentar el uso de almacenamiento.'),
      '#default_value' => $config->get('enable_step_logging') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_agent_flows.settings')
      ->set('default_timeout', (int) $form_state->getValue('default_timeout'))
      ->set('max_concurrent', (int) $form_state->getValue('max_concurrent'))
      ->set('max_retries', (int) $form_state->getValue('max_retries'))
      ->set('retry_delay', (int) $form_state->getValue('retry_delay'))
      ->set('log_retention_days', (int) $form_state->getValue('log_retention_days'))
      ->set('enable_step_logging', (bool) $form_state->getValue('enable_step_logging'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
