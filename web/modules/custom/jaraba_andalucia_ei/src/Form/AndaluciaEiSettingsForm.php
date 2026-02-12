<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración general del módulo Andalucía +ei.
 */
class AndaluciaEiSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_andalucia_ei.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_andalucia_ei_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_andalucia_ei.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['horas_minimas_orientacion'] = [
            '#type' => 'number',
            '#title' => $this->t('Horas Mínimas Orientación para Inserción'),
            '#description' => $this->t('Horas mínimas de orientación requeridas para transitar a fase Inserción.'),
            '#default_value' => $config->get('horas_minimas_orientacion') ?? 10,
            '#min' => 0,
            '#step' => 0.5,
        ];

        $form['general']['horas_minimas_formacion'] = [
            '#type' => 'number',
            '#title' => $this->t('Horas Mínimas Formación para Inserción'),
            '#description' => $this->t('Horas mínimas de formación requeridas para transitar a fase Inserción.'),
            '#default_value' => $config->get('horas_minimas_formacion') ?? 50,
            '#min' => 0,
            '#step' => 0.5,
        ];

        $form['ia_tracking'] = [
            '#type' => 'details',
            '#title' => $this->t('Tracking de Mentoría IA'),
            '#open' => TRUE,
        ];

        $form['ia_tracking']['horas_por_sesion_ia'] = [
            '#type' => 'number',
            '#title' => $this->t('Horas por Sesión con Tutor IA'),
            '#description' => $this->t('Horas a contabilizar por cada sesión con el Copiloto IA.'),
            '#default_value' => $config->get('horas_por_sesion_ia') ?? 0.25,
            '#min' => 0.1,
            '#max' => 2,
            '#step' => 0.05,
        ];

        $form['ia_tracking']['maximo_horas_ia_dia'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo Horas IA por Día'),
            '#description' => $this->t('Límite de horas IA contabilizables por día.'),
            '#default_value' => $config->get('maximo_horas_ia_dia') ?? 4,
            '#min' => 1,
            '#max' => 8,
            '#step' => 0.5,
        ];

        $form['sto'] = [
            '#type' => 'details',
            '#title' => $this->t('Integración STO'),
            '#open' => FALSE,
        ];

        $form['sto']['sto_sync_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar sincronización automática con STO'),
            '#default_value' => $config->get('sto_sync_enabled') ?? FALSE,
        ];

        $form['sto']['sto_endpoint'] = [
            '#type' => 'url',
            '#title' => $this->t('Endpoint STO'),
            '#description' => $this->t('URL del servicio web del STO.'),
            '#default_value' => $config->get('sto_endpoint') ?? '',
            '#states' => [
                'visible' => [
                    ':input[name="sto_sync_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_andalucia_ei.settings')
            ->set('horas_minimas_orientacion', $form_state->getValue('horas_minimas_orientacion'))
            ->set('horas_minimas_formacion', $form_state->getValue('horas_minimas_formacion'))
            ->set('horas_por_sesion_ia', $form_state->getValue('horas_por_sesion_ia'))
            ->set('maximo_horas_ia_dia', $form_state->getValue('maximo_horas_ia_dia'))
            ->set('sto_sync_enabled', $form_state->getValue('sto_sync_enabled'))
            ->set('sto_endpoint', $form_state->getValue('sto_endpoint'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
