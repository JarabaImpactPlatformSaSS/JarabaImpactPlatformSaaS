<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para UsageLimitRecord.
 *
 * En producción, los registros de límites se generan automáticamente
 * vía AupEnforcerService. Este formulario permite la gestión manual.
 */
class UsageLimitRecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%id' => $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Registro de límite #%id creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Registro de límite #%id actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
