<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class FlashOfferForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_oferta'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion de la Oferta'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'description', 'merchant_id', 'product_id', 'image_url'] as $field) {
      if (isset($form[$field])) {
        $form['info_oferta'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['descuento'] = [
      '#type' => 'details',
      '#title' => $this->t('Descuento'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['discount_type', 'discount_value', 'original_price', 'offer_price'] as $field) {
      if (isset($form[$field])) {
        $form['descuento'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['programacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Programacion'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['start_time', 'end_time', 'max_claims', 'status'] as $field) {
      if (isset($form[$field])) {
        $form['programacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['geolocalizacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Geolocalizacion'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['location_lat', 'location_lng', 'radius_km'] as $field) {
      if (isset($form[$field])) {
        $form['geolocalizacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('title')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Oferta flash %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Oferta flash %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
