<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo Jaraba Secure Messaging.
 *
 * Ruta: /admin/config/jaraba/messaging
 * Todos los parámetros son configurables desde la UI sin tocar código.
 */
class MessagingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_messaging.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_messaging_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_messaging.settings');

    // Encryption settings.
    $form['encryption'] = [
      '#type' => 'details',
      '#title' => $this->t('Encryption'),
      '#open' => TRUE,
    ];
    $form['encryption']['algorithm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encryption algorithm'),
      '#default_value' => $config->get('encryption.algorithm') ?? 'aes-256-gcm',
      '#disabled' => TRUE,
      '#description' => $this->t('AES-256-GCM is the only supported algorithm.'),
    ];
    $form['encryption']['argon2id_memory'] = [
      '#type' => 'number',
      '#title' => $this->t('Argon2id memory (KB)'),
      '#default_value' => $config->get('encryption.argon2id_memory') ?? 65536,
      '#min' => 8192,
      '#max' => 1048576,
      '#description' => $this->t('Memory cost for key derivation. Higher = more secure but slower.'),
    ];
    $form['encryption']['argon2id_iterations'] = [
      '#type' => 'number',
      '#title' => $this->t('Argon2id iterations'),
      '#default_value' => $config->get('encryption.argon2id_iterations') ?? 3,
      '#min' => 1,
      '#max' => 10,
    ];

    // Rate limiting.
    $form['rate_limiting'] = [
      '#type' => 'details',
      '#title' => $this->t('Rate Limiting'),
      '#open' => TRUE,
    ];
    $form['rate_limiting']['messages_per_minute_per_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Messages per minute per user'),
      '#default_value' => $config->get('rate_limiting.messages_per_minute_per_user') ?? 30,
      '#min' => 1,
      '#max' => 1000,
    ];
    $form['rate_limiting']['messages_per_minute_per_conversation'] = [
      '#type' => 'number',
      '#title' => $this->t('Messages per minute per conversation'),
      '#default_value' => $config->get('rate_limiting.messages_per_minute_per_conversation') ?? 100,
      '#min' => 1,
      '#max' => 5000,
    ];

    // GDPR Retention.
    $form['retention'] = [
      '#type' => 'details',
      '#title' => $this->t('GDPR Retention'),
      '#open' => TRUE,
    ];
    $form['retention']['default_message_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Default message retention (days)'),
      '#default_value' => $config->get('retention.default_message_retention_days') ?? 730,
      '#min' => 30,
      '#max' => 3650,
      '#description' => $this->t('Messages older than this will be purged. 730 = 2 years.'),
    ];
    $form['retention']['audit_log_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Audit log retention (days)'),
      '#default_value' => $config->get('retention.audit_log_retention_days') ?? 2555,
      '#min' => 365,
      '#max' => 7300,
      '#description' => $this->t('Audit trail entries retained. 2555 = 7 years.'),
    ];
    $form['retention']['auto_close_inactive_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-close inactive conversations (days)'),
      '#default_value' => $config->get('retention.auto_close_inactive_days') ?? 90,
      '#min' => 7,
      '#max' => 365,
    ];

    // WebSocket.
    $form['websocket'] = [
      '#type' => 'details',
      '#title' => $this->t('WebSocket Server'),
      '#open' => FALSE,
    ];
    $form['websocket']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebSocket host'),
      '#default_value' => $config->get('websocket.host') ?? '0.0.0.0',
    ];
    $form['websocket']['port'] = [
      '#type' => 'number',
      '#title' => $this->t('WebSocket port'),
      '#default_value' => $config->get('websocket.port') ?? 8090,
      '#min' => 1024,
      '#max' => 65535,
    ];
    $form['websocket']['ping_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Ping interval (seconds)'),
      '#default_value' => $config->get('websocket.ping_interval') ?? 30,
      '#min' => 5,
      '#max' => 120,
    ];
    $form['websocket']['online_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Online presence TTL (seconds)'),
      '#default_value' => $config->get('websocket.online_ttl') ?? 120,
      '#min' => 30,
      '#max' => 600,
    ];

    // Notifications.
    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => FALSE,
    ];
    $form['notifications']['offline_delay_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Offline notification delay (seconds)'),
      '#default_value' => $config->get('notifications.offline_delay_seconds') ?? 30,
      '#min' => 0,
      '#max' => 300,
      '#description' => $this->t('Wait this long before sending offline notification (user may come back online).'),
    ];
    $form['notifications']['digest_interval_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Unread digest interval (hours)'),
      '#default_value' => $config->get('notifications.digest_interval_hours') ?? 4,
      '#min' => 1,
      '#max' => 24,
    ];

    // General settings.
    $form['edit_window_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Message edit window (minutes)'),
      '#default_value' => $config->get('edit_window_minutes') ?? 15,
      '#min' => 1,
      '#max' => 60,
      '#description' => $this->t('Users can edit their messages within this time window.'),
    ];
    $form['max_participants_per_conversation'] = [
      '#type' => 'number',
      '#title' => $this->t('Max participants per conversation'),
      '#default_value' => $config->get('max_participants_per_conversation') ?? 50,
      '#min' => 2,
      '#max' => 200,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_messaging.settings')
      ->set('encryption.algorithm', $form_state->getValue('algorithm'))
      ->set('encryption.argon2id_memory', (int) $form_state->getValue('argon2id_memory'))
      ->set('encryption.argon2id_iterations', (int) $form_state->getValue('argon2id_iterations'))
      ->set('rate_limiting.messages_per_minute_per_user', (int) $form_state->getValue('messages_per_minute_per_user'))
      ->set('rate_limiting.messages_per_minute_per_conversation', (int) $form_state->getValue('messages_per_minute_per_conversation'))
      ->set('retention.default_message_retention_days', (int) $form_state->getValue('default_message_retention_days'))
      ->set('retention.audit_log_retention_days', (int) $form_state->getValue('audit_log_retention_days'))
      ->set('retention.auto_close_inactive_days', (int) $form_state->getValue('auto_close_inactive_days'))
      ->set('websocket.host', $form_state->getValue('host'))
      ->set('websocket.port', (int) $form_state->getValue('port'))
      ->set('websocket.ping_interval', (int) $form_state->getValue('ping_interval'))
      ->set('websocket.online_ttl', (int) $form_state->getValue('online_ttl'))
      ->set('notifications.offline_delay_seconds', (int) $form_state->getValue('offline_delay_seconds'))
      ->set('notifications.digest_interval_hours', (int) $form_state->getValue('digest_interval_hours'))
      ->set('edit_window_minutes', (int) $form_state->getValue('edit_window_minutes'))
      ->set('max_participants_per_conversation', (int) $form_state->getValue('max_participants_per_conversation'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
