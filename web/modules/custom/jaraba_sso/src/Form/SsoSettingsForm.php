<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Global SSO Settings Form.
 *
 * Admin configuration form at /admin/config/jaraba/sso for platform-wide
 * SSO settings including SP Entity ID, default certificate, and SCIM
 * bearer token configuration.
 *
 * SETTINGS:
 * - sp_entity_id: Service Provider Entity ID for SAML metadata.
 * - sp_certificate: SP X.509 certificate for signed AuthnRequests.
 * - sp_private_key: SP private key for SAML signature.
 * - scim_bearer_token: Bearer token for SCIM endpoint authentication.
 * - session_binding: Whether to bind sessions to IP address.
 * - default_redirect: Default redirect URL after SSO login.
 */
class SsoSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_sso.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // ====================================================
    // SERVICE PROVIDER (SP) CONFIGURATION
    // ====================================================
    $form['sp'] = [
      '#type' => 'details',
      '#title' => $this->t('Service Provider (SP) Configuration'),
      '#open' => TRUE,
    ];

    $form['sp']['sp_entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SP Entity ID'),
      '#description' => $this->t('The globally unique identifier for this Service Provider. Usually the base URL of the platform (e.g., https://app.jaraba.es/sso). If empty, auto-generated from the current domain.'),
      '#default_value' => $config->get('sp_entity_id') ?? '',
      '#maxlength' => 500,
    ];

    $form['sp']['sp_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SP X.509 Certificate (PEM)'),
      '#description' => $this->t('Optional SP certificate for signing AuthnRequests. Paste the full PEM including BEGIN/END markers.'),
      '#default_value' => $config->get('sp_certificate') ?? '',
      '#rows' => 8,
    ];

    $form['sp']['sp_private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SP Private Key (PEM)'),
      '#description' => $this->t('Optional SP private key for SAML response decryption. Keep this secret.'),
      '#default_value' => $config->get('sp_private_key') ?? '',
      '#rows' => 8,
    ];

    // ====================================================
    // SCIM CONFIGURATION
    // ====================================================
    $form['scim'] = [
      '#type' => 'details',
      '#title' => $this->t('SCIM 2.0 Configuration'),
      '#open' => TRUE,
    ];

    $form['scim']['scim_bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SCIM Bearer Token'),
      '#description' => $this->t('Bearer token for authenticating SCIM provisioning requests from the IdP. Generate a strong random token and configure the same value in your IdP SCIM app.'),
      '#default_value' => $config->get('scim_bearer_token') ?? '',
      '#maxlength' => 500,
    ];

    $form['scim']['scim_max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Max SCIM Results'),
      '#description' => $this->t('Maximum number of resources returned in a single SCIM list response.'),
      '#default_value' => $config->get('scim_max_results') ?? 200,
      '#min' => 10,
      '#max' => 1000,
    ];

    // ====================================================
    // SESSION & SECURITY
    // ====================================================
    $form['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Session & Security'),
      '#open' => FALSE,
    ];

    $form['security']['session_binding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bind sessions to IP address'),
      '#description' => $this->t('If enabled, SSO sessions are invalidated when the client IP changes. More secure but may cause issues with mobile users.'),
      '#default_value' => $config->get('session_binding') ?? FALSE,
    ];

    $form['security']['default_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Redirect After Login'),
      '#description' => $this->t('URL to redirect users after successful SSO login. Default: /'),
      '#default_value' => $config->get('default_redirect') ?? '/',
      '#maxlength' => 500,
    ];

    $form['security']['clock_skew_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Clock Skew Tolerance (seconds)'),
      '#description' => $this->t('Maximum allowed clock difference between SP and IdP for SAML assertion validation.'),
      '#default_value' => $config->get('clock_skew_seconds') ?? 300,
      '#min' => 0,
      '#max' => 600,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::CONFIG_NAME)
      ->set('sp_entity_id', $form_state->getValue('sp_entity_id'))
      ->set('sp_certificate', $form_state->getValue('sp_certificate'))
      ->set('sp_private_key', $form_state->getValue('sp_private_key'))
      ->set('scim_bearer_token', $form_state->getValue('scim_bearer_token'))
      ->set('scim_max_results', (int) $form_state->getValue('scim_max_results'))
      ->set('session_binding', (bool) $form_state->getValue('session_binding'))
      ->set('default_redirect', $form_state->getValue('default_redirect'))
      ->set('clock_skew_seconds', (int) $form_state->getValue('clock_skew_seconds'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
