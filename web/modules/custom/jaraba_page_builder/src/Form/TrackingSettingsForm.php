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

        // Seccion: Google Analytics 4 (P2-04).
        $form['ga4'] = [
            '#type' => 'details',
            '#title' => $this->t('Google Analytics 4'),
            '#open' => !empty($config->get('ga4_enabled')),
            '#description' => $this->t('Conecta con GA4 para enviar eventos server-side via Measurement Protocol.'),
        ];

        $form['ga4']['ga4_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar Google Analytics 4'),
            '#default_value' => $config->get('ga4_enabled') ?? FALSE,
            '#description' => $this->t('Envia eventos del Page Builder a tu propiedad GA4 en tiempo real.'),
        ];

        $form['ga4']['ga4_measurement_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Measurement ID'),
            '#default_value' => $config->get('ga4_measurement_id') ?? '',
            '#placeholder' => 'G-XXXXXXXXXX',
            '#description' => $this->t('ID de medicion de tu propiedad GA4 (formato G-XXXXXXXXXX).'),
            '#states' => [
                'visible' => [':input[name="ga4_enabled"]' => ['checked' => TRUE]],
            ],
        ];

        $form['ga4']['ga4_api_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Secret'),
            '#default_value' => $config->get('ga4_api_secret') ?? '',
            '#description' => $this->t('Secret de la API de Measurement Protocol. Obtenlo en Admin > Data Streams > Measurement Protocol API secrets.'),
            '#states' => [
                'visible' => [':input[name="ga4_enabled"]' => ['checked' => TRUE]],
            ],
        ];

        $form['ga4']['ga4_property_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Property ID (para reportes)'),
            '#default_value' => $config->get('ga4_property_id') ?? '',
            '#placeholder' => '123456789',
            '#description' => $this->t('ID numerico de la propiedad GA4 para consultar reportes via Data API.'),
            '#states' => [
                'visible' => [':input[name="ga4_enabled"]' => ['checked' => TRUE]],
            ],
        ];

        // Seccion: Google Search Console (P2-04).
        $form['search_console'] = [
            '#type' => 'details',
            '#title' => $this->t('Google Search Console'),
            '#open' => !empty($config->get('search_console_enabled')),
            '#description' => $this->t('Conecta con Search Console para ver datos de busqueda organica en el dashboard.'),
        ];

        $form['search_console']['search_console_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar Google Search Console'),
            '#default_value' => $config->get('search_console_enabled') ?? FALSE,
        ];

        $form['search_console']['search_console_site_url'] = [
            '#type' => 'url',
            '#title' => $this->t('URL del sitio'),
            '#default_value' => $config->get('search_console_site_url') ?? '',
            '#placeholder' => 'https://mi-sitio.com',
            '#description' => $this->t('La URL del sitio verificada en Search Console (sc-domain: o URL prefix).'),
            '#states' => [
                'visible' => [':input[name="search_console_enabled"]' => ['checked' => TRUE]],
            ],
        ];

        $form['search_console']['search_console_oauth_credentials'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Credenciales OAuth2 (JSON)'),
            '#default_value' => $config->get('search_console_oauth_credentials') ?? '',
            '#rows' => 4,
            '#description' => $this->t('Credenciales OAuth2 en formato JSON con access_token, refresh_token, client_id, client_secret.'),
            '#states' => [
                'visible' => [':input[name="search_console_enabled"]' => ['checked' => TRUE]],
            ],
        ];

        // Seccion: Microsoft Clarity (P2-04).
        $form['clarity'] = [
            '#type' => 'details',
            '#title' => $this->t('Microsoft Clarity'),
            '#open' => !empty($config->get('clarity_enabled')),
            '#description' => $this->t('Heatmaps y grabaciones de sesion con Microsoft Clarity.'),
        ];

        $form['clarity']['clarity_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar Microsoft Clarity'),
            '#default_value' => $config->get('clarity_enabled') ?? FALSE,
        ];

        $form['clarity']['clarity_project_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Project ID'),
            '#default_value' => $config->get('clarity_project_id') ?? '',
            '#description' => $this->t('ID del proyecto Clarity. Se inyectara automaticamente el script de tracking.'),
            '#states' => [
                'visible' => [':input[name="clarity_enabled"]' => ['checked' => TRUE]],
            ],
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
            // GA4 (P2-04).
            ->set('ga4_enabled', $form_state->getValue('ga4_enabled'))
            ->set('ga4_measurement_id', $form_state->getValue('ga4_measurement_id'))
            ->set('ga4_api_secret', $form_state->getValue('ga4_api_secret'))
            ->set('ga4_property_id', $form_state->getValue('ga4_property_id'))
            // Search Console (P2-04).
            ->set('search_console_enabled', $form_state->getValue('search_console_enabled'))
            ->set('search_console_site_url', $form_state->getValue('search_console_site_url'))
            ->set('search_console_oauth_credentials', $form_state->getValue('search_console_oauth_credentials'))
            // Clarity (P2-04).
            ->set('clarity_enabled', $form_state->getValue('clarity_enabled'))
            ->set('clarity_project_id', $form_state->getValue('clarity_project_id'))
            // Privacy.
            ->set('respect_dnt', $form_state->getValue('respect_dnt'))
            ->set('anonymize_ip', $form_state->getValue('anonymize_ip'))
            ->set('cookie_consent_required', $form_state->getValue('cookie_consent_required'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
