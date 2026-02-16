<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración global del módulo de privacidad.
 *
 * Permite configurar: plazos ARCO-POL, datos del DPO, banner de cookies
 * y parámetros de notificación de brechas.
 *
 * Ruta: /admin/config/jaraba/privacy
 */
class PrivacySettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_privacy.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_privacy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // =================================================================
    // DPO — Datos del Delegado de Protección de Datos
    // =================================================================
    $form['dpo'] = [
      '#type' => 'details',
      '#title' => $this->t('Delegado de Protección de Datos (DPO)'),
      '#open' => TRUE,
    ];

    $form['dpo']['dpo_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre del DPO'),
      '#default_value' => $config->get('dpo_name') ?? '',
      '#maxlength' => 255,
    ];

    $form['dpo']['dpo_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email del DPO'),
      '#description' => $this->t('Email de contacto del Delegado de Protección de Datos.'),
      '#default_value' => $config->get('dpo_email') ?? '',
    ];

    // =================================================================
    // ARCO-POL — Plazos de derechos del interesado
    // =================================================================
    $form['arco_pol'] = [
      '#type' => 'details',
      '#title' => $this->t('Derechos ARCO-POL'),
      '#open' => TRUE,
    ];

    $form['arco_pol']['arco_pol_deadline_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Plazo máximo de respuesta (días)'),
      '#description' => $this->t('Plazo máximo en días naturales para responder solicitudes ARCO-POL (RGPD: 30 días).'),
      '#default_value' => $config->get('arco_pol_deadline_days') ?? 30,
      '#min' => 1,
      '#max' => 90,
      '#required' => TRUE,
    ];

    // =================================================================
    // BRECHAS — Notificación AEPD
    // =================================================================
    $form['breach'] = [
      '#type' => 'details',
      '#title' => $this->t('Notificación de brechas'),
      '#open' => TRUE,
    ];

    $form['breach']['breach_notification_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Plazo notificación AEPD (horas)'),
      '#description' => $this->t('Plazo máximo en horas para notificar a la AEPD (RGPD Art. 33: 72 horas).'),
      '#default_value' => $config->get('breach_notification_hours') ?? 72,
      '#min' => 1,
      '#max' => 72,
      '#required' => TRUE,
    ];

    // =================================================================
    // COOKIES — Banner de consentimiento
    // =================================================================
    $form['cookies'] = [
      '#type' => 'details',
      '#title' => $this->t('Banner de cookies'),
      '#open' => TRUE,
    ];

    $form['cookies']['enable_cookie_banner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar banner de cookies'),
      '#description' => $this->t('Activa el banner de consentimiento de cookies en todas las páginas frontend.'),
      '#default_value' => $config->get('enable_cookie_banner') ?? TRUE,
    ];

    $form['cookies']['cookie_banner_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Posición del banner'),
      '#options' => [
        'bottom-bar' => $this->t('Barra inferior'),
        'top-bar' => $this->t('Barra superior'),
        'center-modal' => $this->t('Modal central'),
        'bottom-left' => $this->t('Esquina inferior izquierda'),
        'bottom-right' => $this->t('Esquina inferior derecha'),
      ],
      '#default_value' => $config->get('cookie_banner_position') ?? 'bottom-bar',
    ];

    $form['cookies']['cookie_expiry_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Duración del consentimiento (días)'),
      '#description' => $this->t('Días que se mantiene válido el consentimiento antes de volver a solicitarlo.'),
      '#default_value' => $config->get('cookie_expiry_days') ?? 365,
      '#min' => 30,
      '#max' => 730,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    $config->set('dpo_name', $form_state->getValue('dpo_name'));
    $config->set('dpo_email', $form_state->getValue('dpo_email'));
    $config->set('arco_pol_deadline_days', (int) $form_state->getValue('arco_pol_deadline_days'));
    $config->set('breach_notification_hours', (int) $form_state->getValue('breach_notification_hours'));
    $config->set('enable_cookie_banner', (bool) $form_state->getValue('enable_cookie_banner'));
    $config->set('cookie_banner_position', $form_state->getValue('cookie_banner_position'));
    $config->set('cookie_expiry_days', (int) $form_state->getValue('cookie_expiry_days'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
