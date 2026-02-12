<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar perfiles de comerciante.
 *
 * Estructura: Extiende ContentEntityForm. Los campos se agrupan en
 *   fieldsets temáticos para facilitar la edición en el admin.
 *
 * Lógica: El formulario se usa tanto para creación como edición.
 *   Los fieldsets agrupan campos por función: datos del negocio,
 *   contacto, dirección, configuración, media y estado.
 */
class MerchantProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['datos_negocio'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Negocio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['business_name', 'slug', 'business_type', 'description'] as $field) {
      if (isset($form[$field])) {
        $form['datos_negocio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['contacto'] = [
      '#type' => 'details',
      '#title' => $this->t('Contacto'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['tax_id', 'phone', 'email', 'website'] as $field) {
      if (isset($form[$field])) {
        $form['contacto'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['direccion'] = [
      '#type' => 'details',
      '#title' => $this->t('Dirección'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['address_street', 'address_city', 'address_postal_code', 'address_province', 'address_country'] as $field) {
      if (isset($form[$field])) {
        $form['direccion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['geolocalizacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Geolocalización'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['latitude', 'longitude'] as $field) {
      if (isset($form[$field])) {
        $form['geolocalizacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['configuracion'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración'),
      '#open' => FALSE,
      '#weight' => 40,
    ];
    foreach (['opening_hours', 'accepts_click_collect', 'delivery_radius_km', 'commission_rate'] as $field) {
      if (isset($form[$field])) {
        $form['configuracion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['stripe'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe Connect'),
      '#open' => FALSE,
      '#weight' => 50,
    ];
    foreach (['stripe_account_id', 'stripe_onboarding_complete'] as $field) {
      if (isset($form[$field])) {
        $form['stripe'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['estado'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 60,
    ];
    foreach (['verification_status', 'is_active'] as $field) {
      if (isset($form[$field])) {
        $form['estado'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['media'] = [
      '#type' => 'details',
      '#title' => $this->t('Imágenes'),
      '#open' => FALSE,
      '#weight' => 70,
    ];
    foreach (['logo', 'cover_image', 'gallery'] as $field) {
      if (isset($form[$field])) {
        $form['media'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->toLink()->toString()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Comerciante %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Comerciante %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
