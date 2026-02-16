<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para exportaciones de tenant y backups.
 *
 * Directriz 3.8: Config editable desde UI Drupal.
 */
class TenantExportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_tenant_export.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tenant_export_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_tenant_export.settings');

    $form['export'] = [
      '#type' => 'details',
      '#title' => $this->t('Exportación de datos'),
      '#open' => TRUE,
    ];

    $form['export']['export_expiration_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas de expiración'),
      '#description' => $this->t('Horas tras las que un paquete de exportación expira y se elimina.'),
      '#default_value' => $config->get('export_expiration_hours') ?? 48,
      '#min' => 1,
      '#max' => 720,
      '#required' => TRUE,
    ];

    $form['export']['rate_limit_per_day'] = [
      '#type' => 'number',
      '#title' => $this->t('Límite de solicitudes por día'),
      '#description' => $this->t('Número máximo de exportaciones que un tenant puede solicitar por día.'),
      '#default_value' => $config->get('rate_limit_per_day') ?? 3,
      '#min' => 1,
      '#max' => 50,
      '#required' => TRUE,
    ];

    $form['export']['max_export_size_mb'] = [
      '#type' => 'number',
      '#title' => $this->t('Tamaño máximo de exportación (MB)'),
      '#description' => $this->t('Tamaño máximo permitido para un paquete de exportación.'),
      '#default_value' => $config->get('max_export_size_mb') ?? 500,
      '#min' => 10,
      '#max' => 5000,
      '#required' => TRUE,
    ];

    $form['export']['analytics_row_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Límite de filas de analytics'),
      '#description' => $this->t('Número máximo de eventos de analytics a incluir en la exportación.'),
      '#default_value' => $config->get('analytics_row_limit') ?? 50000,
      '#min' => 1000,
      '#max' => 500000,
      '#required' => TRUE,
    ];

    $sectionOptions = [
      'core' => $this->t('Datos principales (tenant, billing, whitelabel)'),
      'analytics' => $this->t('Analytics (eventos, dashboards, funnels)'),
      'knowledge' => $this->t('Base de conocimiento (documentos, KB, FAQs)'),
      'operational' => $this->t('Operacional (audit log, email campaigns, CRM)'),
      'vertical' => $this->t('Vertical (productos agrícolas, productores)'),
      'files' => $this->t('Archivos (documentos subidos por el tenant)'),
    ];

    $form['export']['default_sections'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Secciones por defecto'),
      '#description' => $this->t('Secciones preseleccionadas al solicitar una exportación.'),
      '#options' => $sectionOptions,
      '#default_value' => $config->get('default_sections') ?? ['core', 'analytics', 'knowledge', 'operational', 'files'],
    ];

    $form['backup'] = [
      '#type' => 'details',
      '#title' => $this->t('Backups automatizados'),
      '#open' => TRUE,
    ];

    $form['backup']['backup_retention_daily_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Retención de backups diarios (días)'),
      '#description' => $this->t('Días que se mantienen los backups diarios antes de convertirse en semanales.'),
      '#default_value' => $config->get('backup_retention_daily_days') ?? 30,
      '#min' => 7,
      '#max' => 365,
      '#required' => TRUE,
    ];

    $form['backup']['backup_retention_weekly_weeks'] = [
      '#type' => 'number',
      '#title' => $this->t('Retención de backups semanales (semanas)'),
      '#description' => $this->t('Semanas que se mantienen los backups semanales (solo lunes).'),
      '#default_value' => $config->get('backup_retention_weekly_weeks') ?? 12,
      '#min' => 4,
      '#max' => 52,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $defaultSections = array_values(array_filter($form_state->getValue('default_sections')));

    $this->config('jaraba_tenant_export.settings')
      ->set('export_expiration_hours', (int) $form_state->getValue('export_expiration_hours'))
      ->set('rate_limit_per_day', (int) $form_state->getValue('rate_limit_per_day'))
      ->set('max_export_size_mb', (int) $form_state->getValue('max_export_size_mb'))
      ->set('analytics_row_limit', (int) $form_state->getValue('analytics_row_limit'))
      ->set('default_sections', $defaultSections)
      ->set('backup_retention_daily_days', (int) $form_state->getValue('backup_retention_daily_days'))
      ->set('backup_retention_weekly_weeks', (int) $form_state->getValue('backup_retention_weekly_weeks'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
