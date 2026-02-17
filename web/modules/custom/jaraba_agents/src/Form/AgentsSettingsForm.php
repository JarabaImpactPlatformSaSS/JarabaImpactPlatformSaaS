<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo de agentes autonomos.
 *
 * Estructura: ConfigFormBase con 3 fieldsets: autonomia, LLM y costes.
 *
 * Logica: Gestiona configuracion global incluyendo nivel de autonomia
 *   por defecto, modelo LLM, presupuesto de tokens y alertas de coste.
 */
class AgentsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * Estructura: Define los nombres de configuracion editables.
   * Logica: Solo expone jaraba_agents.settings para edicion.
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_agents.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Retorna el ID unico del formulario.
   * Logica: Convencional para ConfigFormBase del ecosistema.
   */
  public function getFormId(): string {
    return 'jaraba_agents_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 3 fieldsets tematicos.
   * Logica: Cada fieldset agrupa parametros relacionados con
   *   autonomia, modelo LLM y gestion de costes.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_agents.settings');

    // --- Fieldset 1: Autonomia ---
    $form['autonomy'] = [
      '#type' => 'details',
      '#title' => $this->t('Autonomia'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $form['autonomy']['default_autonomy_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Nivel de autonomia por defecto'),
      '#description' => $this->t('Nivel de autonomia asignado a nuevos agentes.'),
      '#options' => [
        'supervised' => $this->t('Supervisado'),
        'semi_autonomous' => $this->t('Semi-autonomo'),
        'autonomous' => $this->t('Autonomo'),
        'full_autonomous' => $this->t('Totalmente autonomo'),
      ],
      '#default_value' => $config->get('default_autonomy_level') ?? 'supervised',
      '#required' => TRUE,
    ];

    $form['autonomy']['guardrails_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Guardrails habilitados'),
      '#description' => $this->t('Activar las restricciones de seguridad globales para todos los agentes.'),
      '#default_value' => $config->get('guardrails_enabled') ?? TRUE,
    ];

    $form['autonomy']['approval_expiry_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas de expiracion de aprobaciones'),
      '#description' => $this->t('Tiempo maximo en horas antes de que una solicitud de aprobacion expire automaticamente.'),
      '#default_value' => $config->get('approval_expiry_hours') ?? 24,
      '#min' => 1,
      '#max' => 168,
      '#required' => TRUE,
    ];

    // --- Fieldset 2: LLM ---
    $form['llm'] = [
      '#type' => 'details',
      '#title' => $this->t('LLM'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $form['llm']['default_llm_model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modelo LLM por defecto'),
      '#description' => $this->t('Identificador del modelo de lenguaje utilizado por defecto (ej. gpt-4o, claude-sonnet-4-20250514).'),
      '#default_value' => $config->get('default_llm_model') ?? 'gpt-4o',
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['llm']['default_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperatura por defecto'),
      '#description' => $this->t('Valor de temperatura del LLM (0.00 a 2.00). Valores bajos = mas determinista.'),
      '#default_value' => $config->get('default_temperature') ?? 0.7,
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['llm']['max_actions_per_run'] = [
      '#type' => 'number',
      '#title' => $this->t('Acciones maximas por ejecucion'),
      '#description' => $this->t('Numero maximo de acciones que un agente puede ejecutar en una sola ejecucion.'),
      '#default_value' => $config->get('max_actions_per_run') ?? 10,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    // --- Fieldset 3: Costes ---
    $form['costs'] = [
      '#type' => 'details',
      '#title' => $this->t('Costes'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $form['costs']['token_budget_per_execution'] = [
      '#type' => 'number',
      '#title' => $this->t('Presupuesto de tokens por ejecucion'),
      '#description' => $this->t('Limite maximo de tokens consumibles por una sola ejecucion de agente.'),
      '#default_value' => $config->get('token_budget_per_execution') ?? 10000,
      '#min' => 100,
      '#max' => 1000000,
      '#required' => TRUE,
    ];

    $form['costs']['cost_alert_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de alerta de coste'),
      '#description' => $this->t('Importe en EUR a partir del cual se genera una alerta de coste por ejecucion.'),
      '#default_value' => $config->get('cost_alert_threshold') ?? 1.00,
      '#min' => 0.01,
      '#max' => 1000,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Persiste los valores del formulario en configuracion.
   * Logica: Guarda cada campo en jaraba_agents.settings usando
   *   los valores del FormState.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_agents.settings')
      ->set('default_autonomy_level', $form_state->getValue('default_autonomy_level'))
      ->set('guardrails_enabled', (bool) $form_state->getValue('guardrails_enabled'))
      ->set('approval_expiry_hours', (int) $form_state->getValue('approval_expiry_hours'))
      ->set('default_llm_model', $form_state->getValue('default_llm_model'))
      ->set('default_temperature', (float) $form_state->getValue('default_temperature'))
      ->set('max_actions_per_run', (int) $form_state->getValue('max_actions_per_run'))
      ->set('token_budget_per_execution', (int) $form_state->getValue('token_budget_per_execution'))
      ->set('cost_alert_threshold', (float) $form_state->getValue('cost_alert_threshold'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
