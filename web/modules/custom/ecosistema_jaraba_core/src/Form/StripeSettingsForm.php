<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion de Stripe.
 */
class StripeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ecosistema_jaraba_core.stripe'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ecosistema_jaraba_core_stripe_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ecosistema_jaraba_core.stripe');

    // Claves API.
    $form['keys'] = [
      '#type' => 'details',
      '#title' => $this->t('Claves API'),
      '#open' => TRUE,
    ];

    $form['keys']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Modo'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode') ?? 'test',
    ];

    $form['keys']['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clave pública (Publishable key)'),
      '#default_value' => $config->get('public_key') ?? '',
      '#placeholder' => 'pk_test_...',
      '#required' => TRUE,
    ];

    $form['keys']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clave secreta (Secret key)'),
      '#default_value' => $config->get('secret_key') ?? '',
      '#placeholder' => 'sk_test_...',
      '#required' => TRUE,
    ];

    $form['keys']['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook signing secret'),
      '#default_value' => $config->get('webhook_secret') ?? '',
      '#placeholder' => 'whsec_...',
    ];

    // Producto y moneda.
    $form['product'] = [
      '#type' => 'details',
      '#title' => $this->t('Producto y moneda'),
      '#open' => TRUE,
    ];

    $form['product']['product_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Product ID'),
      '#default_value' => $config->get('product_id') ?? '',
      '#placeholder' => 'prod_...',
    ];

    $form['product']['currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moneda por defecto'),
      '#default_value' => $config->get('currency') ?? 'eur',
      '#size' => 5,
    ];

    $form['product']['platform_fee_percent'] = [
      '#type' => 'number',
      '#title' => $this->t('Comisión de plataforma (%)'),
      '#default_value' => $config->get('platform_fee_percent') ?? 10,
      '#min' => 0,
      '#max' => 100,
      '#step' => 0.1,
    ];

    // Checkout URLs.
    $form['checkout'] = [
      '#type' => 'details',
      '#title' => $this->t('URLs de Checkout'),
      '#open' => FALSE,
    ];

    $form['checkout']['success_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de éxito'),
      '#default_value' => $config->get('checkout.success_url') ?? '',
      '#placeholder' => '/checkout/success',
    ];

    $form['checkout']['cancel_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de cancelación'),
      '#default_value' => $config->get('checkout.cancel_url') ?? '',
      '#placeholder' => '/checkout/cancel',
    ];

    // Portal de clientes.
    $form['portal'] = [
      '#type' => 'details',
      '#title' => $this->t('Portal de clientes'),
      '#open' => FALSE,
    ];

    $form['portal']['portal_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar portal de clientes'),
      '#default_value' => $config->get('portal.enabled') ?? TRUE,
    ];

    $form['portal']['allow_cancellation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permitir cancelación'),
      '#default_value' => $config->get('portal.allow_cancellation') ?? TRUE,
    ];

    $form['portal']['allow_plan_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permitir cambio de plan'),
      '#default_value' => $config->get('portal.allow_plan_change') ?? TRUE,
    ];

    // Trial.
    $form['trial'] = [
      '#type' => 'details',
      '#title' => $this->t('Periodo de prueba'),
      '#open' => FALSE,
    ];

    $form['trial']['trial_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Días de prueba'),
      '#default_value' => $config->get('trial.days') ?? 14,
      '#min' => 0,
    ];

    $form['trial']['require_payment_method'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Requerir método de pago para el periodo de prueba'),
      '#default_value' => $config->get('trial.require_payment_method') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ecosistema_jaraba_core.stripe')
      ->set('mode', $form_state->getValue('mode'))
      ->set('public_key', $form_state->getValue('public_key'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('webhook_secret', $form_state->getValue('webhook_secret'))
      ->set('product_id', $form_state->getValue('product_id'))
      ->set('currency', $form_state->getValue('currency'))
      ->set('platform_fee_percent', (float) $form_state->getValue('platform_fee_percent'))
      ->set('checkout.success_url', $form_state->getValue('success_url'))
      ->set('checkout.cancel_url', $form_state->getValue('cancel_url'))
      ->set('portal.enabled', (bool) $form_state->getValue('portal_enabled'))
      ->set('portal.allow_cancellation', (bool) $form_state->getValue('allow_cancellation'))
      ->set('portal.allow_plan_change', (bool) $form_state->getValue('allow_plan_change'))
      ->set('trial.days', (int) $form_state->getValue('trial_days'))
      ->set('trial.require_payment_method', (bool) $form_state->getValue('require_payment_method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
