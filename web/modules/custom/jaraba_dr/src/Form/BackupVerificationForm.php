<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para BackupVerification.
 *
 * Los registros se generan normalmente via BackupVerifierService.
 * Este formulario permite el registro manual desde admin cuando sea necesario.
 */
class BackupVerificationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $type = $entity->get('backup_type')->value ?? 'backup';
    $message_args = ['%type' => $type];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Verificacion de backup %type registrada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Verificacion de backup %type actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
