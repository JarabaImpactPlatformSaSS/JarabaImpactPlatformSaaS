<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de configuracion del modulo de programas institucionales.
 *
 * ESTRUCTURA: ConfigFormBase con 4 fieldsets: programas, fichas STO,
 *   firma digital y codigos de entidad.
 *
 * LOGICA: Gestiona configuracion global del modulo incluyendo
 *   prefijo de fichas, formato de numeracion, proveedor de firma
 *   y codigos de entidades reguladoras (FUNDAE, FSE+).
 */
class InstitutionalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_institutional.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_institutional_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_institutional.settings');

    // --- Fieldset 1: Programas ---
    $form['programs'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuracion de programas'),
      '#open' => TRUE,
    ];
    $form['programs']['default_program_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de programa por defecto'),
      '#options' => [
        'sto' => 'STO',
        'piil' => 'PIIL',
        'fundae' => 'FUNDAE',
        'fse_plus' => 'FSE+',
        'other' => $this->t('Otro'),
      ],
      '#default_value' => $config->get('default_program_type') ?? 'sto',
    ];

    // --- Fieldset 2: Fichas STO ---
    $form['fichas'] = [
      '#type' => 'details',
      '#title' => $this->t('Fichas tecnicas STO'),
      '#open' => TRUE,
    ];
    $form['fichas']['sto_ficha_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefijo de numeracion'),
      '#default_value' => $config->get('sto_ficha_prefix') ?? 'STO',
      '#maxlength' => 10,
    ];
    $form['fichas']['auto_generate_ficha_number'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generar numero de ficha automaticamente'),
      '#default_value' => $config->get('auto_generate_ficha_number') ?? TRUE,
    ];
    $form['fichas']['pdf_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Plantilla PDF'),
      '#options' => [
        'sae_standard' => $this->t('SAE Estandar'),
        'custom' => $this->t('Personalizada'),
      ],
      '#default_value' => $config->get('pdf_template') ?? 'sae_standard',
    ];

    // --- Fieldset 3: Firma digital ---
    $form['signature'] = [
      '#type' => 'details',
      '#title' => $this->t('Firma digital'),
      '#open' => FALSE,
    ];
    $form['signature']['signature_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Proveedor de firma'),
      '#options' => [
        'pades' => 'PAdES',
        'xades' => 'XAdES',
        'none' => $this->t('Sin firma'),
      ],
      '#default_value' => $config->get('signature_provider') ?? 'pades',
    ];

    // --- Fieldset 4: Codigos de entidad ---
    $form['entities'] = [
      '#type' => 'details',
      '#title' => $this->t('Codigos de entidad'),
      '#open' => FALSE,
    ];
    $form['entities']['fundae_entity_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Codigo entidad FUNDAE'),
      '#default_value' => $config->get('fundae_entity_code') ?? '',
      '#maxlength' => 50,
    ];
    $form['entities']['fse_entity_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Codigo entidad FSE+'),
      '#default_value' => $config->get('fse_entity_code') ?? '',
      '#maxlength' => 50,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_institutional.settings')
      ->set('default_program_type', $form_state->getValue('default_program_type'))
      ->set('sto_ficha_prefix', $form_state->getValue('sto_ficha_prefix'))
      ->set('auto_generate_ficha_number', (bool) $form_state->getValue('auto_generate_ficha_number'))
      ->set('pdf_template', $form_state->getValue('pdf_template'))
      ->set('signature_provider', $form_state->getValue('signature_provider'))
      ->set('fundae_entity_code', $form_state->getValue('fundae_entity_code'))
      ->set('fse_entity_code', $form_state->getValue('fse_entity_code'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
