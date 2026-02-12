<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración general de Usage Billing.
 */
class UsageBillingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_usage_billing.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_usage_billing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_usage_billing.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración General'),
      '#open' => TRUE,
    ];

    $form['general']['default_currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moneda por defecto'),
      '#description' => $this->t('Código ISO de la moneda por defecto (EUR, USD, etc.).'),
      '#default_value' => $config->get('default_currency') ?? 'EUR',
      '#maxlength' => 3,
      '#required' => TRUE,
    ];

    $form['general']['enable_stripe_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar sincronización con Stripe'),
      '#description' => $this->t('Sincronizar automáticamente los datos de uso con Stripe Metered Billing.'),
      '#default_value' => $config->get('enable_stripe_sync') ?? FALSE,
    ];

    $form['aggregation'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Agregación'),
      '#open' => TRUE,
    ];

    $form['aggregation']['hourly_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Agregación horaria'),
      '#description' => $this->t('Habilitar la agregación horaria de eventos.'),
      '#default_value' => $config->get('hourly_enabled') ?? TRUE,
    ];

    $form['aggregation']['daily_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Agregación diaria'),
      '#description' => $this->t('Habilitar la agregación diaria de eventos.'),
      '#default_value' => $config->get('daily_enabled') ?? TRUE,
    ];

    $form['aggregation']['monthly_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Agregación mensual'),
      '#description' => $this->t('Habilitar la agregación mensual de eventos.'),
      '#default_value' => $config->get('monthly_enabled') ?? TRUE,
    ];

    $form['alerts'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Alertas'),
      '#open' => TRUE,
    ];

    $form['alerts']['alert_threshold_warning'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de advertencia (%)'),
      '#description' => $this->t('Porcentaje del límite en el que se envía advertencia.'),
      '#default_value' => $config->get('alert_threshold_warning') ?? 80,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['alerts']['alert_threshold_critical'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral crítico (%)'),
      '#description' => $this->t('Porcentaje del límite en el que se envía alerta crítica.'),
      '#default_value' => $config->get('alert_threshold_critical') ?? 95,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['alerts']['alert_email_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enviar alertas por email'),
      '#description' => $this->t('Enviar notificaciones por email cuando se superan umbrales.'),
      '#default_value' => $config->get('alert_email_enabled') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_usage_billing.settings')
      ->set('default_currency', $form_state->getValue('default_currency'))
      ->set('enable_stripe_sync', (bool) $form_state->getValue('enable_stripe_sync'))
      ->set('hourly_enabled', (bool) $form_state->getValue('hourly_enabled'))
      ->set('daily_enabled', (bool) $form_state->getValue('daily_enabled'))
      ->set('monthly_enabled', (bool) $form_state->getValue('monthly_enabled'))
      ->set('alert_threshold_warning', (int) $form_state->getValue('alert_threshold_warning'))
      ->set('alert_threshold_critical', (int) $form_state->getValue('alert_threshold_critical'))
      ->set('alert_email_enabled', (bool) $form_state->getValue('alert_email_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
