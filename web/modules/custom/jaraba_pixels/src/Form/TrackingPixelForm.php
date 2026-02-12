<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar pixels de seguimiento.
 */
class TrackingPixelForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Pixel de seguimiento %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Pixel de seguimiento %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
