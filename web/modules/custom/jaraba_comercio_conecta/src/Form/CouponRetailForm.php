<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class CouponRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_cupon'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion del Cupon'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['code', 'description', 'discount_type', 'discount_value', 'min_order_amount', 'merchant_id'] as $field) {
      if (isset($form[$field])) {
        $form['info_cupon'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['limites'] = [
      '#type' => 'details',
      '#title' => $this->t('Limites de Uso'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['max_uses', 'max_uses_per_user', 'current_uses'] as $field) {
      if (isset($form[$field])) {
        $form['limites'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['vigencia'] = [
      '#type' => 'details',
      '#title' => $this->t('Vigencia'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['valid_from', 'valid_until', 'status'] as $field) {
      if (isset($form[$field])) {
        $form['vigencia'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('code')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Cupon %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Cupon %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
