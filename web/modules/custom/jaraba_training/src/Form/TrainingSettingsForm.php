<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración general del módulo Training.
 */
class TrainingSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_training_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_training.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_training.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['upsell_delay_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Días para email de upsell'),
            '#description' => $this->t('Días después de compra para enviar email de upsell.'),
            '#default_value' => $config->get('upsell_delay_days') ?? 7,
            '#min' => 1,
            '#max' => 30,
        ];

        $form['general']['certification_expiry_months'] = [
            '#type' => 'number',
            '#title' => $this->t('Meses de vigencia de certificación'),
            '#description' => $this->t('Meses de validez de una certificación otorgada.'),
            '#default_value' => $config->get('certification_expiry_months') ?? 12,
            '#min' => 6,
            '#max' => 60,
        ];

        $form['royalties'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Royalties'),
            '#open' => FALSE,
        ];

        $form['royalties']['default_royalty_percent'] = [
            '#type' => 'number',
            '#title' => $this->t('Royalty por defecto (%)'),
            '#description' => $this->t('Porcentaje de royalty por defecto para certificados.'),
            '#default_value' => $config->get('default_royalty_percent') ?? 10,
            '#min' => 0,
            '#max' => 50,
            '#step' => 0.5,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_training.settings')
            ->set('upsell_delay_days', $form_state->getValue('upsell_delay_days'))
            ->set('certification_expiry_months', $form_state->getValue('certification_expiry_months'))
            ->set('default_royalty_percent', $form_state->getValue('default_royalty_percent'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
