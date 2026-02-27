<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the Support system.
 *
 * FIELD-UI-SETTINGS-TAB-001: Required settings tab for Field UI.
 */
class SupportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_support.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_support_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_support.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['default_vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Vertical'),
      '#options' => [
        'platform' => $this->t('Platform'),
        'empleabilidad' => $this->t('Empleabilidad'),
        'emprendimiento' => $this->t('Emprendimiento'),
        'agro' => $this->t('AgroConecta'),
        'comercio' => $this->t('ComercioConecta'),
        'servicios' => $this->t('ServiciosConecta'),
        'billing' => $this->t('Billing'),
        'formacion' => $this->t('Formación'),
      ],
      '#default_value' => $config->get('default_vertical') ?? 'platform',
    ];

    $form['general']['ai_auto_classify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI auto-classification on ticket creation'),
      '#default_value' => $config->get('ai_auto_classify') ?? TRUE,
    ];

    $form['general']['ai_auto_resolve'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI auto-resolution (offer solution if confidence > 0.85)'),
      '#default_value' => $config->get('ai_auto_resolve') ?? TRUE,
    ];

    $form['general']['deflection_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable KB deflection before ticket creation'),
      '#default_value' => $config->get('deflection_enabled') ?? TRUE,
    ];

    $form['attachments'] = [
      '#type' => 'details',
      '#title' => $this->t('Attachment Settings'),
      '#open' => TRUE,
    ];

    $form['attachments']['max_files_per_ticket'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum files per ticket'),
      '#default_value' => $config->get('max_files_per_ticket') ?? 10,
      '#min' => 1,
      '#max' => 50,
    ];

    $form['attachments']['virus_scanning_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ClamAV virus scanning'),
      '#default_value' => $config->get('virus_scanning_enabled') ?? TRUE,
    ];

    $form['attachments']['clamav_socket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ClamAV Socket Path'),
      '#default_value' => $config->get('clamav_socket') ?? '/var/run/clamav/clamd.ctl',
    ];

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification Settings'),
      '#open' => TRUE,
    ];

    $form['notifications']['email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email notifications for ticket updates'),
      '#default_value' => $config->get('email_notifications') ?? TRUE,
    ];

    $form['notifications']['csat_delay_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('CSAT survey delay (hours after resolution)'),
      '#default_value' => $config->get('csat_delay_hours') ?? 24,
      '#min' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_support.settings')
      ->set('default_vertical', $form_state->getValue('default_vertical'))
      ->set('ai_auto_classify', (bool) $form_state->getValue('ai_auto_classify'))
      ->set('ai_auto_resolve', (bool) $form_state->getValue('ai_auto_resolve'))
      ->set('deflection_enabled', (bool) $form_state->getValue('deflection_enabled'))
      ->set('max_files_per_ticket', (int) $form_state->getValue('max_files_per_ticket'))
      ->set('virus_scanning_enabled', (bool) $form_state->getValue('virus_scanning_enabled'))
      ->set('clamav_socket', $form_state->getValue('clamav_socket'))
      ->set('email_notifications', (bool) $form_state->getValue('email_notifications'))
      ->set('csat_delay_hours', (int) $form_state->getValue('csat_delay_hours'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
