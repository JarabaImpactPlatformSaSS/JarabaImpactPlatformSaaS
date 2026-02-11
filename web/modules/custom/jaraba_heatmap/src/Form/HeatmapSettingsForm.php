<?php

namespace Drupal\jaraba_heatmap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo Jaraba Heatmap.
 *
 * Permite configurar parámetros del tracker y agregación desde la UI de Drupal
 * siguiendo el Mandato de Configurabilidad Zero-Code (Directriz #20).
 *
 * Las variables CSS se inyectan dinámicamente al tema para permitir
 * personalización sin tocar código.
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */
class HeatmapSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['jaraba_heatmap.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'jaraba_heatmap_settings_form';
    }

    /**
     * {@inheritdoc}
     *
     * Construye el formulario con secciones para: General, Tracker y Apariencia.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('jaraba_heatmap.settings');

        // =======================================================================
        // Sección General
        // =======================================================================
        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar tracking de heatmaps'),
            '#description' => $this->t('Cuando está habilitado, el tracker JavaScript se carga en todas las páginas frontend.'),
            '#default_value' => $config->get('enabled') ?? TRUE,
        ];

        // =======================================================================
        // Sección Tracker
        // =======================================================================
        $form['tracker'] = [
            '#type' => 'details',
            '#title' => $this->t('Parámetros del Tracker'),
            '#open' => FALSE,
        ];

        $form['tracker']['buffer_size'] = [
            '#type' => 'number',
            '#title' => $this->t('Tamaño del buffer'),
            '#description' => $this->t('Número de eventos acumulados antes de enviar al servidor.'),
            '#default_value' => $config->get('buffer_size') ?? 50,
            '#min' => 10,
            '#max' => 200,
        ];

        $form['tracker']['flush_interval'] = [
            '#type' => 'number',
            '#title' => $this->t('Intervalo de flush (ms)'),
            '#description' => $this->t('Intervalo en milisegundos para envío automático de eventos.'),
            '#default_value' => $config->get('flush_interval') ?? 10000,
            '#min' => 5000,
            '#max' => 60000,
        ];

        $form['tracker']['throttle_move'] = [
            '#type' => 'number',
            '#title' => $this->t('Throttle movimiento de mouse (ms)'),
            '#description' => $this->t('Intervalo mínimo entre capturas de movimiento de mouse.'),
            '#default_value' => $config->get('throttle_move') ?? 100,
            '#min' => 50,
            '#max' => 500,
        ];

        $form['tracker']['throttle_scroll'] = [
            '#type' => 'number',
            '#title' => $this->t('Throttle scroll (ms)'),
            '#description' => $this->t('Intervalo mínimo entre capturas de eventos de scroll.'),
            '#default_value' => $config->get('throttle_scroll') ?? 200,
            '#min' => 100,
            '#max' => 1000,
        ];

        // =======================================================================
        // Sección Retención
        // =======================================================================
        $form['retention'] = [
            '#type' => 'details',
            '#title' => $this->t('Retención de Datos'),
            '#open' => FALSE,
        ];

        $form['retention']['retention_raw_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención eventos raw (días)'),
            '#description' => $this->t('Días que se conservan los eventos antes de agregación. Tras este período se purgan.'),
            '#default_value' => $config->get('retention_raw_days') ?? 7,
            '#min' => 3,
            '#max' => 30,
        ];

        $form['retention']['retention_aggregated_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Retención datos agregados (días)'),
            '#description' => $this->t('Días que se conservan los datos agregados para visualización.'),
            '#default_value' => $config->get('retention_aggregated_days') ?? 90,
            '#min' => 30,
            '#max' => 365,
        ];

        // =======================================================================
        // Sección Apariencia (Variables CSS inyectables)
        // =======================================================================
        $form['appearance'] = [
            '#type' => 'details',
            '#title' => $this->t('Apariencia del Dashboard'),
            '#description' => $this->t('Estos valores se inyectan como CSS Variables (--ej-heatmap-*) para personalización del visor sin modificar código.'),
            '#open' => FALSE,
        ];

        $form['appearance']['color_accent'] = [
            '#type' => 'color',
            '#title' => $this->t('Color de acento'),
            '#description' => $this->t('Color principal para botones y elementos interactivos.'),
            '#default_value' => $config->get('color_accent') ?? '#0d6efd',
        ];

        $form['appearance']['color_bg'] = [
            '#type' => 'color',
            '#title' => $this->t('Color de fondo'),
            '#description' => $this->t('Color de fondo del contenedor principal.'),
            '#default_value' => $config->get('color_bg') ?? '#f8f9fa',
        ];

        $form['appearance']['border_radius'] = [
            '#type' => 'number',
            '#title' => $this->t('Radio de bordes (px)'),
            '#description' => $this->t('Radio de redondeo para los elementos del dashboard.'),
            '#default_value' => $config->get('border_radius') ?? 8,
            '#min' => 0,
            '#max' => 24,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('jaraba_heatmap.settings')
            ->set('enabled', $form_state->getValue('enabled'))
            ->set('buffer_size', (int) $form_state->getValue('buffer_size'))
            ->set('flush_interval', (int) $form_state->getValue('flush_interval'))
            ->set('throttle_move', (int) $form_state->getValue('throttle_move'))
            ->set('throttle_scroll', (int) $form_state->getValue('throttle_scroll'))
            ->set('retention_raw_days', (int) $form_state->getValue('retention_raw_days'))
            ->set('retention_aggregated_days', (int) $form_state->getValue('retention_aggregated_days'))
            ->set('color_accent', $form_state->getValue('color_accent'))
            ->set('color_bg', $form_state->getValue('color_bg'))
            ->set('border_radius', (int) $form_state->getValue('border_radius'))
            ->save();

        // Limpiar caché de CSS para aplicar nuevas variables.
        \Drupal::service('asset.css.collection_optimizer')->deleteAll();

        parent::submitForm($form, $form_state);
    }

}
