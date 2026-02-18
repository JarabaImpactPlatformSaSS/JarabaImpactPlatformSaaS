<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class QrCodeRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_qr'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion del Codigo QR'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['name', 'merchant_id', 'qr_type', 'short_code'] as $field) {
      if (isset($form[$field])) {
        $form['info_qr'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['destino'] = [
      '#type' => 'details',
      '#title' => $this->t('Destino'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['target_url', 'target_entity_type', 'target_entity_id'] as $field) {
      if (isset($form[$field])) {
        $form['destino'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['ab_testing'] = [
      '#type' => 'details',
      '#title' => $this->t('Test A/B'),
      '#open' => FALSE,
      '#weight' => 20,
    ];
    foreach (['ab_variant', 'ab_target_url'] as $field) {
      if (isset($form[$field])) {
        $form['ab_testing'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['diseno'] = [
      '#type' => 'details',
      '#title' => $this->t('Diseno'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['design_config'] as $field) {
      if (isset($form[$field])) {
        $form['diseno'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Codigo QR %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Codigo QR %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
