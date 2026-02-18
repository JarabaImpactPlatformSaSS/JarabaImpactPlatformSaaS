<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class PosConnectionForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['conexion'] = [
      '#type' => 'details',
      '#title' => $this->t('Conexion TPV'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['name', 'merchant_id', 'provider', 'status'] as $field) {
      if (isset($form[$field])) {
        $form['conexion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion API'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    foreach (['api_key', 'api_secret', 'webhook_url', 'location_id', 'sync_frequency'] as $field) {
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
    $message_args = ['%label' => $entity->get('name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Conexion TPV %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Conexion TPV %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
