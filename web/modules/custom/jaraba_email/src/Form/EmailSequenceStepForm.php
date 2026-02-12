<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar pasos de secuencia de email.
 */
class EmailSequenceStepForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $message_args = ['%label' => $entity->get('subject_line')->value ?: $this->t('Paso')];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Paso de secuencia %label creado correctamente.', $message_args));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Paso de secuencia %label actualizado correctamente.', $message_args));
        break;
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
