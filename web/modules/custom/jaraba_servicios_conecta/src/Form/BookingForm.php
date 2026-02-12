<?php

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar reservas.
 *
 * Estructura: Extiende ContentEntityForm con fieldsets temáticos.
 *
 * Lógica: Agrupa campos por: datos de la cita, cliente, estado,
 *   pago, videollamada y notas.
 */
class BookingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['cita'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de la Cita'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['provider_id', 'offering_id', 'booking_date', 'duration_minutes', 'modality'] as $field) {
      if (isset($form[$field])) {
        $form['cita'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['cliente'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Cliente'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['client_name', 'client_email', 'client_phone', 'client_notes'] as $field) {
      if (isset($form[$field])) {
        $form['cliente'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['estado'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['status', 'cancellation_reason'] as $field) {
      if (isset($form[$field])) {
        $form['estado'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['pago'] = [
      '#type' => 'details',
      '#title' => $this->t('Pago'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['price', 'payment_status', 'stripe_payment_intent'] as $field) {
      if (isset($form[$field])) {
        $form['pago'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['videollamada'] = [
      '#type' => 'details',
      '#title' => $this->t('Videollamada'),
      '#open' => FALSE,
      '#weight' => 40,
    ];
    foreach (['meeting_url'] as $field) {
      if (isset($form[$field])) {
        $form['videollamada'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['notas_profesional'] = [
      '#type' => 'details',
      '#title' => $this->t('Notas del Profesional'),
      '#open' => FALSE,
      '#weight' => 50,
    ];
    foreach (['provider_notes'] as $field) {
      if (isset($form[$field])) {
        $form['notas_profesional'][$field] = $form[$field];
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

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Reserva #@id creada.', ['@id' => $entity->id()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Reserva #@id actualizada.', ['@id' => $entity->id()]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
