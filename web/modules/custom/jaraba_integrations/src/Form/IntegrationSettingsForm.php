<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración general del marketplace de integraciones.
 *
 * PROPÓSITO:
 * Configuración global: webhook retry policy, rate limiting de API,
 * OAuth token lifetime, etc.
 */
class IntegrationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_integrations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_integrations_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_integrations.settings');

    // Grupo: Webhooks.
    $form['webhooks'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Webhooks'),
      '#open' => TRUE,
    ];

    $form['webhooks']['webhook_max_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Máximo de Reintentos'),
      '#description' => $this->t('Número máximo de reintentos para entregas fallidas.'),
      '#default_value' => $config->get('webhook_max_retries') ?? 3,
      '#min' => 1,
      '#max' => 10,
    ];

    $form['webhooks']['webhook_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout (segundos)'),
      '#description' => $this->t('Tiempo máximo de espera para respuesta del webhook.'),
      '#default_value' => $config->get('webhook_timeout') ?? 30,
      '#min' => 5,
      '#max' => 120,
    ];

    $form['webhooks']['webhook_disable_after_failures'] = [
      '#type' => 'number',
      '#title' => $this->t('Desactivar después de N fallos'),
      '#description' => $this->t('Desactivar automáticamente la suscripción después de N fallos consecutivos.'),
      '#default_value' => $config->get('webhook_disable_after_failures') ?? 10,
      '#min' => 3,
      '#max' => 100,
    ];

    // Grupo: OAuth2.
    $form['oauth'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración OAuth2'),
      '#open' => TRUE,
    ];

    $form['oauth']['oauth_token_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Duración del Access Token (segundos)'),
      '#description' => $this->t('Tiempo de vida del access token OAuth2.'),
      '#default_value' => $config->get('oauth_token_lifetime') ?? 3600,
      '#min' => 300,
      '#max' => 86400,
    ];

    $form['oauth']['oauth_refresh_token_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Duración del Refresh Token (días)'),
      '#description' => $this->t('Tiempo de vida del refresh token.'),
      '#default_value' => $config->get('oauth_refresh_token_lifetime') ?? 30,
      '#min' => 1,
      '#max' => 365,
    ];

    // Grupo: Rate Limiting.
    $form['rate_limiting'] = [
      '#type' => 'details',
      '#title' => $this->t('Rate Limiting'),
      '#open' => FALSE,
    ];

    $form['rate_limiting']['api_rate_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Límite de Peticiones por Minuto'),
      '#description' => $this->t('Máximo de peticiones API por tenant por minuto.'),
      '#default_value' => $config->get('api_rate_limit') ?? 60,
      '#min' => 10,
      '#max' => 1000,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_integrations.settings')
      ->set('webhook_max_retries', $form_state->getValue('webhook_max_retries'))
      ->set('webhook_timeout', $form_state->getValue('webhook_timeout'))
      ->set('webhook_disable_after_failures', $form_state->getValue('webhook_disable_after_failures'))
      ->set('oauth_token_lifetime', $form_state->getValue('oauth_token_lifetime'))
      ->set('oauth_refresh_token_lifetime', $form_state->getValue('oauth_refresh_token_lifetime'))
      ->set('api_rate_limit', $form_state->getValue('api_rate_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
