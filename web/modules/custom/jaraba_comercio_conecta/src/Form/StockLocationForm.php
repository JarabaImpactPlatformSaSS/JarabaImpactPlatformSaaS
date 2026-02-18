<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar ubicaciones de stock.
 *
 * Estructura: Extiende ContentEntityForm con fieldsets para
 *   datos de ubicación, capacidades omnicanal y configuración.
 *
 * Lógica: El formulario organiza los campos para facilitar la
 *   configuración de cada punto de inventario del comerciante,
 *   incluyendo las capacidades de Click & Collect y Ship-from-Store.
 */
class StockLocationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Datos de la ubicación
    $form['datos_ubicacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de la Ubicación'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $location_fields = ['name', 'type', 'address', 'merchant_id'];
    foreach ($location_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['datos_ubicacion'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Geolocalización
    $form['geolocalizacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Geolocalización'),
      '#open' => FALSE,
      '#weight' => 1,
    ];

    $geo_fields = ['latitude', 'longitude'];
    foreach ($geo_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['geolocalizacion'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Capacidades Omnicanal
    $form['omnicanal'] = [
      '#type' => 'details',
      '#title' => $this->t('Capacidades Omnicanal'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    $omni_fields = ['is_pickup_point', 'is_ship_from', 'priority'];
    foreach ($omni_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['omnicanal'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Estado y Configuración
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    $config_fields = ['is_active', 'tenant_id'];
    foreach ($config_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['config'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    $label = $entity->get('name')->value;

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('La ubicación %label ha sido creada.', [
        '%label' => $label,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('La ubicación %label ha sido actualizada.', [
        '%label' => $label,
      ]));
    }

    $form_state->setRedirect('entity.stock_location.collection');
    return $status;
  }

}
