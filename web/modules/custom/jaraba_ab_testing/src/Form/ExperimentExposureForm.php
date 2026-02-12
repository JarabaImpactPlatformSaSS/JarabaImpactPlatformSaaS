<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar exposiciones de experimento.
 *
 * Estructura: Extiende ContentEntityForm con logica de guardado
 *   y mensajes de estado.
 *
 * Logica: Al guardar, muestra un mensaje de exito indicando si
 *   se creo o actualizo la exposicion. Redirige al listado tras guardar.
 *
 * Sintaxis: Drupal 11 — return types estrictos, SAVED_NEW/SAVED_UPDATED.
 */
class ExperimentExposureForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);

    $visitor_id = $entity->get('visitor_id')->value ?? '';
    $message_args = ['%visitor' => $visitor_id];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Exposicion para visitante %visitor creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Exposicion para visitante %visitor actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
