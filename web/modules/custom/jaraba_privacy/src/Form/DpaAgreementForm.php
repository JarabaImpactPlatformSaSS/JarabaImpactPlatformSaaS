<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para DpaAgreement.
 *
 * En producción, los DPA se generan y firman vía DpaManagerService.
 * Este formulario permite la creación y edición manual desde admin.
 */
class DpaAgreementForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('version')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('DPA versión %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('DPA versión %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
