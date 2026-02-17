<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class SearchIndexForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['datos_indice'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Indice'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'entity_type_ref', 'entity_id_ref', 'search_text', 'keywords'] as $field) {
      if (isset($form[$field])) {
        $form['datos_indice'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['ubicacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Ubicacion'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    foreach (['location_lat', 'location_lng'] as $field) {
      if (isset($form[$field])) {
        $form['ubicacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['ranking'] = [
      '#type' => 'details',
      '#title' => $this->t('Ranking'),
      '#open' => FALSE,
      '#weight' => 20,
    ];
    foreach (['weight', 'boost_factor', 'is_active'] as $field) {
      if (isset($form[$field])) {
        $form['ranking'][$field] = $form[$field];
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
      $this->messenger()->addStatus($this->t('Entrada de indice %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Entrada de indice %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
