<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para ProcessingActivity (RAT).
 *
 * Permite crear y editar actividades de tratamiento del Registro
 * de Actividades de Tratamiento (RGPD Art. 30).
 */
class ProcessingActivityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('activity_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Actividad de tratamiento "%label" creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Actividad de tratamiento "%label" actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
