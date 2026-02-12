<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo Security Compliance.
 *
 * Permite configurar:
 * - Retención de datos (días para audit logs, assessments, anonimización)
 * - Frameworks habilitados
 * - Intervalo de auto-refresh del dashboard
 */
class SecurityComplianceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_security_compliance.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_security_compliance_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_security_compliance.settings');

    // Sección de Retención de Datos.
    $form['retention'] = [
      '#type' => 'details',
      '#title' => $this->t('Retención de Datos'),
      '#open' => TRUE,
    ];

    $form['retention']['audit_log_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Retención de logs de auditoría (días)'),
      '#description' => $this->t('Número de días que se conservan los registros de auditoría. Mínimo 365 para cumplir con SOC2.'),
      '#default_value' => $config->get('audit_log_retention_days') ?? 365,
      '#min' => 90,
      '#max' => 3650,
      '#required' => TRUE,
    ];

    $form['retention']['assessment_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Retención de evaluaciones (días)'),
      '#description' => $this->t('Número de días que se conservan las evaluaciones de compliance.'),
      '#default_value' => $config->get('assessment_retention_days') ?? 730,
      '#min' => 365,
      '#max' => 3650,
      '#required' => TRUE,
    ];

    $form['retention']['anonymize_after_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Anonimización de datos (días)'),
      '#description' => $this->t('Número de días tras la baja de un usuario para anonimizar sus datos personales. Conforme a GDPR.'),
      '#default_value' => $config->get('anonymize_after_days') ?? 90,
      '#min' => 30,
      '#max' => 365,
      '#required' => TRUE,
    ];

    // Sección de Frameworks.
    $form['frameworks'] = [
      '#type' => 'details',
      '#title' => $this->t('Frameworks de Compliance'),
      '#open' => TRUE,
    ];

    $form['frameworks']['enabled_frameworks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Frameworks habilitados'),
      '#description' => $this->t('Seleccione los marcos normativos aplicables a su organización.'),
      '#options' => [
        'soc2' => 'SOC 2 Type II',
        'iso27001' => 'ISO 27001:2022',
        'ens' => 'ENS (Esquema Nacional de Seguridad)',
        'gdpr' => 'GDPR / RGPD',
      ],
      '#default_value' => $config->get('enabled_frameworks') ?? ['soc2', 'iso27001', 'ens', 'gdpr'],
    ];

    // Sección de Dashboard.
    $form['dashboard'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración del Dashboard'),
      '#open' => TRUE,
    ];

    $form['dashboard']['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalo de auto-refresh (segundos)'),
      '#description' => $this->t('Intervalo en segundos para actualizar automáticamente los datos del dashboard. 0 para desactivar.'),
      '#default_value' => $config->get('refresh_interval') ?? 30,
      '#min' => 0,
      '#max' => 300,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_security_compliance.settings')
      ->set('audit_log_retention_days', (int) $form_state->getValue('audit_log_retention_days'))
      ->set('assessment_retention_days', (int) $form_state->getValue('assessment_retention_days'))
      ->set('anonymize_after_days', (int) $form_state->getValue('anonymize_after_days'))
      ->set('enabled_frameworks', array_filter($form_state->getValue('enabled_frameworks')))
      ->set('refresh_interval', (int) $form_state->getValue('refresh_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
