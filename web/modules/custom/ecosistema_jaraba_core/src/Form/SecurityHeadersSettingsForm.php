<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SEC-08: Formulario de configuración de headers de seguridad.
 *
 * ESTRUCTURA:
 * Permite configurar CORS, CSP y HSTS desde la interfaz de administración
 * sin necesidad de tocar código ni archivos de configuración.
 *
 * LÓGICA:
 * - Los valores se almacenan en config 'ecosistema_jaraba_core.security_headers'
 * - El SecurityHeadersSubscriber lee estos valores en cada request
 * - Defaults seguros si no se ha configurado nada
 *
 * RELACIONES:
 * - SecurityHeadersSettingsForm -> config('ecosistema_jaraba_core.security_headers')
 * - SecurityHeadersSubscriber <- lee la config
 *
 * @see docs/tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md (SEC-08)
 */
class SecurityHeadersSettingsForm extends ConfigFormBase
{

    const CONFIG_NAME = 'ecosistema_jaraba_core.security_headers';

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return [self::CONFIG_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'ecosistema_jaraba_core_security_headers_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config(self::CONFIG_NAME);

        // ═══════════════════════════════════════════════════
        // CORS
        // ═══════════════════════════════════════════════════
        $form['cors'] = [
            '#type' => 'details',
            '#title' => $this->t('CORS (Cross-Origin Resource Sharing)'),
            '#open' => TRUE,
        ];

        $form['cors']['cors_allowed_origins'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Orígenes permitidos'),
            '#description' => $this->t('Lista de orígenes separados por coma. Ejemplo: https://mi-frontend.com, https://app.jaraba.es. Dejar vacío para bloquear todas las solicitudes cross-origin.'),
            '#default_value' => $config->get('cors.allowed_origins') ?? '',
            '#rows' => 3,
        ];

        // ═══════════════════════════════════════════════════
        // CSP
        // ═══════════════════════════════════════════════════
        $form['csp'] = [
            '#type' => 'details',
            '#title' => $this->t('CSP (Content Security Policy)'),
            '#open' => TRUE,
        ];

        $form['csp']['csp_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar CSP'),
            '#default_value' => $config->get('csp.enabled') ?? TRUE,
        ];

        $form['csp']['csp_policy'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Política CSP'),
            '#description' => $this->t('Política de seguridad de contenido. Se recomienda no modificar salvo necesidad.'),
            '#default_value' => $config->get('csp.policy') ?: "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob: *.stripe.com images.unsplash.com; connect-src 'self' api.stripe.com api.openai.com api.anthropic.com; frame-src 'self' js.stripe.com",
            '#rows' => 4,
            '#states' => [
                'visible' => [
                    ':input[name="csp_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        // ═══════════════════════════════════════════════════
        // HSTS
        // ═══════════════════════════════════════════════════
        $form['hsts'] = [
            '#type' => 'details',
            '#title' => $this->t('HSTS (HTTP Strict Transport Security)'),
            '#open' => FALSE,
        ];

        $form['hsts']['hsts_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar HSTS'),
            '#description' => $this->t('Solo activar en producción con HTTPS habilitado. Fuerza HTTPS durante 1 año.'),
            '#default_value' => $config->get('hsts.enabled') ?? FALSE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config(self::CONFIG_NAME)
            ->set('cors.allowed_origins', $form_state->getValue('cors_allowed_origins'))
            ->set('csp.enabled', (bool) $form_state->getValue('csp_enabled'))
            ->set('csp.policy', $form_state->getValue('csp_policy'))
            ->set('hsts.enabled', (bool) $form_state->getValue('hsts_enabled'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
