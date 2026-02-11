<?php

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de Jaraba Analytics.
 */
class AnalyticsSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['jaraba_analytics.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'jaraba_analytics_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('jaraba_analytics.settings');

        $form['tracking'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Tracking'),
            '#open' => TRUE,
        ];

        $form['tracking']['enable_tracking'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar tracking de eventos'),
            '#default_value' => $config->get('enable_tracking') ?? TRUE,
            '#description' => $this->t('Activa el tracking nativo de eventos.'),
        ];

        $form['tracking']['track_page_views'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Trackear page views automáticamente'),
            '#default_value' => $config->get('track_page_views') ?? TRUE,
        ];

        $form['tracking']['track_ecommerce'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Trackear eventos e-commerce'),
            '#default_value' => $config->get('track_ecommerce') ?? TRUE,
            '#description' => $this->t('add_to_cart, purchase, etc.'),
        ];

        $form['retention'] = [
            '#type' => 'details',
            '#title' => $this->t('Retención de Datos'),
            '#open' => TRUE,
        ];

        $form['retention']['event_retention_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención de eventos raw (días)'),
            '#default_value' => $config->get('event_retention_days') ?? 90,
            '#min' => 7,
            '#max' => 365,
            '#description' => $this->t('Los eventos individuales se eliminan después de este período.'),
        ];

        $form['retention']['daily_retention_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención de métricas diarias (días)'),
            '#default_value' => $config->get('daily_retention_days') ?? 730,
            '#min' => 30,
            '#max' => 2555,
            '#description' => $this->t('Las métricas agregadas se mantienen más tiempo.'),
        ];

        $form['privacy'] = [
            '#type' => 'details',
            '#title' => $this->t('Privacidad (GDPR)'),
            '#open' => FALSE,
        ];

        $form['privacy']['anonymize_ip'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Anonimizar IPs'),
            '#default_value' => $config->get('anonymize_ip') ?? TRUE,
            '#description' => $this->t('Las IPs se hashean antes de almacenar.'),
        ];

        $form['privacy']['respect_dnt'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Respetar Do Not Track'),
            '#default_value' => $config->get('respect_dnt') ?? TRUE,
        ];

        $form['privacy']['cookie_consent_required'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Requerir consentimiento de cookies'),
            '#default_value' => $config->get('cookie_consent_required') ?? FALSE,
        ];

        $form['cache'] = [
            '#type' => 'details',
            '#title' => $this->t('Cache'),
            '#open' => FALSE,
        ];

        $form['cache']['cache_ttl'] = [
            '#type' => 'number',
            '#title' => $this->t('TTL de cache para métricas (segundos)'),
            '#default_value' => $config->get('cache_ttl') ?? 300,
            '#min' => 60,
            '#max' => 3600,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('jaraba_analytics.settings')
            ->set('enable_tracking', $form_state->getValue('enable_tracking'))
            ->set('track_page_views', $form_state->getValue('track_page_views'))
            ->set('track_ecommerce', $form_state->getValue('track_ecommerce'))
            ->set('event_retention_days', $form_state->getValue('event_retention_days'))
            ->set('daily_retention_days', $form_state->getValue('daily_retention_days'))
            ->set('anonymize_ip', $form_state->getValue('anonymize_ip'))
            ->set('respect_dnt', $form_state->getValue('respect_dnt'))
            ->set('cookie_consent_required', $form_state->getValue('cookie_consent_required'))
            ->set('cache_ttl', $form_state->getValue('cache_ttl'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
