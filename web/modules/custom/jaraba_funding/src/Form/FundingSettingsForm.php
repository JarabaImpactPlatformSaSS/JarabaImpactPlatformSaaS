<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de configuracion del modulo de Fondos.
 *
 * Estructura: ConfigFormBase que gestiona jaraba_funding.settings.
 *   Permite configurar alertas, limites del dashboard, generacion
 *   con IA y moneda por defecto desde la UI de Drupal.
 *
 * Logica: Los valores se almacenan en config y se consumen desde
 *   los servicios del modulo. Ningun valor esta hardcodeado en codigo.
 */
class FundingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_funding.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_funding_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_funding.settings');

    $form['alerts'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Alertas'),
      '#open' => TRUE,
    ];

    $form['alerts']['alert_days_default'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Dias de alerta por defecto'),
      '#description' => new TranslatableMarkup('Dias antes del deadline para generar alertas automaticas.'),
      '#default_value' => $config->get('alert_days_default') ?? 15,
      '#min' => 1,
      '#max' => 90,
      '#required' => TRUE,
    ];

    $form['dashboard'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Dashboard'),
      '#open' => TRUE,
    ];

    $form['dashboard']['max_opportunities_dashboard'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Convocatorias en dashboard'),
      '#description' => new TranslatableMarkup('Numero maximo de convocatorias a mostrar en el dashboard.'),
      '#default_value' => $config->get('max_opportunities_dashboard') ?? 20,
      '#min' => 5,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['dashboard']['max_applications_dashboard'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Solicitudes en dashboard'),
      '#description' => new TranslatableMarkup('Numero maximo de solicitudes a mostrar en el dashboard.'),
      '#default_value' => $config->get('max_applications_dashboard') ?? 10,
      '#min' => 5,
      '#max' => 50,
      '#required' => TRUE,
    ];

    $form['ai'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Inteligencia Artificial'),
      '#open' => TRUE,
    ];

    $form['ai']['ai_report_enabled'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Habilitar generacion con IA'),
      '#description' => new TranslatableMarkup('Permite generar memorias tecnicas con asistencia de inteligencia artificial.'),
      '#default_value' => $config->get('ai_report_enabled') ?? TRUE,
    ];

    $form['ai']['ai_model_preference'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Modelo IA preferido'),
      '#description' => new TranslatableMarkup('Modelo de IA para generacion de memorias tecnicas.'),
      '#options' => [
        'gemini-2.0-flash' => 'Gemini 2.0 Flash',
        'gemini-2.0-pro' => 'Gemini 2.0 Pro',
        'claude-sonnet' => 'Claude Sonnet',
      ],
      '#default_value' => $config->get('ai_model_preference') ?? 'gemini-2.0-flash',
    ];

    $form['financial'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Configuracion financiera'),
      '#open' => TRUE,
    ];

    $form['financial']['budget_currency'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Moneda por defecto'),
      '#description' => new TranslatableMarkup('Moneda para importes de convocatorias y presupuestos.'),
      '#options' => [
        'EUR' => 'Euro (EUR)',
        'USD' => 'Dolar (USD)',
        'GBP' => 'Libra (GBP)',
      ],
      '#default_value' => $config->get('budget_currency') ?? 'EUR',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_funding.settings')
      ->set('alert_days_default', (int) $form_state->getValue('alert_days_default'))
      ->set('max_opportunities_dashboard', (int) $form_state->getValue('max_opportunities_dashboard'))
      ->set('max_applications_dashboard', (int) $form_state->getValue('max_applications_dashboard'))
      ->set('ai_report_enabled', (bool) $form_state->getValue('ai_report_enabled'))
      ->set('ai_model_preference', $form_state->getValue('ai_model_preference'))
      ->set('budget_currency', $form_state->getValue('budget_currency'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
