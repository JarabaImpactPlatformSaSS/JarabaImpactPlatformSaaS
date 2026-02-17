<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de configuracion general del modulo Multi-Region.
 *
 * Estructura: ConfigFormBase que gestiona jaraba_multiregion.settings.
 *   Agrupa la configuracion en 3 fieldsets: valores por defecto,
 *   cache y automatizacion.
 *
 * Logica: Los valores se almacenan en config y se consumen desde
 *   los servicios del modulo (RegionManagerService, CurrencyConverterService,
 *   ViesValidatorService). Ningun valor esta hardcodeado en codigo.
 *   Los defaults razonables garantizan funcionamiento out-of-the-box.
 *
 * Sintaxis: Todos los strings usan TranslatableMarkup. Los campos
 *   numericos definen rangos minimos y maximos para validacion.
 */
class RegionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * Estructura: Define las configuraciones editables por este form.
   * Logica: Solo se expone jaraba_multiregion.settings para evitar
   *   modificaciones accidentales a otras configuraciones.
   * Sintaxis: Retorna array con un unico nombre de config.
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_multiregion.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Identificador unico del formulario.
   * Logica: Sigue la convencion modulo_tipo_form del ecosistema.
   * Sintaxis: Retorna string con el ID del formulario.
   */
  public function getFormId(): string {
    return 'jaraba_multiregion_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 3 fieldsets principales.
   * Logica: Cada fieldset agrupa configuraciones relacionadas.
   *   Los valores por defecto cubren jurisdiccion, moneda y region
   *   de datos. Cache controla TTL de VIES y tasas ECB. Automatizacion
   *   habilita la obtencion automatica de tasas de cambio.
   * Sintaxis: Usa #type select, number y checkbox con rangos definidos.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_multiregion.settings');

    // --- Fieldset: Valores por defecto ---
    $form['defaults'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Valores por defecto'),
      '#open' => TRUE,
    ];

    $form['defaults']['default_currency'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Moneda por defecto'),
      '#description' => new TranslatableMarkup('Moneda base para nuevos tenants.'),
      '#options' => [
        'EUR' => 'Euro (EUR)',
        'USD' => 'Dolar (USD)',
        'GBP' => 'Libra (GBP)',
        'BRL' => 'Real (BRL)',
      ],
      '#default_value' => $config->get('default_currency') ?? 'EUR',
      '#required' => TRUE,
    ];

    $form['defaults']['default_jurisdiction'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Jurisdiccion por defecto'),
      '#description' => new TranslatableMarkup('Jurisdiccion legal para nuevos tenants.'),
      '#options' => [
        'ES' => 'ES',
        'PT' => 'PT',
        'FR' => 'FR',
        'IT' => 'IT',
        'DE' => 'DE',
        'NL' => 'NL',
        'BE' => 'BE',
        'IE' => 'IE',
        'AT' => 'AT',
        'PL' => 'PL',
      ],
      '#default_value' => $config->get('default_jurisdiction') ?? 'ES',
      '#required' => TRUE,
    ];

    $form['defaults']['default_data_region'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Region de datos por defecto'),
      '#description' => new TranslatableMarkup('Region de almacenamiento de datos para nuevos tenants.'),
      '#options' => [
        'eu-west' => 'EU West',
        'eu-central' => 'EU Central',
        'us-east' => 'US East',
        'latam' => 'LATAM',
      ],
      '#default_value' => $config->get('default_data_region') ?? 'eu-west',
      '#required' => TRUE,
    ];

    // --- Fieldset: Cache ---
    $form['cache'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Cache'),
      '#open' => TRUE,
    ];

    $form['cache']['vies_cache_hours'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Cache de validaciones VIES (horas)'),
      '#description' => new TranslatableMarkup('Tiempo en horas para cachear resultados de validaciones VIES. Rango: 1-168 (1 semana).'),
      '#default_value' => $config->get('vies_cache_hours') ?? 24,
      '#min' => 1,
      '#max' => 168,
      '#required' => TRUE,
    ];

    $form['cache']['ecb_rates_cache_hours'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Cache de tasas ECB (horas)'),
      '#description' => new TranslatableMarkup('Tiempo en horas para cachear tasas de cambio del BCE. Rango: 1-24.'),
      '#default_value' => $config->get('ecb_rates_cache_hours') ?? 4,
      '#min' => 1,
      '#max' => 24,
      '#required' => TRUE,
    ];

    // --- Fieldset: Automatizacion ---
    $form['automation'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Automatizacion'),
      '#open' => TRUE,
    ];

    $form['automation']['auto_fetch_rates'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Obtener tasas automaticamente'),
      '#description' => new TranslatableMarkup('Habilita la obtencion automatica de tasas de cambio desde el BCE via cron.'),
      '#default_value' => $config->get('auto_fetch_rates') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Almacena todos los valores del formulario en config.
   * Logica: Cada valor se castea al tipo correcto (int, bool, string)
   *   antes de persistirlo en jaraba_multiregion.settings. Invoca
   *   parent::submitForm() para mostrar el mensaje de confirmacion.
   * Sintaxis: Encadena set() y finaliza con save().
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_multiregion.settings')
      ->set('default_currency', $form_state->getValue('default_currency'))
      ->set('default_jurisdiction', $form_state->getValue('default_jurisdiction'))
      ->set('default_data_region', $form_state->getValue('default_data_region'))
      ->set('vies_cache_hours', (int) $form_state->getValue('vies_cache_hours'))
      ->set('ecb_rates_cache_hours', (int) $form_state->getValue('ecb_rates_cache_hours'))
      ->set('auto_fetch_rates', (bool) $form_state->getValue('auto_fetch_rates'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
