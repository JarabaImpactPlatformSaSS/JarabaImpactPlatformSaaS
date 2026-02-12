<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de Tracking Nativo.
 *
 * Configura el sistema de analytics 100% nativo de la plataforma,
 * sin dependencias de servicios externos (Google Analytics, Clarity, etc.).
 */
class TrackingSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['jaraba_page_builder.tracking'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'jaraba_page_builder_tracking_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('jaraba_page_builder.tracking');

        // Sección: Tracking Global.
        $form['global'] = [
            '#type' => 'details',
            '#title' => $this->t('Tracking Nativo'),
            '#open' => TRUE,
        ];

        $form['global']['enable_tracking'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar tracking de páginas'),
            '#default_value' => $config->get('enable_tracking') ?? TRUE,
            '#description' => $this->t('Activa el seguimiento nativo de visitas y eventos. Los datos se almacenan en tu servidor.'),
        ];

        // Sección: Eventos a Trackear.
        $form['events'] = [
            '#type' => 'details',
            '#title' => $this->t('Eventos de Tracking'),
            '#open' => TRUE,
        ];

        $form['events']['track_page_views'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Registrar visualizaciones de página'),
            '#default_value' => $config->get('track_page_views') ?? TRUE,
        ];

        $form['events']['track_cta_clicks'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Registrar clics en CTAs'),
            '#default_value' => $config->get('track_cta_clicks') ?? TRUE,
            '#description' => $this->t('Trackea clics en botones y enlaces de llamada a la acción.'),
        ];

        $form['events']['track_scroll_depth'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Registrar profundidad de scroll'),
            '#default_value' => $config->get('track_scroll_depth') ?? TRUE,
            '#description' => $this->t('Mide hasta dónde hacen scroll los usuarios en cada página.'),
        ];

        $form['events']['track_time_on_page'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Registrar tiempo en página'),
            '#default_value' => $config->get('track_time_on_page') ?? TRUE,
        ];

        // Sección: Heatmaps Nativos.
        $form['heatmaps'] = [
            '#type' => 'details',
            '#title' => $this->t('Heatmaps Nativos'),
            '#open' => TRUE,
            '#description' => $this->t('Sistema de mapas de calor 100% nativo, sin dependencias externas.'),
        ];

        $form['heatmaps']['enable_heatmaps'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar heatmaps'),
            '#default_value' => $config->get('enable_heatmaps') ?? FALSE,
            '#description' => $this->t('Activa el tracking de clics y movimiento para generar mapas de calor.'),
        ];

        $form['heatmaps']['heatmap_sample_rate'] = [
            '#type' => 'number',
            '#title' => $this->t('Tasa de muestreo (%)'),
            '#default_value' => $config->get('heatmap_sample_rate') ?? 100,
            '#min' => 1,
            '#max' => 100,
            '#description' => $this->t('Porcentaje de sesiones a trackear. Reduce para sitios de alto tráfico.'),
        ];

        $form['heatmaps']['heatmap_retention_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención de datos (días)'),
            '#default_value' => $config->get('heatmap_retention_days') ?? 30,
            '#min' => 7,
            '#max' => 90,
            '#description' => $this->t('Los eventos raw se eliminan automáticamente después de este período.'),
        ];

        // Sección: Privacidad.
        $form['privacy'] = [
            '#type' => 'details',
            '#title' => $this->t('Privacidad y GDPR'),
            '#open' => FALSE,
        ];

        $form['privacy']['respect_dnt'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Respetar Do Not Track'),
            '#default_value' => $config->get('respect_dnt') ?? TRUE,
            '#description' => $this->t('No trackear usuarios que tengan habilitado DNT en su navegador.'),
        ];

        $form['privacy']['anonymize_ip'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Anonimizar direcciones IP'),
            '#default_value' => $config->get('anonymize_ip') ?? TRUE,
            '#description' => $this->t('Las IPs se truncan antes de almacenar para cumplir GDPR.'),
        ];

        $form['privacy']['cookie_consent_required'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Requerir consentimiento de cookies'),
            '#default_value' => $config->get('cookie_consent_required') ?? FALSE,
            '#description' => $this->t('Sólo trackear si el usuario ha aceptado cookies de analytics.'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('jaraba_page_builder.tracking')
            // Global.
            ->set('enable_tracking', $form_state->getValue('enable_tracking'))
            // Events.
            ->set('track_page_views', $form_state->getValue('track_page_views'))
            ->set('track_cta_clicks', $form_state->getValue('track_cta_clicks'))
            ->set('track_scroll_depth', $form_state->getValue('track_scroll_depth'))
            ->set('track_time_on_page', $form_state->getValue('track_time_on_page'))
            // Heatmaps.
            ->set('enable_heatmaps', $form_state->getValue('enable_heatmaps'))
            ->set('heatmap_sample_rate', $form_state->getValue('heatmap_sample_rate'))
            ->set('heatmap_retention_days', $form_state->getValue('heatmap_retention_days'))
            // Privacy.
            ->set('respect_dnt', $form_state->getValue('respect_dnt'))
            ->set('anonymize_ip', $form_state->getValue('anonymize_ip'))
            ->set('cookie_consent_required', $form_state->getValue('cookie_consent_required'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
