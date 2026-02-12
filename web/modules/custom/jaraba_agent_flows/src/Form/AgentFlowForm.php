<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar flujos de agentes IA.
 *
 * PROPOSITO:
 * Proporciona el formulario de edicion de la entidad AgentFlow con
 * validacion de JSON para flow_config y trigger_config.
 *
 * LOGICA:
 * - Valida que flow_config sea JSON valido antes de guardar.
 * - Valida que trigger_config sea JSON valido si se proporciona.
 * - Redirige a la coleccion tras guardar/eliminar.
 */
class AgentFlowForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar que flow_config sea JSON valido.
    $flowConfig = $form_state->getValue(['flow_config', 0, 'value']);
    if (!empty($flowConfig)) {
      json_decode($flowConfig);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName(
          'flow_config',
          $this->t('La configuracion del flujo debe ser un JSON valido. Error: @error', [
            '@error' => json_last_error_msg(),
          ]),
        );
      }
    }

    // Validar que trigger_config sea JSON valido si se proporciona.
    $triggerConfig = $form_state->getValue(['trigger_config', 0, 'value']);
    if (!empty($triggerConfig)) {
      json_decode($triggerConfig);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName(
          'trigger_config',
          $this->t('La configuracion del trigger debe ser un JSON valido. Error: @error', [
            '@error' => json_last_error_msg(),
          ]),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Flujo de agente %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Flujo de agente %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
