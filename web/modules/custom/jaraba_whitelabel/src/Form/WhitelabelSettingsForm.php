<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Global whitelabel settings form.
 */
class WhitelabelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_whitelabel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_whitelabel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_whitelabel.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['enable_custom_domains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom domains'),
      '#description' => $this->t('Allow tenants to configure custom domains.'),
      '#default_value' => $config->get('enable_custom_domains') ?? TRUE,
    ];

    $form['general']['enable_email_templates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email templates'),
      '#description' => $this->t('Allow tenants to customise transactional email templates.'),
      '#default_value' => $config->get('enable_email_templates') ?? TRUE,
    ];

    $form['general']['allow_hide_powered_by'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow hiding "Powered by" branding'),
      '#description' => $this->t('Premium tenants may hide the platform branding.'),
      '#default_value' => $config->get('allow_hide_powered_by') ?? FALSE,
    ];

    $form['dns'] = [
      '#type' => 'details',
      '#title' => $this->t('DNS Verification'),
      '#open' => TRUE,
    ];

    $form['dns']['dns_verification_method'] = [
      '#type' => 'select',
      '#title' => $this->t('DNS verification method'),
      '#options' => [
        'txt' => $this->t('TXT record'),
        'cname' => $this->t('CNAME record'),
      ],
      '#default_value' => $config->get('dns_verification_method') ?? 'txt',
    ];

    $form['dns']['dns_verification_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DNS verification prefix'),
      '#description' => $this->t('Prefix for the verification DNS record (e.g. _jaraba-verify).'),
      '#default_value' => $config->get('dns_verification_prefix') ?? '_jaraba-verify',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_whitelabel.settings')
      ->set('enable_custom_domains', (bool) $form_state->getValue('enable_custom_domains'))
      ->set('enable_email_templates', (bool) $form_state->getValue('enable_email_templates'))
      ->set('allow_hide_powered_by', (bool) $form_state->getValue('allow_hide_powered_by'))
      ->set('dns_verification_method', $form_state->getValue('dns_verification_method'))
      ->set('dns_verification_prefix', $form_state->getValue('dns_verification_prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
