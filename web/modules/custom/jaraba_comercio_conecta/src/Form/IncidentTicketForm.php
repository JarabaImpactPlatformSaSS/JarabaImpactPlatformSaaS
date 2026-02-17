<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class IncidentTicketForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['incidencia'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de la Incidencia'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['subject', 'description', 'category', 'priority', 'order_id', 'merchant_id'] as $field) {
      if (isset($form[$field])) {
        $form['incidencia'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['resolucion'] = [
      '#type' => 'details',
      '#title' => $this->t('Resolucion'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    foreach (['status', 'assigned_to', 'resolution_notes'] as $field) {
      if (isset($form[$field])) {
        $form['resolucion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('subject')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Incidencia %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Incidencia %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
