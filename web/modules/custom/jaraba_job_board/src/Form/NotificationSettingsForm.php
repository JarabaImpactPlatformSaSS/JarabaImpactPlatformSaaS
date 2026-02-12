<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de notificaciones del Job Board.
 */
class NotificationSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_job_board.push_settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_job_board_notification_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_job_board.push_settings');

        $form['push'] = [
            '#type' => 'details',
            '#title' => $this->t('Web Push Notifications (VAPID)'),
            '#open' => TRUE,
        ];

        $form['push']['vapid_public_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('VAPID Public Key'),
            '#default_value' => $config->get('vapid_public_key') ?: '',
            '#description' => $this->t('Clave pública VAPID para Web Push. Generar con: npx web-push generate-vapid-keys'),
            '#maxlength' => 255,
        ];

        $form['push']['vapid_private_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('VAPID Private Key'),
            '#default_value' => $config->get('vapid_private_key') ?: '',
            '#description' => $this->t('Clave privada VAPID. Mantener segura.'),
            '#maxlength' => 255,
        ];

        $form['push']['vapid_subject'] = [
            '#type' => 'email',
            '#title' => $this->t('VAPID Subject (Email)'),
            '#default_value' => $config->get('vapid_subject') ?: 'mailto:admin@jaraba.es',
            '#description' => $this->t('Email de contacto para el servidor push (formato mailto:).'),
        ];

        $form['activecampaign'] = [
            '#type' => 'details',
            '#title' => $this->t('ActiveCampaign Integration'),
            '#open' => TRUE,
        ];

        $form['activecampaign']['webhook_url'] = [
            '#type' => 'url',
            '#title' => $this->t('Webhook URL'),
            '#default_value' => $config->get('activecampaign_webhook_url') ?: '',
            '#description' => $this->t('URL del webhook de ActiveCampaign para eventos de candidaturas.'),
        ];

        $form['activecampaign']['api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Key'),
            '#default_value' => $config->get('activecampaign_api_key') ?: '',
            '#description' => $this->t('API Key de ActiveCampaign para tracking de contactos.'),
        ];

        $form['activecampaign']['account_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Account ID'),
            '#default_value' => $config->get('activecampaign_account_id') ?: '',
            '#description' => $this->t('ID de cuenta de ActiveCampaign.'),
        ];

        $form['email'] = [
            '#type' => 'details',
            '#title' => $this->t('Email Settings'),
            '#open' => TRUE,
        ];

        $form['email']['from_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('From Name'),
            '#default_value' => $config->get('email_from_name') ?: 'Jaraba Empleabilidad',
            '#description' => $this->t('Nombre del remitente de emails.'),
        ];

        $form['email']['from_email'] = [
            '#type' => 'email',
            '#title' => $this->t('From Email'),
            '#default_value' => $config->get('email_from_email') ?: 'empleo@jaraba.es',
            '#description' => $this->t('Email del remitente.'),
        ];

        $form['email']['use_html'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Usar emails HTML'),
            '#default_value' => $config->get('email_use_html') ?? TRUE,
            '#description' => $this->t('Enviar emails en formato HTML con estilos.'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_job_board.push_settings')
            ->set('vapid_public_key', $form_state->getValue('vapid_public_key'))
            ->set('vapid_private_key', $form_state->getValue('vapid_private_key'))
            ->set('vapid_subject', $form_state->getValue('vapid_subject'))
            ->set('activecampaign_webhook_url', $form_state->getValue('webhook_url'))
            ->set('activecampaign_api_key', $form_state->getValue('api_key'))
            ->set('activecampaign_account_id', $form_state->getValue('account_id'))
            ->set('email_from_name', $form_state->getValue('from_name'))
            ->set('email_from_email', $form_state->getValue('from_email'))
            ->set('email_use_html', $form_state->getValue('use_html'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
