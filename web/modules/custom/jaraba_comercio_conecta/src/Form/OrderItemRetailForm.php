<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class OrderItemRetailForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('product_title')->value ?? $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Linea de pedido %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Linea de pedido %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
