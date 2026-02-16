<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para SlaRecord.
 *
 * En producción, los registros SLA se generan automáticamente
 * vía SlaCalculatorService. Este formulario permite la gestión manual.
 */
class SlaRecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%id' => $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Registro SLA #%id creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Registro SLA #%id actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
