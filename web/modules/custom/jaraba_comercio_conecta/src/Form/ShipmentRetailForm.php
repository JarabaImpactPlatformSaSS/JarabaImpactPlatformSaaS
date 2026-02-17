<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ShipmentRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['envio'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Envio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['order_id', 'carrier_id', 'shipping_method_id', 'tracking_number', 'tracking_url'] as $field) {
      if (isset($form[$field])) {
        $form['envio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['paquete'] = [
      '#type' => 'details',
      '#title' => $this->t('Paquete'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['weight_kg', 'dimensions', 'shipping_cost'] as $field) {
      if (isset($form[$field])) {
        $form['paquete'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['fechas'] = [
      '#type' => 'details',
      '#title' => $this->t('Fechas y Estado'),
      '#open' => FALSE,
      '#weight' => 20,
    ];
    foreach (['estimated_delivery', 'status', 'notes'] as $field) {
      if (isset($form[$field])) {
        $form['fechas'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('tracking_number')->value ?? $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Envio %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Envio %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
