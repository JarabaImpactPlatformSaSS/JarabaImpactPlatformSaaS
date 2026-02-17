<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ReturnRequestForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_devolucion'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion de la Devolucion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['order_id', 'suborder_id', 'reason', 'description'] as $field) {
      if (isset($form[$field])) {
        $form['info_devolucion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['resolucion'] = [
      '#type' => 'details',
      '#title' => $this->t('Resolucion'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['status', 'refund_amount'] as $field) {
      if (isset($form[$field])) {
        $form['resolucion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Solicitud de devolucion creada.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Solicitud de devolucion actualizada.'));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
