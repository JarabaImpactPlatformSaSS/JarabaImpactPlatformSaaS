<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de precios FinOps.
 *
 * PROPÓSITO:
 * Permite configurar los precios unitarios para el cálculo
 * de costes en el Dashboard FinOps desde la interfaz.
 *
 * RUTA: /admin/config/finops
 *
 * PRECIOS CONFIGURABLES:
 * - Storage: precio por MB
 * - API Requests: precio por request
 * - CPU Hours: precio por hora de CPU
 * - Budget mensual
 */
class FinOpsSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['ecosistema_jaraba_core.finops'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'ecosistema_jaraba_core_finops_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('ecosistema_jaraba_core.finops');

        $form['pricing'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Unit Pricing'),
            '#description' => $this->t('Configure the unit prices for cost calculations.'),
        ];

        $form['pricing']['price_storage_mb'] = [
            '#type' => 'number',
            '#title' => $this->t('Storage Price (€ per MB)'),
            '#description' => $this->t('Price charged per megabyte of storage.'),
            '#default_value' => $config->get('price_storage_mb') ?: 0.02,
            '#min' => 0,
            '#step' => 0.001,
            '#required' => TRUE,
        ];

        $form['pricing']['price_api_request'] = [
            '#type' => 'number',
            '#title' => $this->t('API Request Price (€ per request)'),
            '#description' => $this->t('Price charged per API request.'),
            '#default_value' => $config->get('price_api_request') ?: 0.001,
            '#min' => 0,
            '#step' => 0.0001,
            '#required' => TRUE,
        ];

        $form['pricing']['price_cpu_hour'] = [
            '#type' => 'number',
            '#title' => $this->t('CPU Hour Price (€ per hour)'),
            '#description' => $this->t('Price charged per CPU hour.'),
            '#default_value' => $config->get('price_cpu_hour') ?: 0.10,
            '#min' => 0,
            '#step' => 0.01,
            '#required' => TRUE,
        ];

        $form['budget'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Budget Settings'),
            '#description' => $this->t('Configure monthly budget limits and alerts.'),
        ];

        $form['budget']['monthly_budget'] = [
            '#type' => 'number',
            '#title' => $this->t('Monthly Budget (€)'),
            '#description' => $this->t('Total monthly budget for cost projections.'),
            '#default_value' => $config->get('monthly_budget') ?: 5000,
            '#min' => 0,
            '#step' => 100,
            '#required' => TRUE,
        ];

        $form['budget']['warning_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Warning Threshold (%)'),
            '#description' => $this->t('Show warning alert when budget usage exceeds this percentage.'),
            '#default_value' => $config->get('warning_threshold') ?: 75,
            '#min' => 0,
            '#max' => 100,
            '#required' => TRUE,
        ];

        $form['budget']['critical_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Critical Threshold (%)'),
            '#description' => $this->t('Show critical alert when budget usage exceeds this percentage.'),
            '#default_value' => $config->get('critical_threshold') ?: 90,
            '#min' => 0,
            '#max' => 100,
            '#required' => TRUE,
        ];

        $form['tier_limits'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Tier Cost Limits'),
            '#description' => $this->t('Configure cost thresholds for tenant status per plan tier.'),
        ];

        $form['tier_limits']['basic_warning'] = [
            '#type' => 'number',
            '#title' => $this->t('Basic Tier - Warning (€)'),
            '#default_value' => $config->get('tier_limits.basic.warning') ?: 50,
            '#min' => 0,
        ];

        $form['tier_limits']['basic_critical'] = [
            '#type' => 'number',
            '#title' => $this->t('Basic Tier - Critical (€)'),
            '#default_value' => $config->get('tier_limits.basic.critical') ?: 100,
            '#min' => 0,
        ];

        $form['tier_limits']['professional_warning'] = [
            '#type' => 'number',
            '#title' => $this->t('Professional Tier - Warning (€)'),
            '#default_value' => $config->get('tier_limits.professional.warning') ?: 200,
            '#min' => 0,
        ];

        $form['tier_limits']['professional_critical'] = [
            '#type' => 'number',
            '#title' => $this->t('Professional Tier - Critical (€)'),
            '#default_value' => $config->get('tier_limits.professional.critical') ?: 500,
            '#min' => 0,
        ];

        $form['tier_limits']['enterprise_warning'] = [
            '#type' => 'number',
            '#title' => $this->t('Enterprise Tier - Warning (€)'),
            '#default_value' => $config->get('tier_limits.enterprise.warning') ?: 1000,
            '#min' => 0,
        ];

        $form['tier_limits']['enterprise_critical'] = [
            '#type' => 'number',
            '#title' => $this->t('Enterprise Tier - Critical (€)'),
            '#default_value' => $config->get('tier_limits.enterprise.critical') ?: 2500,
            '#min' => 0,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('ecosistema_jaraba_core.finops')
            ->set('price_storage_mb', (float) $form_state->getValue('price_storage_mb'))
            ->set('price_api_request', (float) $form_state->getValue('price_api_request'))
            ->set('price_cpu_hour', (float) $form_state->getValue('price_cpu_hour'))
            ->set('monthly_budget', (float) $form_state->getValue('monthly_budget'))
            ->set('warning_threshold', (int) $form_state->getValue('warning_threshold'))
            ->set('critical_threshold', (int) $form_state->getValue('critical_threshold'))
            ->set('tier_limits.basic.warning', (float) $form_state->getValue('basic_warning'))
            ->set('tier_limits.basic.critical', (float) $form_state->getValue('basic_critical'))
            ->set('tier_limits.professional.warning', (float) $form_state->getValue('professional_warning'))
            ->set('tier_limits.professional.critical', (float) $form_state->getValue('professional_critical'))
            ->set('tier_limits.enterprise.warning', (float) $form_state->getValue('enterprise_warning'))
            ->set('tier_limits.enterprise.critical', (float) $form_state->getValue('enterprise_critical'))
            ->save();

        parent::submitForm($form, $form_state);

        $this->messenger()->addStatus($this->t('FinOps settings have been saved.'));
    }

}
