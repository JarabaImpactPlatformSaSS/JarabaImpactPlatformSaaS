<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * WhatsApp module configuration form.
 *
 * SECRET-MGMT-001: Credentials are NOT in this config form.
 * They are set via getenv() in settings.secrets.php.
 */
class WhatsAppSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_whatsapp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_whatsapp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_whatsapp.settings');

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API Configuration'),
      '#open' => TRUE,
    ];

    $form['api']['whatsapp_api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WhatsApp API Version'),
      '#default_value' => $config->get('whatsapp_api_version') ?? 'v21.0',
      '#description' => $this->t('Meta Graph API version (e.g., v21.0).'),
    ];

    $form['api']['whatsapp_phone_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Phone Number'),
      '#default_value' => $config->get('whatsapp_phone_display') ?? '',
      '#description' => $this->t('Phone number for display purposes.'),
    ];

    $form['agent'] = [
      '#type' => 'details',
      '#title' => $this->t('Agent Configuration'),
      '#open' => TRUE,
    ];

    $form['agent']['agent_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI Agent'),
      '#default_value' => $config->get('agent_enabled') ?? TRUE,
    ];

    $form['agent']['auto_classify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-classify leads'),
      '#default_value' => $config->get('auto_classify') ?? TRUE,
    ];

    $form['agent']['max_messages_before_escalation'] = [
      '#type' => 'number',
      '#title' => $this->t('Max messages before auto-escalation'),
      '#default_value' => $config->get('max_messages_before_escalation') ?? 8,
      '#min' => 3,
      '#max' => 20,
    ];

    $form['escalation'] = [
      '#type' => 'details',
      '#title' => $this->t('Escalation'),
      '#open' => TRUE,
    ];

    $form['escalation']['escalation_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Escalation Email'),
      '#default_value' => $config->get('escalation_email') ?? '',
    ];

    $form['escalation']['escalation_whatsapp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Escalation WhatsApp Number'),
      '#default_value' => $config->get('escalation_whatsapp') ?? '',
      '#description' => $this->t('Number in E.164 format (without +).'),
    ];

    $form['timing'] = [
      '#type' => 'details',
      '#title' => $this->t('Timing Rules'),
    ];

    $form['timing']['inactivity_hours_close'] = [
      '#type' => 'number',
      '#title' => $this->t('Hours of inactivity before closing'),
      '#default_value' => $config->get('inactivity_hours_close') ?? 48,
    ];

    $form['timing']['off_hours_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Off-hours start (hour)'),
      '#default_value' => $config->get('off_hours_start') ?? 22,
      '#min' => 0,
      '#max' => 23,
    ];

    $form['timing']['off_hours_end'] = [
      '#type' => 'number',
      '#title' => $this->t('Off-hours end (hour)'),
      '#default_value' => $config->get('off_hours_end') ?? 7,
      '#min' => 0,
      '#max' => 23,
    ];

    $form['rgpd'] = [
      '#type' => 'details',
      '#title' => $this->t('RGPD'),
    ];

    $form['rgpd']['rgpd_retention_months'] = [
      '#type' => 'number',
      '#title' => $this->t('Data retention (months)'),
      '#default_value' => $config->get('rgpd_retention_months') ?? 18,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_whatsapp.settings')
      ->set('whatsapp_api_version', $form_state->getValue('whatsapp_api_version'))
      ->set('whatsapp_phone_display', $form_state->getValue('whatsapp_phone_display'))
      ->set('agent_enabled', (bool) $form_state->getValue('agent_enabled'))
      ->set('auto_classify', (bool) $form_state->getValue('auto_classify'))
      ->set('max_messages_before_escalation', (int) $form_state->getValue('max_messages_before_escalation'))
      ->set('escalation_email', $form_state->getValue('escalation_email'))
      ->set('escalation_whatsapp', $form_state->getValue('escalation_whatsapp'))
      ->set('inactivity_hours_close', (int) $form_state->getValue('inactivity_hours_close'))
      ->set('off_hours_start', (int) $form_state->getValue('off_hours_start'))
      ->set('off_hours_end', (int) $form_state->getValue('off_hours_end'))
      ->set('rgpd_retention_months', (int) $form_state->getValue('rgpd_retention_months'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
