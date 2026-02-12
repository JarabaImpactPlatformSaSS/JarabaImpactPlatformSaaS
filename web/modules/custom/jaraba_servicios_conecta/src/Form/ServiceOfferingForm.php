<?php

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar servicios ofertados.
 *
 * Estructura: Extiende ContentEntityForm. Los campos se agrupan en
 *   fieldsets temáticos.
 *
 * Lógica: Agrupa campos por: datos del servicio, precio y duración,
 *   modalidad, configuración de reserva, estado e imagen.
 */
class ServiceOfferingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['servicio'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Servicio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'provider_id', 'description', 'category'] as $field) {
      if (isset($form[$field])) {
        $form['servicio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['precio'] = [
      '#type' => 'details',
      '#title' => $this->t('Precio y Duración'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['price', 'price_type', 'duration_minutes'] as $field) {
      if (isset($form[$field])) {
        $form['precio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['modalidad'] = [
      '#type' => 'details',
      '#title' => $this->t('Modalidad'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['modality', 'max_participants'] as $field) {
      if (isset($form[$field])) {
        $form['modalidad'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['configuracion_reserva'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Reserva'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['requires_prepayment', 'advance_booking_min'] as $field) {
      if (isset($form[$field])) {
        $form['configuracion_reserva'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['estado'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado y Orden'),
      '#open' => TRUE,
      '#weight' => 40,
    ];
    foreach (['is_published', 'is_featured', 'sort_weight'] as $field) {
      if (isset($form[$field])) {
        $form['estado'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['media'] = [
      '#type' => 'details',
      '#title' => $this->t('Imagen'),
      '#open' => FALSE,
      '#weight' => 50,
    ];
    foreach (['image'] as $field) {
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
      $this->messenger()->addStatus($this->t('Servicio %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Servicio %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
