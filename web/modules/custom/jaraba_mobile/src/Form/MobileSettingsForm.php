<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Jaraba Mobile settings.
 *
 * Provides admin UI for configuring FCM credentials, APNs certificates,
 * deep linking base URL, and per-channel push notification settings.
 */
class MobileSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_mobile.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_mobile_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_mobile.settings');

    // FCM Configuration fieldset.
    $form['fcm'] = [
      '#type' => 'details',
      '#title' => $this->t('FCM Configuration'),
      '#description' => $this->t('Firebase Cloud Messaging credentials for push notification delivery.'),
      '#open' => TRUE,
    ];

    $form['fcm']['fcm_server_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FCM Server Key'),
      '#default_value' => $config->get('fcm_server_key') ?? '',
      '#description' => $this->t('The Firebase Cloud Messaging server key or OAuth 2.0 access token.'),
      '#maxlength' => 512,
    ];

    $form['fcm']['fcm_project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FCM Project ID'),
      '#default_value' => $config->get('fcm_project_id') ?? '',
      '#description' => $this->t('The Firebase project ID (e.g., my-project-123).'),
      '#maxlength' => 128,
    ];

    $form['fcm']['fcm_sender_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FCM Sender ID'),
      '#default_value' => $config->get('fcm_sender_id') ?? '',
      '#description' => $this->t('The Firebase sender ID for client-side SDK initialization.'),
      '#maxlength' => 128,
    ];

    // APNs Configuration fieldset.
    $form['apns'] = [
      '#type' => 'details',
      '#title' => $this->t('APNs Configuration'),
      '#description' => $this->t('Apple Push Notification service credentials for iOS devices.'),
      '#open' => FALSE,
    ];

    $form['apns']['apns_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('APNs Certificate'),
      '#default_value' => $config->get('apns_certificate') ?? '',
      '#description' => $this->t('The APNs authentication key (.p8 file content) or certificate path.'),
      '#rows' => 6,
    ];

    $form['apns']['apns_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('APNs Key ID'),
      '#default_value' => $config->get('apns_key_id') ?? '',
      '#description' => $this->t('The 10-character Key ID from Apple Developer portal.'),
      '#maxlength' => 20,
    ];

    $form['apns']['apns_team_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('APNs Team ID'),
      '#default_value' => $config->get('apns_team_id') ?? '',
      '#description' => $this->t('The 10-character Team ID from Apple Developer portal.'),
      '#maxlength' => 20,
    ];

    // Deep Linking fieldset.
    $form['deep_linking'] = [
      '#type' => 'details',
      '#title' => $this->t('Deep Linking'),
      '#description' => $this->t('Configuration for mobile app deep links and universal links.'),
      '#open' => FALSE,
    ];

    $form['deep_linking']['deep_link_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Deep Link Base URL'),
      '#default_value' => $config->get('deep_link_base_url') ?? '',
      '#description' => $this->t('The base URL for universal links (e.g., https://app.pepejaraba.com). Leave empty to use the current site URL.'),
      '#maxlength' => 256,
    ];

    // Push Channels fieldset.
    $form['push_channels'] = [
      '#type' => 'details',
      '#title' => $this->t('Push Channels'),
      '#description' => $this->t('Configure notification channels and daily rate limits. Set max per day to 0 for unlimited.'),
      '#open' => TRUE,
    ];

    $channels = [
      'jobs' => $this->t('Jobs'),
      'orders' => $this->t('Orders'),
      'alerts' => $this->t('Alerts'),
      'marketing' => $this->t('Marketing'),
      'general' => $this->t('General'),
    ];

    foreach ($channels as $channelKey => $channelLabel) {
      $form['push_channels'][$channelKey] = [
        '#type' => 'fieldset',
        '#title' => $channelLabel,
        '#attributes' => ['class' => ['container-inline']],
      ];

      $form['push_channels'][$channelKey][$channelKey . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $config->get('push_channels.' . $channelKey . '.enabled') ?? TRUE,
      ];

      $form['push_channels'][$channelKey][$channelKey . '_max_per_day'] = [
        '#type' => 'number',
        '#title' => $this->t('Max per day'),
        '#default_value' => $config->get('push_channels.' . $channelKey . '.max_per_day') ?? 0,
        '#min' => 0,
        '#max' => 1000,
        '#description' => $this->t('0 = unlimited'),
        '#size' => 5,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('jaraba_mobile.settings');

    // FCM settings.
    $config->set('fcm_server_key', $form_state->getValue('fcm_server_key'));
    $config->set('fcm_project_id', $form_state->getValue('fcm_project_id'));
    $config->set('fcm_sender_id', $form_state->getValue('fcm_sender_id'));

    // APNs settings.
    $config->set('apns_certificate', $form_state->getValue('apns_certificate'));
    $config->set('apns_key_id', $form_state->getValue('apns_key_id'));
    $config->set('apns_team_id', $form_state->getValue('apns_team_id'));

    // Deep linking.
    $config->set('deep_link_base_url', $form_state->getValue('deep_link_base_url'));

    // Push channels.
    $channels = ['jobs', 'orders', 'alerts', 'marketing', 'general'];
    foreach ($channels as $channel) {
      $config->set('push_channels.' . $channel . '.enabled', (bool) $form_state->getValue($channel . '_enabled'));
      $config->set('push_channels.' . $channel . '.max_per_day', (int) $form_state->getValue($channel . '_max_per_day'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
