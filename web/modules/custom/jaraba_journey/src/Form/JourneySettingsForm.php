<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del Journey Engine.
 */
class JourneySettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['jaraba_journey.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'jaraba_journey_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('jaraba_journey.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['enable_ai_triggers'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar triggers IA'),
            '#description' => $this->t('Permite que el motor active intervenciones IA automáticas.'),
            '#default_value' => $config->get('enable_ai_triggers') ?? TRUE,
        ];

        $form['general']['risk_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Umbral de riesgo'),
            '#description' => $this->t('Score mínimo para marcar usuario como en riesgo (0-100).'),
            '#min' => 0,
            '#max' => 100,
            '#default_value' => $config->get('risk_threshold') ?? 70,
        ];

        $form['notifications'] = [
            '#type' => 'details',
            '#title' => $this->t('Reglas de No Intrusión'),
            '#open' => TRUE,
        ];

        $form['notifications']['max_daily_notifications'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo notificaciones diarias'),
            '#min' => 1,
            '#max' => 20,
            '#default_value' => $config->get('max_daily_notifications') ?? 3,
        ];

        $form['notifications']['quiet_hours_start'] = [
            '#type' => 'number',
            '#title' => $this->t('Inicio horas silencio'),
            '#description' => $this->t('Hora de inicio (0-23) para no enviar notificaciones.'),
            '#min' => 0,
            '#max' => 23,
            '#default_value' => $config->get('quiet_hours_start') ?? 22,
        ];

        $form['notifications']['quiet_hours_end'] = [
            '#type' => 'number',
            '#title' => $this->t('Fin horas silencio'),
            '#min' => 0,
            '#max' => 23,
            '#default_value' => $config->get('quiet_hours_end') ?? 8,
        ];

        $form['notifications']['min_interval_minutes'] = [
            '#type' => 'number',
            '#title' => $this->t('Intervalo mínimo entre notificaciones (minutos)'),
            '#min' => 5,
            '#max' => 480,
            '#default_value' => $config->get('min_interval_minutes') ?? 30,
        ];

        $form['analytics'] = [
            '#type' => 'details',
            '#title' => $this->t('Analytics'),
            '#open' => FALSE,
        ];

        $form['analytics']['enable_cohort_tracking'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar tracking por cohorte'),
            '#default_value' => $config->get('enable_cohort_tracking') ?? TRUE,
        ];

        $form['analytics']['retention_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Días de retención de eventos'),
            '#description' => $this->t('Número de días que se conservan los eventos de journey.'),
            '#min' => 30,
            '#max' => 365,
            '#default_value' => $config->get('retention_days') ?? 90,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('jaraba_journey.settings')
            ->set('enable_ai_triggers', $form_state->getValue('enable_ai_triggers'))
            ->set('risk_threshold', $form_state->getValue('risk_threshold'))
            ->set('max_daily_notifications', $form_state->getValue('max_daily_notifications'))
            ->set('quiet_hours_start', $form_state->getValue('quiet_hours_start'))
            ->set('quiet_hours_end', $form_state->getValue('quiet_hours_end'))
            ->set('min_interval_minutes', $form_state->getValue('min_interval_minutes'))
            ->set('enable_cohort_tracking', $form_state->getValue('enable_cohort_tracking'))
            ->set('retention_days', $form_state->getValue('retention_days'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
