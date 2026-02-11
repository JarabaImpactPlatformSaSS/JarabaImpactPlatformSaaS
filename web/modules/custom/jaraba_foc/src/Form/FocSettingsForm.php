<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo FOC.
 *
 * PROPÓSITO:
 * Centraliza la configuración del Centro de Operaciones Financieras:
 * - Credenciales de Stripe Connect
 * - Umbrales de alertas
 * - Configuración de snapshots automáticos
 * - Parámetros de proyecciones
 */
class FocSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_foc.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_foc_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_foc.settings');

        // ═══════════════════════════════════════════════════════════════════════
        // CONFIGURACIÓN DE STRIPE CONNECT
        // ═══════════════════════════════════════════════════════════════════════
        $form['stripe'] = [
            '#type' => 'details',
            '#title' => $this->t('Stripe Connect'),
            '#open' => TRUE,
        ];

        $form['stripe']['stripe_secret_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Secret Key'),
            '#description' => $this->t('Clave secreta de Stripe (sk_live_xxx o sk_test_xxx).'),
            '#default_value' => $config->get('stripe_secret_key'),
            '#maxlength' => 255,
        ];

        $form['stripe']['stripe_webhook_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Webhook Secret'),
            '#description' => $this->t('Secreto del webhook para verificar firmas (whsec_xxx).'),
            '#default_value' => $config->get('stripe_webhook_secret'),
            '#maxlength' => 255,
        ];

        $form['stripe']['stripe_platform_fee'] = [
            '#type' => 'number',
            '#title' => $this->t('Comisión de Plataforma (%)'),
            '#description' => $this->t('Porcentaje de application_fee en Destination Charges. Recomendado: 5%.'),
            '#default_value' => $config->get('stripe_platform_fee') ?? 5,
            '#min' => 0,
            '#max' => 30,
            '#step' => 0.5,
        ];

        // ═══════════════════════════════════════════════════════════════════════
        // UMBRALES DE ALERTAS
        // ═══════════════════════════════════════════════════════════════════════
        $form['alerts'] = [
            '#type' => 'details',
            '#title' => $this->t('Umbrales de Alertas'),
            '#open' => TRUE,
        ];

        $form['alerts']['alert_churn_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Umbral de Churn (%)'),
            '#description' => $this->t('Alertar cuando el churn mensual supere este porcentaje.'),
            '#default_value' => $config->get('alert_churn_threshold') ?? 5,
            '#min' => 0,
            '#max' => 50,
            '#step' => 0.5,
        ];

        $form['alerts']['alert_ltv_cac_min'] = [
            '#type' => 'number',
            '#title' => $this->t('Ratio LTV:CAC Mínimo'),
            '#description' => $this->t('Alertar cuando el ratio en un tenant esté por debajo de este valor.'),
            '#default_value' => $config->get('alert_ltv_cac_min') ?? 3,
            '#min' => 1,
            '#max' => 10,
            '#step' => 0.5,
        ];

        $form['alerts']['alert_mrr_drop_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Caída MRR (%)'),
            '#description' => $this->t('Alertar cuando el MRR caiga más de este porcentaje mes a mes.'),
            '#default_value' => $config->get('alert_mrr_drop_threshold') ?? 10,
            '#min' => 0,
            '#max' => 50,
            '#step' => 1,
        ];

        // ═══════════════════════════════════════════════════════════════════════
        // CONFIGURACIÓN DE SNAPSHOTS
        // ═══════════════════════════════════════════════════════════════════════
        $form['snapshots'] = [
            '#type' => 'details',
            '#title' => $this->t('Snapshots Automáticos'),
            '#open' => FALSE,
        ];

        $form['snapshots']['snapshot_frequency'] = [
            '#type' => 'select',
            '#title' => $this->t('Frecuencia'),
            '#description' => $this->t('Frecuencia de generación automática de snapshots.'),
            '#options' => [
                'daily' => $this->t('Diario'),
                'weekly' => $this->t('Semanal'),
                'monthly' => $this->t('Mensual'),
            ],
            '#default_value' => $config->get('snapshot_frequency') ?? 'daily',
        ];

        $form['snapshots']['snapshot_retention_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención (días)'),
            '#description' => $this->t('Número de días a mantener los snapshots. 0 = indefinido.'),
            '#default_value' => $config->get('snapshot_retention_days') ?? 365,
            '#min' => 0,
            '#max' => 3650,
        ];

        // ═══════════════════════════════════════════════════════════════════════
        // CONFIGURACIÓN DE PROYECCIONES (AI)
        // ═══════════════════════════════════════════════════════════════════════
        $form['projections'] = [
            '#type' => 'details',
            '#title' => $this->t('Motor de Proyecciones'),
            '#open' => FALSE,
        ];

        $form['projections']['projection_provider'] = [
            '#type' => 'select',
            '#title' => $this->t('Proveedor de IA'),
            '#description' => $this->t('API externa para cálculo de proyecciones.'),
            '#options' => [
                'openai' => 'OpenAI (GPT-4)',
                'anthropic' => 'Anthropic (Claude)',
            ],
            '#default_value' => $config->get('projection_provider') ?? 'anthropic',
        ];

        $form['projections']['projection_horizon_months'] = [
            '#type' => 'number',
            '#title' => $this->t('Horizonte de Proyección (meses)'),
            '#description' => $this->t('Número de meses a proyectar por defecto.'),
            '#default_value' => $config->get('projection_horizon_months') ?? 12,
            '#min' => 3,
            '#max' => 36,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_foc.settings')
            // Stripe.
            ->set('stripe_secret_key', $form_state->getValue('stripe_secret_key'))
            ->set('stripe_webhook_secret', $form_state->getValue('stripe_webhook_secret'))
            ->set('stripe_platform_fee', $form_state->getValue('stripe_platform_fee'))
            // Alerts.
            ->set('alert_churn_threshold', $form_state->getValue('alert_churn_threshold'))
            ->set('alert_ltv_cac_min', $form_state->getValue('alert_ltv_cac_min'))
            ->set('alert_mrr_drop_threshold', $form_state->getValue('alert_mrr_drop_threshold'))
            // Snapshots.
            ->set('snapshot_frequency', $form_state->getValue('snapshot_frequency'))
            ->set('snapshot_retention_days', $form_state->getValue('snapshot_retention_days'))
            // Projections.
            ->set('projection_provider', $form_state->getValue('projection_provider'))
            ->set('projection_horizon_months', $form_state->getValue('projection_horizon_months'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
