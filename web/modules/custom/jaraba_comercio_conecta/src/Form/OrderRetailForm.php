<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class OrderRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_pedido'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion del Pedido'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['order_number', 'customer_uid', 'merchant_id', 'status'] as $field) {
      if (isset($form[$field])) {
        $form['info_pedido'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['importes'] = [
      '#type' => 'details',
      '#title' => $this->t('Importes'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['subtotal', 'tax_amount', 'shipping_cost', 'discount_amount', 'total'] as $field) {
      if (isset($form[$field])) {
        $form['importes'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['pago'] = [
      '#type' => 'details',
      '#title' => $this->t('Pago'),
      '#open' => FALSE,
      '#weight' => 20,
    ];
    foreach (['payment_method', 'payment_status', 'payment_intent_id'] as $field) {
      if (isset($form[$field])) {
        $form['pago'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['envio'] = [
      '#type' => 'details',
      '#title' => $this->t('Envio'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['shipping_address', 'billing_address', 'shipping_method', 'tracking_number'] as $field) {
      if (isset($form[$field])) {
        $form['envio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('order_number')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Pedido %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Pedido %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
