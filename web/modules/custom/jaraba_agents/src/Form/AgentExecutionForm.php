<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para registrar ejecuciones de agentes (solo creacion).
 *
 * Estructura: ContentEntityForm con 3 fieldsets: configuracion,
 *   ejecucion e IA. El formulario es append-only, no permite edicion.
 *
 * Logica: Los campos de resultado (completed_at, status, tokens_used,
 *   cost_estimate) se rellenan automaticamente durante la ejecucion
 *   del agente por el orquestador, no por el usuario.
 */
class AgentExecutionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 3 fieldsets tematicos.
   * Logica: Solo expone los campos necesarios para iniciar una
   *   ejecucion. Los campos de resultado son de solo lectura.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Configuracion ---
    $form['config_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $config_fields = ['agent_id', 'trigger_type', 'trigger_data'];
    foreach ($config_fields as $field) {
      if (isset($form[$field])) {
        $form['config_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 2: Ejecucion ---
    $form['execution_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Ejecucion'),
      '#open' => FALSE,
      '#weight' => -15,
    ];

    $execution_fields = ['started_at'];
    foreach ($execution_fields as $field) {
      if (isset($form[$field])) {
        $form['execution_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['execution_section']['execution_note'] = [
      '#type' => 'markup',
      '#markup' => '<p><em>' . $this->t('Los campos de resultado (completed_at, status, tokens_used, cost_estimate) se rellenan automaticamente durante la ejecucion.') . '</em></p>',
    ];

    // --- Fieldset 3: IA ---
    $form['ia_section'] = [
      '#type' => 'details',
      '#title' => $this->t('IA'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    $form['ia_section']['tokens_note'] = [
      '#type' => 'markup',
      '#markup' => '<p><em>' . $this->t('El consumo de tokens y la estimacion de coste se calculan automaticamente al finalizar la ejecucion del agente.') . '</em></p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Guarda la entidad y muestra mensaje de confirmacion.
   * Logica: Solo soporta creacion (append-only). El mensaje es
   *   generico porque no hay campo nombre en AgentExecution.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $this->messenger()->addStatus(
      $this->t('Ejecucion registrada correctamente.')
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
