<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form for SLA Management module.
 *
 * Structure: ConfigFormBase with fieldsets for monitoring config and SLA tiers.
 * Logic: Manages monitoring intervals, timeout thresholds, default SLA tier,
 *   and per-tier uptime targets / credit policies.
 */
class SlaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_sla.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_sla_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_sla.settings');

    // =========================================================================
    // Fieldset 1: Monitoring Configuration
    // =========================================================================

    $form['monitoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Monitoring Configuration'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $form['monitoring']['web_app_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web App health check URL'),
      '#default_value' => $config->get('monitoring.web_app.url') ?? '/health',
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['monitoring']['web_app_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Web App check interval (seconds)'),
      '#default_value' => $config->get('monitoring.web_app.interval_seconds') ?? 30,
      '#min' => 5,
      '#max' => 300,
      '#required' => TRUE,
    ];

    $form['monitoring']['web_app_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Web App timeout (ms)'),
      '#default_value' => $config->get('monitoring.web_app.timeout_ms') ?? 2000,
      '#min' => 100,
      '#max' => 30000,
      '#required' => TRUE,
    ];

    $form['monitoring']['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API health check URL'),
      '#default_value' => $config->get('monitoring.api.url') ?? '/api/v1/health',
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['monitoring']['api_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('API check interval (seconds)'),
      '#default_value' => $config->get('monitoring.api.interval_seconds') ?? 30,
      '#min' => 5,
      '#max' => 300,
      '#required' => TRUE,
    ];

    $form['monitoring']['api_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('API timeout (ms)'),
      '#default_value' => $config->get('monitoring.api.timeout_ms') ?? 2000,
      '#min' => 100,
      '#max' => 30000,
      '#required' => TRUE,
    ];

    $form['monitoring']['database_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Database check interval (seconds)'),
      '#default_value' => $config->get('monitoring.database.interval_seconds') ?? 15,
      '#min' => 5,
      '#max' => 300,
      '#required' => TRUE,
    ];

    $form['monitoring']['redis_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Redis check interval (seconds)'),
      '#default_value' => $config->get('monitoring.redis.interval_seconds') ?? 15,
      '#min' => 5,
      '#max' => 300,
      '#required' => TRUE,
    ];

    $form['monitoring']['email_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Email check interval (seconds)'),
      '#default_value' => $config->get('monitoring.email.interval_seconds') ?? 60,
      '#min' => 10,
      '#max' => 600,
      '#required' => TRUE,
    ];

    $form['monitoring']['ai_copilot_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('AI Copilot check interval (seconds)'),
      '#default_value' => $config->get('monitoring.ai_copilot.interval_seconds') ?? 60,
      '#min' => 10,
      '#max' => 600,
      '#required' => TRUE,
    ];

    $form['monitoring']['payment_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Payment check interval (seconds)'),
      '#default_value' => $config->get('monitoring.payment.interval_seconds') ?? 60,
      '#min' => 10,
      '#max' => 600,
      '#required' => TRUE,
    ];

    // =========================================================================
    // Fieldset 2: SLA Tier Defaults
    // =========================================================================

    $form['sla_tiers'] = [
      '#type' => 'details',
      '#title' => $this->t('SLA Tier Configuration'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $form['sla_tiers']['default_sla_tier'] = [
      '#type' => 'select',
      '#title' => $this->t('Default SLA Tier'),
      '#options' => [
        'standard' => $this->t('Standard (99.9%)'),
        'premium' => $this->t('Premium (99.95%)'),
        'critical' => $this->t('Critical (99.99%)'),
      ],
      '#default_value' => $config->get('default_sla_tier') ?? 'standard',
      '#required' => TRUE,
    ];

    // Standard tier.
    $form['sla_tiers']['standard_uptime'] = [
      '#type' => 'number',
      '#title' => $this->t('Standard tier uptime target (%)'),
      '#default_value' => $config->get('sla_tiers.standard.uptime_target') ?? 99.900,
      '#min' => 90,
      '#max' => 100,
      '#step' => 0.001,
      '#required' => TRUE,
    ];

    $form['sla_tiers']['standard_max_downtime'] = [
      '#type' => 'number',
      '#title' => $this->t('Standard tier max downtime (minutes/month)'),
      '#default_value' => $config->get('sla_tiers.standard.max_downtime_minutes_month') ?? 43.8,
      '#min' => 0,
      '#max' => 1440,
      '#step' => 0.1,
      '#required' => TRUE,
    ];

    // Premium tier.
    $form['sla_tiers']['premium_uptime'] = [
      '#type' => 'number',
      '#title' => $this->t('Premium tier uptime target (%)'),
      '#default_value' => $config->get('sla_tiers.premium.uptime_target') ?? 99.950,
      '#min' => 90,
      '#max' => 100,
      '#step' => 0.001,
      '#required' => TRUE,
    ];

    $form['sla_tiers']['premium_max_downtime'] = [
      '#type' => 'number',
      '#title' => $this->t('Premium tier max downtime (minutes/month)'),
      '#default_value' => $config->get('sla_tiers.premium.max_downtime_minutes_month') ?? 21.9,
      '#min' => 0,
      '#max' => 1440,
      '#step' => 0.1,
      '#required' => TRUE,
    ];

    // Critical tier.
    $form['sla_tiers']['critical_uptime'] = [
      '#type' => 'number',
      '#title' => $this->t('Critical tier uptime target (%)'),
      '#default_value' => $config->get('sla_tiers.critical.uptime_target') ?? 99.990,
      '#min' => 90,
      '#max' => 100,
      '#step' => 0.001,
      '#required' => TRUE,
    ];

    $form['sla_tiers']['critical_max_downtime'] = [
      '#type' => 'number',
      '#title' => $this->t('Critical tier max downtime (minutes/month)'),
      '#default_value' => $config->get('sla_tiers.critical.max_downtime_minutes_month') ?? 4.38,
      '#min' => 0,
      '#max' => 1440,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_sla.settings')
      // Monitoring.
      ->set('monitoring.web_app.url', $form_state->getValue('web_app_url'))
      ->set('monitoring.web_app.interval_seconds', (int) $form_state->getValue('web_app_interval'))
      ->set('monitoring.web_app.timeout_ms', (int) $form_state->getValue('web_app_timeout'))
      ->set('monitoring.api.url', $form_state->getValue('api_url'))
      ->set('monitoring.api.interval_seconds', (int) $form_state->getValue('api_interval'))
      ->set('monitoring.api.timeout_ms', (int) $form_state->getValue('api_timeout'))
      ->set('monitoring.database.interval_seconds', (int) $form_state->getValue('database_interval'))
      ->set('monitoring.redis.interval_seconds', (int) $form_state->getValue('redis_interval'))
      ->set('monitoring.email.interval_seconds', (int) $form_state->getValue('email_interval'))
      ->set('monitoring.ai_copilot.interval_seconds', (int) $form_state->getValue('ai_copilot_interval'))
      ->set('monitoring.payment.interval_seconds', (int) $form_state->getValue('payment_interval'))
      // SLA Tiers.
      ->set('default_sla_tier', $form_state->getValue('default_sla_tier'))
      ->set('sla_tiers.standard.uptime_target', (float) $form_state->getValue('standard_uptime'))
      ->set('sla_tiers.standard.max_downtime_minutes_month', (float) $form_state->getValue('standard_max_downtime'))
      ->set('sla_tiers.premium.uptime_target', (float) $form_state->getValue('premium_uptime'))
      ->set('sla_tiers.premium.max_downtime_minutes_month', (float) $form_state->getValue('premium_max_downtime'))
      ->set('sla_tiers.critical.uptime_target', (float) $form_state->getValue('critical_uptime'))
      ->set('sla_tiers.critical.max_downtime_minutes_month', (float) $form_state->getValue('critical_max_downtime'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
