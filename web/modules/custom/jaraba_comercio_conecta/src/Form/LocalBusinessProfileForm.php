<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class LocalBusinessProfileForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['datos_negocio'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Negocio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['business_name', 'description_seo', 'phone', 'email', 'website_url'] as $field) {
      if (isset($form[$field])) {
        $form['datos_negocio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['direccion'] = [
      '#type' => 'details',
      '#title' => $this->t('Direccion'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['address_street', 'city', 'postal_code', 'province', 'country'] as $field) {
      if (isset($form[$field])) {
        $form['direccion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['geolocalizacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Geolocalizacion'),
      '#open' => FALSE,
      '#weight' => 20,
    ];
    foreach (['latitude', 'longitude'] as $field) {
      if (isset($form[$field])) {
        $form['geolocalizacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['seo'] = [
      '#type' => 'details',
      '#title' => $this->t('SEO Local'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['google_place_id', 'google_business_url', 'schema_type', 'opening_hours'] as $field) {
      if (isset($form[$field])) {
        $form['seo'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('business_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Perfil de negocio %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Perfil de negocio %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
