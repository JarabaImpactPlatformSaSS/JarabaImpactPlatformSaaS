<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuración general del módulo AgroConecta.
 */
class AgroconectaSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_agroconecta_core.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_agroconecta_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_agroconecta_core.settings');

        $form['marketplace'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración del Marketplace'),
            '#open' => TRUE,
        ];

        $form['marketplace']['marketplace_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre del marketplace'),
            '#description' => $this->t('Nombre que aparece en la cabecera del marketplace.'),
            '#default_value' => $config->get('marketplace_name') ?? 'AgroConecta',
            '#required' => TRUE,
        ];

        $form['marketplace']['marketplace_description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción del marketplace'),
            '#description' => $this->t('Texto descriptivo para la página principal del marketplace.'),
            '#default_value' => $config->get('marketplace_description') ?? '',
        ];

        $form['marketplace']['products_per_page'] = [
            '#type' => 'number',
            '#title' => $this->t('Productos por página'),
            '#description' => $this->t('Número de productos a mostrar por página en el marketplace.'),
            '#default_value' => $config->get('products_per_page') ?? 12,
            '#min' => 4,
            '#max' => 48,
        ];

        $form['commerce'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración Commerce'),
            '#open' => FALSE,
        ];

        $form['commerce']['currency_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Moneda por defecto'),
            '#description' => $this->t('Código ISO 4217 de la moneda (ej: EUR, USD).'),
            '#default_value' => $config->get('currency_code') ?? 'EUR',
            '#maxlength' => 3,
        ];

        $form['commerce']['commission_percentage'] = [
            '#type' => 'number',
            '#title' => $this->t('Comisión de plataforma (%)'),
            '#description' => $this->t('Porcentaje de comisión que retiene la plataforma en cada venta.'),
            '#default_value' => $config->get('commission_percentage') ?? 10,
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
        $this->config('jaraba_agroconecta_core.settings')
            ->set('marketplace_name', $form_state->getValue('marketplace_name'))
            ->set('marketplace_description', $form_state->getValue('marketplace_description'))
            ->set('products_per_page', $form_state->getValue('products_per_page'))
            ->set('currency_code', $form_state->getValue('currency_code'))
            ->set('commission_percentage', $form_state->getValue('commission_percentage'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
