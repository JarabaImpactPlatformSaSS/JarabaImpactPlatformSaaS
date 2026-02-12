<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de Playbooks de Customer Success.
 *
 * PROPÓSITO:
 * Permite a los CSM crear y editar playbooks con triggers, pasos
 * secuenciales y configuración de ejecución automática.
 *
 * LÓGICA:
 * - Grupo 1: Información básica (nombre, trigger, prioridad, estado).
 * - Grupo 2: Condiciones del trigger (JSON editor).
 * - Grupo 3: Pasos de ejecución (JSON editor con guía).
 * - Grupo 4: Configuración de ejecución (auto, estadísticas).
 */
class CsPlaybookForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Información básica.
    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['name']['#group'] = 'basic_info';
    }
    if (isset($form['trigger_type'])) {
      $form['trigger_type']['#group'] = 'basic_info';
    }
    if (isset($form['priority'])) {
      $form['priority']['#group'] = 'basic_info';
    }
    if (isset($form['status'])) {
      $form['status']['#group'] = 'basic_info';
    }

    // Grupo: Condiciones del trigger.
    $form['trigger_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Trigger Conditions'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['trigger_conditions'])) {
      $form['trigger_conditions']['#group'] = 'trigger_settings';
      $form['trigger_conditions']['widget'][0]['value']['#description'] = $this->t('JSON object with conditions. Examples: {"score_below": 60}, {"usage_above": 80}, {"churn_probability_above": 0.7}');
    }

    // Grupo: Pasos de ejecución.
    $form['execution_steps'] = [
      '#type' => 'details',
      '#title' => $this->t('Execution Steps'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    if (isset($form['steps'])) {
      $form['steps']['#group'] = 'execution_steps';
      $form['steps']['widget'][0]['value']['#description'] = $this->t('JSON array of steps. Each step: {"day": 0, "action": "email|call|in_app|internal", "subject": "...", "details": {...}}. Example: [{"day": 0, "action": "internal", "subject": "Alert CSM"}, {"day": 1, "action": "email", "subject": "Check-in email"}]');
    }

    // Grupo: Configuración de ejecución.
    $form['execution_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Execution Configuration'),
      '#open' => FALSE,
      '#weight' => 30,
    ];

    if (isset($form['auto_execute'])) {
      $form['auto_execute']['#group'] = 'execution_config';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar JSON de trigger_conditions.
    $conditions = $form_state->getValue(['trigger_conditions', 0, 'value']);
    if ($conditions && json_decode($conditions) === NULL && json_last_error() !== JSON_ERROR_NONE) {
      $form_state->setErrorByName('trigger_conditions', $this->t('Trigger conditions must be valid JSON.'));
    }

    // Validar JSON de steps.
    $steps = $form_state->getValue(['steps', 0, 'value']);
    if ($steps) {
      $decoded = json_decode($steps, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('steps', $this->t('Steps must be valid JSON.'));
      }
      elseif (!is_array($decoded)) {
        $form_state->setErrorByName('steps', $this->t('Steps must be a JSON array.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Playbook %name created.', [
        '%name' => $entity->getName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Playbook %name updated.', [
        '%name' => $entity->getName(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
