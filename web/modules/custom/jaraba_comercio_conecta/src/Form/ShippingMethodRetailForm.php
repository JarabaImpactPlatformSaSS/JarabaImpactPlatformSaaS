<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ShippingMethodRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['metodo'] = [
      '#type' => 'details',
      '#title' => $this->t('Metodo de Envio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['name', 'machine_name', 'description', 'base_price', 'free_above', 'estimated_days_min', 'estimated_days_max', 'is_active'] as $field) {
      if (isset($form[$field])) {
        $form['metodo'][$field] = $form[$field];
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
      $this->messenger()->addStatus($this->t('Metodo de envio %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Metodo de envio %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
