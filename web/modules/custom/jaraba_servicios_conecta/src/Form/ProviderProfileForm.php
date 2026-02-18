<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar perfiles de profesional.
 *
 * Estructura: Extiende ContentEntityForm. Los campos se agrupan en
 *   fieldsets temáticos para facilitar la edición en el admin.
 *
 * Lógica: El formulario se usa tanto para creación como edición.
 *   Los fieldsets agrupan campos por función: identidad, credenciales,
 *   contacto, dirección, configuración, pagos, estado y media.
 */
class ProviderProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['identidad'] = [
      '#type' => 'details',
      '#title' => $this->t('Identidad Profesional'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['display_name', 'slug', 'professional_title', 'service_category', 'specialties', 'description'] as $field) {
      if (isset($form[$field])) {
        $form['identidad'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['credenciales'] = [
      '#type' => 'details',
      '#title' => $this->t('Credenciales'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['license_number', 'tax_id', 'insurance_policy', 'years_experience'] as $field) {
      if (isset($form[$field])) {
        $form['credenciales'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['contacto'] = [
      '#type' => 'details',
      '#title' => $this->t('Contacto'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['phone', 'email', 'website'] as $field) {
      if (isset($form[$field])) {
        $form['contacto'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['direccion'] = [
      '#type' => 'details',
      '#title' => $this->t('Dirección'),
      '#open' => TRUE,
      '#weight' => 30,
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
      '#weight' => 40,
    ];
    foreach (['latitude', 'longitude'] as $field) {
      if (isset($form[$field])) {
        $form['geolocalizacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['configuracion'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración del Servicio'),
      '#open' => FALSE,
      '#weight' => 50,
    ];
    foreach (['service_radius_km', 'default_session_duration', 'buffer_time', 'advance_booking_days', 'cancellation_hours', 'requires_prepayment', 'accepts_online'] as $field) {
      if (isset($form[$field])) {
        $form['configuracion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['stripe'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe Connect'),
      '#open' => FALSE,
      '#weight' => 60,
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
      '#weight' => 70,
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
      '#weight' => 80,
    ];
    foreach (['photo', 'cover_image'] as $field) {
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
      $this->messenger()->addStatus($this->t('Profesional %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Profesional %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
