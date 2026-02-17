<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion del vertical ServiciosConecta.
 *
 * Accesible en /admin/config/jaraba/servicios-conecta.
 * Todos los labels usan $this->t() para i18n.
 */
class ServiciosConectaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_servicios_conecta.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_servicios_conecta_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_servicios_conecta.settings');

    $form['booking'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion de Reservas'),
      '#open' => TRUE,
    ];

    $form['booking']['booking_buffer_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Buffer entre reservas (minutos)'),
      '#description' => $this->t('Tiempo minimo entre el fin de una reserva y el inicio de la siguiente.'),
      '#default_value' => $config->get('booking_buffer_minutes') ?? 15,
      '#min' => 0,
      '#max' => 120,
      '#required' => TRUE,
    ];

    $form['booking']['max_advance_booking_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Dias maximo de antelacion'),
      '#description' => $this->t('Numero maximo de dias en el futuro para una reserva.'),
      '#default_value' => $config->get('max_advance_booking_days') ?? 60,
      '#min' => 1,
      '#max' => 365,
      '#required' => TRUE,
    ];

    $form['booking']['require_prepayment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Requerir pago anticipado'),
      '#description' => $this->t('Si esta activo, los clientes deben pagar antes de confirmar la reserva.'),
      '#default_value' => $config->get('require_prepayment') ?? FALSE,
    ];

    $form['booking']['auto_cancel_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Cancelacion automatica (horas)'),
      '#description' => $this->t('Horas sin confirmacion antes de cancelar automaticamente una reserva pendiente.'),
      '#default_value' => $config->get('auto_cancel_hours') ?? 24,
      '#min' => 1,
      '#max' => 168,
      '#required' => TRUE,
    ];

    $form['billing'] = [
      '#type' => 'details',
      '#title' => $this->t('Facturacion y Comisiones'),
      '#open' => TRUE,
    ];

    $form['billing']['commission_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Comision SaaS (%)'),
      '#description' => $this->t('Porcentaje de comision aplicado a cada transaccion.'),
      '#default_value' => $config->get('commission_rate') ?? 10,
      '#min' => 0,
      '#max' => 50,
      '#required' => TRUE,
    ];

    $form['billing']['stripe_connect_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Modo de Stripe Connect'),
      '#options' => [
        'express' => $this->t('Express (recomendado)'),
        'standard' => $this->t('Standard'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $config->get('stripe_connect_mode') ?? 'express',
    ];

    $form['billing']['min_service_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Precio minimo de servicio (EUR)'),
      '#description' => $this->t('Precio minimo permitido para un servicio publicado. 0 permite servicios gratuitos.'),
      '#default_value' => $config->get('min_service_price') ?? 0,
      '#min' => 0,
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Visualizacion'),
      '#open' => FALSE,
    ];

    $form['display']['max_services_display'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximo de servicios en listado'),
      '#default_value' => $config->get('max_services_display') ?? 50,
      '#min' => 10,
      '#max' => 200,
    ];

    $form['display']['review_auto_approve'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-aprobar resenas'),
      '#description' => $this->t('Si esta activo, las resenas se publican automaticamente sin moderacion.'),
      '#default_value' => $config->get('review_auto_approve') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_servicios_conecta.settings')
      ->set('booking_buffer_minutes', (int) $form_state->getValue('booking_buffer_minutes'))
      ->set('max_advance_booking_days', (int) $form_state->getValue('max_advance_booking_days'))
      ->set('require_prepayment', (bool) $form_state->getValue('require_prepayment'))
      ->set('auto_cancel_hours', (int) $form_state->getValue('auto_cancel_hours'))
      ->set('commission_rate', (int) $form_state->getValue('commission_rate'))
      ->set('stripe_connect_mode', $form_state->getValue('stripe_connect_mode'))
      ->set('min_service_price', (int) $form_state->getValue('min_service_price'))
      ->set('max_services_display', (int) $form_state->getValue('max_services_display'))
      ->set('review_auto_approve', (bool) $form_state->getValue('review_auto_approve'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
