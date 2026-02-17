<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar agentes autonomos.
 *
 * Estructura: ContentEntityForm con 4 fieldsets agrupados por
 *   seccion logica: identificacion, capacidades, configuracion LLM
 *   y estado operativo.
 *
 * Logica: Los campos se redistribuyen desde el formulario base
 *   a fieldsets con peso para ordenacion coherente. El mensaje
 *   de guardado distingue entre creacion y actualizacion.
 */
class AutonomousAgentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 4 fieldsets tematicos.
   * Logica: Mueve los campos del formulario base a sus fieldsets
   *   correspondientes para mejorar la experiencia de usuario.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Identificacion ---
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $identification_fields = ['name', 'agent_type', 'vertical', 'objective'];
    foreach ($identification_fields as $field) {
      if (isset($form[$field])) {
        $form['identification'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 2: Capacidades ---
    $form['capabilities_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Capacidades'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $capabilities_fields = ['capabilities', 'guardrails', 'requires_approval'];
    foreach ($capabilities_fields as $field) {
      if (isset($form[$field])) {
        $form['capabilities_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 3: Configuracion LLM ---
    $form['llm_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion LLM'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $llm_fields = ['autonomy_level', 'llm_model', 'temperature', 'max_actions_per_run'];
    foreach ($llm_fields as $field) {
      if (isset($form[$field])) {
        $form['llm_config'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    // --- Fieldset 4: Estado ---
    $form['status_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => FALSE,
      '#weight' => -5,
    ];

    $status_fields = ['is_active', 'performance_metrics'];
    foreach ($status_fields as $field) {
      if (isset($form[$field])) {
        $form['status_section'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Guarda la entidad y muestra mensaje contextual.
   * Logica: Distingue entre creacion (SAVED_NEW) y actualizacion
   *   (SAVED_UPDATED) para mostrar el mensaje apropiado.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $name = $entity->get('name')->value ?? '';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(
        $this->t('Agente «@name» creado correctamente.', ['@name' => $name])
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t('Agente «@name» actualizado correctamente.', ['@name' => $name])
      );
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
