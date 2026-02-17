<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class SuborderRetailForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Sub-pedido creado.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Sub-pedido actualizado.'));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
