<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class NotificationTemplateForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['plantilla'] = [
      '#type' => 'details',
      '#title' => $this->t('Plantilla de Notificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['name', 'machine_name', 'channel', 'subject_template', 'body_template', 'is_active'] as $field) {
      if (isset($form[$field])) {
        $form['plantilla'][$field] = $form[$field];
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
      $this->messenger()->addStatus($this->t('Plantilla %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Plantilla %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
