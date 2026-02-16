<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración global del módulo legal.
 *
 * Permite configurar: re-aceptación de ToS, tier SLA por defecto,
 * créditos SLA, días de gracia offboarding, formatos de exportación,
 * y parámetros del canal de denuncias.
 *
 * Ruta: /admin/config/jaraba/legal
 */
class LegalSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_legal.settings';

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
    return 'jaraba_legal_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // =================================================================
    // TOS — Terms of Service
    // =================================================================
    $form['tos'] = [
      '#type' => 'details',
      '#title' => $this->t('Terms of Service (ToS)'),
      '#open' => TRUE,
    ];

    $form['tos']['tos_re_acceptance_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Re-aceptación requerida'),
      '#description' => $this->t('Forzar re-aceptación de ToS cuando se publique una nueva versión.'),
      '#default_value' => $config->get('tos_re_acceptance_required') ?? TRUE,
    ];

    // =================================================================
    // SLA — Service Level Agreement
    // =================================================================
    $form['sla'] = [
      '#type' => 'details',
      '#title' => $this->t('Service Level Agreement (SLA)'),
      '#open' => TRUE,
    ];

    $form['sla']['sla_default_tier'] = [
      '#type' => 'select',
      '#title' => $this->t('Tier SLA por defecto'),
      '#description' => $this->t('Porcentaje de disponibilidad comprometido por defecto.'),
      '#options' => [
        '99.0' => '99.0%',
        '99.5' => '99.5%',
        '99.9' => '99.9%',
        '99.95' => '99.95%',
        '99.99' => '99.99%',
      ],
      '#default_value' => $config->get('sla_default_tier') ?? '99.5',
    ];

    $form['sla']['sla_credit_percentage'] = [
      '#type' => 'number',
      '#title' => $this->t('Porcentaje de crédito por incumplimiento'),
      '#description' => $this->t('Porcentaje de la factura mensual que se devuelve como crédito por incumplimiento SLA.'),
      '#default_value' => $config->get('sla_credit_percentage') ?? 10,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    // =================================================================
    // OFFBOARDING — Proceso de baja
    // =================================================================
    $form['offboarding'] = [
      '#type' => 'details',
      '#title' => $this->t('Offboarding'),
      '#open' => TRUE,
    ];

    $form['offboarding']['offboarding_grace_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Días de gracia'),
      '#description' => $this->t('Días de gracia antes de iniciar la eliminación de datos tras solicitar la baja.'),
      '#default_value' => $config->get('offboarding_grace_days') ?? 30,
      '#min' => 7,
      '#max' => 90,
      '#required' => TRUE,
    ];

    $form['offboarding']['offboarding_export_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Formatos de exportación'),
      '#description' => $this->t('Formatos disponibles para la exportación de datos durante el offboarding.'),
      '#options' => [
        'json' => 'JSON',
        'csv' => 'CSV',
        'xml' => 'XML',
        'sql' => 'SQL',
      ],
      '#default_value' => $config->get('offboarding_export_formats') ?? ['json', 'csv'],
    ];

    // =================================================================
    // WHISTLEBLOWER — Canal de denuncias
    // =================================================================
    $form['whistleblower'] = [
      '#type' => 'details',
      '#title' => $this->t('Canal de denuncias'),
      '#open' => TRUE,
    ];

    $form['whistleblower']['whistleblower_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar canal de denuncias'),
      '#description' => $this->t('Activa el formulario público de denuncias conforme a la Directiva EU 2019/1937.'),
      '#default_value' => $config->get('whistleblower_enabled') ?? TRUE,
    ];

    $form['whistleblower']['whistleblower_anonymous_tracking'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Seguimiento anónimo'),
      '#description' => $this->t('Permite a los denunciantes consultar el estado de su reporte con un código de seguimiento.'),
      '#default_value' => $config->get('whistleblower_anonymous_tracking') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    $config->set('tos_re_acceptance_required', (bool) $form_state->getValue('tos_re_acceptance_required'));
    $config->set('sla_default_tier', $form_state->getValue('sla_default_tier'));
    $config->set('sla_credit_percentage', (int) $form_state->getValue('sla_credit_percentage'));
    $config->set('offboarding_grace_days', (int) $form_state->getValue('offboarding_grace_days'));

    // Filtrar formatos seleccionados (eliminar valores no seleccionados).
    $formats = array_values(array_filter($form_state->getValue('offboarding_export_formats')));
    $config->set('offboarding_export_formats', $formats);

    $config->set('whistleblower_enabled', (bool) $form_state->getValue('whistleblower_enabled'));
    $config->set('whistleblower_anonymous_tracking', (bool) $form_state->getValue('whistleblower_anonymous_tracking'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
