<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class SearchSynonymForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Simple form — term and synonyms fields are rendered at the top level.

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('term')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Sinonimo %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Sinonimo %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
