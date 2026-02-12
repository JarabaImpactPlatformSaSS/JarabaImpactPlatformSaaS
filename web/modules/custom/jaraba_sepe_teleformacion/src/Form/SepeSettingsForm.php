<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo SEPE Teleformación.
 */
class SepeSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_sepe_teleformacion.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_sepe_teleformacion_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_sepe_teleformacion.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['centro_activo_id'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Centro SEPE Activo'),
            '#description' => $this->t('Centro de formación a usar en las operaciones SOAP.'),
            '#target_type' => 'sepe_centro',
            '#default_value' => $this->getCentroEntity($config->get('centro_activo_id')),
        ];

        $form['soap'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración Web Service SOAP'),
            '#open' => TRUE,
        ];

        $form['soap']['soap_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar Web Service SOAP'),
            '#default_value' => $config->get('soap_enabled') ?? TRUE,
        ];

        $form['soap']['soap_log_requests'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Registrar todas las peticiones SOAP'),
            '#description' => $this->t('Guarda log de peticiones para auditoría.'),
            '#default_value' => $config->get('soap_log_requests') ?? TRUE,
        ];

        $form['security'] = [
            '#type' => 'details',
            '#title' => $this->t('Seguridad WS-Security'),
            '#open' => FALSE,
        ];

        $form['security']['ws_security_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar WS-Security'),
            '#description' => $this->t('Requiere certificado X.509 válido para peticiones.'),
            '#default_value' => $config->get('ws_security_enabled') ?? FALSE,
        ];

        $form['security']['certificate_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Ruta al Certificado'),
            '#description' => $this->t('Ruta al certificado X.509 del centro.'),
            '#default_value' => $config->get('certificate_path') ?? '',
            '#states' => [
                'visible' => [
                    ':input[name="ws_security_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        $form['seguimiento'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Seguimiento'),
            '#open' => FALSE,
        ];

        $form['seguimiento']['auto_update_interval'] = [
            '#type' => 'select',
            '#title' => $this->t('Intervalo de actualización automática'),
            '#options' => [
                0 => $this->t('Deshabilitado'),
                3600 => $this->t('Cada hora'),
                21600 => $this->t('Cada 6 horas'),
                86400 => $this->t('Diario'),
            ],
            '#default_value' => $config->get('auto_update_interval') ?? 21600,
        ];

        $form['seguimiento']['nota_minima_apto'] = [
            '#type' => 'number',
            '#title' => $this->t('Nota mínima para APTO'),
            '#min' => 0,
            '#max' => 10,
            '#step' => 0.5,
            '#default_value' => $config->get('nota_minima_apto') ?? 5,
        ];

        $form['seguimiento']['progreso_minimo_apto'] = [
            '#type' => 'number',
            '#title' => $this->t('Progreso mínimo para APTO (%)'),
            '#min' => 0,
            '#max' => 100,
            '#step' => 5,
            '#default_value' => $config->get('progreso_minimo_apto') ?? 75,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_sepe_teleformacion.settings')
            ->set('centro_activo_id', $form_state->getValue('centro_activo_id'))
            ->set('soap_enabled', $form_state->getValue('soap_enabled'))
            ->set('soap_log_requests', $form_state->getValue('soap_log_requests'))
            ->set('ws_security_enabled', $form_state->getValue('ws_security_enabled'))
            ->set('certificate_path', $form_state->getValue('certificate_path'))
            ->set('auto_update_interval', $form_state->getValue('auto_update_interval'))
            ->set('nota_minima_apto', $form_state->getValue('nota_minima_apto'))
            ->set('progreso_minimo_apto', $form_state->getValue('progreso_minimo_apto'))
            ->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * Obtiene la entidad centro por ID.
     */
    protected function getCentroEntity(?int $id): ?object
    {
        if (!$id) {
            return NULL;
        }
        return \Drupal::entityTypeManager()
            ->getStorage('sepe_centro')
            ->load($id);
    }

}
