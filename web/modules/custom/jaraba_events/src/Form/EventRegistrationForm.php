<?php

namespace Drupal\jaraba_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar registros de evento.
 *
 * Estructura: Extiende ContentEntityForm para la entidad EventRegistration.
 *
 * Lógica: Muestra mensaje con el nombre del asistente al guardar.
 *   Redirige al listado de registros tras creación/edición.
 *
 * Sintaxis: Drupal 11 — return types estrictos, SAVED_NEW/SAVED_UPDATED.
 */
class EventRegistrationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%name' => $entity->get('attendee_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Registro de evento para %name creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Registro de evento para %name actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
