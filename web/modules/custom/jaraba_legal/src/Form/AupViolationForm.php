<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para AupViolation.
 *
 * En producción, las violaciones AUP se detectan automáticamente
 * vía AupEnforcerService. Este formulario permite la gestión manual.
 */
class AupViolationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%id' => $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Violación AUP #%id registrada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Violación AUP #%id actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
