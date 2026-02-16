<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para ServiceAgreement.
 *
 * En producción, los acuerdos se gestionan vía TosManagerService.
 * Este formulario permite la creación y edición manual desde admin.
 */
class ServiceAgreementForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('title')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Acuerdo de servicio %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Acuerdo de servicio %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
