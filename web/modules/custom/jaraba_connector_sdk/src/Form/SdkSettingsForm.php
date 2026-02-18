<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the Connector SDK module.
 *
 * Manages Stripe Connect settings and revenue sharing tier percentages.
 */
class SdkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_connector_sdk.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_connector_sdk_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_connector_sdk.settings');

    // Stripe Connect settings.
    $form['stripe'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe Connect'),
      '#open' => TRUE,
    ];

    $form['stripe']['stripe_connect_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Stripe Connect'),
      '#description' => $this->t('Enable revenue sharing payouts via Stripe Connect.'),
      '#default_value' => $config->get('stripe_connect_enabled') ?? FALSE,
    ];

    $form['stripe']['stripe_connect_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Connect Client ID'),
      '#description' => $this->t('The Stripe Connect platform client ID.'),
      '#default_value' => $config->get('stripe_connect_client_id') ?? '',
      '#maxlength' => 255,
    ];

    // Revenue tiers.
    $form['revenue'] = [
      '#type' => 'details',
      '#title' => $this->t('Revenue Sharing Tiers'),
      '#open' => TRUE,
    ];

    $tiers = ['standard', 'premium', 'strategic'];
    foreach ($tiers as $tier) {
      $form['revenue'][$tier] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@tier Tier', ['@tier' => ucfirst($tier)]),
      ];

      $form['revenue'][$tier][$tier . '_developer_pct'] = [
        '#type' => 'number',
        '#title' => $this->t('Developer Percentage'),
        '#default_value' => $config->get('revenue_tiers.' . $tier . '.developer_pct') ?? 70,
        '#min' => 0,
        '#max' => 100,
      ];

      $form['revenue'][$tier][$tier . '_platform_pct'] = [
        '#type' => 'number',
        '#title' => $this->t('Platform Percentage'),
        '#default_value' => $config->get('revenue_tiers.' . $tier . '.platform_pct') ?? 30,
        '#min' => 0,
        '#max' => 100,
      ];

      $form['revenue'][$tier][$tier . '_requirement'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Requirement'),
        '#default_value' => $config->get('revenue_tiers.' . $tier . '.requirement') ?? '',
        '#maxlength' => 255,
      ];
    }

    // Certification auto tests.
    $form['certification'] = [
      '#type' => 'details',
      '#title' => $this->t('Certification Auto Tests'),
      '#open' => FALSE,
    ];

    $form['certification']['security_scan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Security Scan'),
      '#description' => $this->t('Run security scan during certification.'),
      '#default_value' => $config->get('certification_auto_tests.security_scan') ?? TRUE,
    ];

    $form['certification']['performance_test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Performance Test'),
      '#description' => $this->t('Run performance test during certification.'),
      '#default_value' => $config->get('certification_auto_tests.performance_test') ?? TRUE,
    ];

    $form['certification']['api_compliance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('API Compliance'),
      '#description' => $this->t('Run API compliance check during certification.'),
      '#default_value' => $config->get('certification_auto_tests.api_compliance') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $tiers = ['standard', 'premium', 'strategic'];
    foreach ($tiers as $tier) {
      $devPct = (int) $form_state->getValue($tier . '_developer_pct');
      $platPct = (int) $form_state->getValue($tier . '_platform_pct');

      if (($devPct + $platPct) !== 100) {
        $form_state->setErrorByName(
          $tier . '_developer_pct',
          $this->t('Developer and platform percentages for @tier tier must sum to 100.', [
            '@tier' => $tier,
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('jaraba_connector_sdk.settings');

    $config
      ->set('stripe_connect_enabled', (bool) $form_state->getValue('stripe_connect_enabled'))
      ->set('stripe_connect_client_id', $form_state->getValue('stripe_connect_client_id'));

    $tiers = ['standard', 'premium', 'strategic'];
    foreach ($tiers as $tier) {
      $config
        ->set('revenue_tiers.' . $tier . '.developer_pct', (int) $form_state->getValue($tier . '_developer_pct'))
        ->set('revenue_tiers.' . $tier . '.platform_pct', (int) $form_state->getValue($tier . '_platform_pct'))
        ->set('revenue_tiers.' . $tier . '.requirement', $form_state->getValue($tier . '_requirement'));
    }

    $config
      ->set('certification_auto_tests.security_scan', (bool) $form_state->getValue('security_scan'))
      ->set('certification_auto_tests.performance_test', (bool) $form_state->getValue('performance_test'))
      ->set('certification_auto_tests.api_compliance', (bool) $form_state->getValue('api_compliance'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
