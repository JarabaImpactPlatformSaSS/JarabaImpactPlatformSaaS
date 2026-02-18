<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class CarrierConfigForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['carrier'] = [
      '#type' => 'details',
      '#title' => $this->t('Transportista'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['carrier_name', 'carrier_code', 'is_active'] as $field) {
      if (isset($form[$field])) {
        $form['carrier'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion API'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    foreach (['api_url', 'api_key', 'api_secret', 'tracking_url_pattern', 'config_data'] as $field) {
      if (isset($form[$field])) {
        $form['api'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('carrier_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Transportista %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Transportista %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
