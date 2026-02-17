<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ReviewRetailForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['resena'] = [
      '#type' => 'details',
      '#title' => $this->t('Resena'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'body', 'rating', 'entity_type_ref', 'entity_id_ref', 'photos'] as $field) {
      if (isset($form[$field])) {
        $form['resena'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['moderacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderacion'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['status', 'merchant_response'] as $field) {
      if (isset($form[$field])) {
        $form['moderacion'][$field] = $form[$field];
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
      $this->messenger()->addStatus($this->t('Resena %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Resena %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
