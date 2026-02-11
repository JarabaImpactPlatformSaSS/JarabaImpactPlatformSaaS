<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Service\AlertingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de configuración de alertas Slack/Teams.
 */
class AlertingSettingsForm extends ConfigFormBase
{

    /**
     * Servicio de alertas.
     */
    protected AlertingService $alertingService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->alertingService = $container->get('ecosistema_jaraba_core.alerting');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['ecosistema_jaraba_core.alerting'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ecosistema_jaraba_core_alerting_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('ecosistema_jaraba_core.alerting');

        $form['#prefix'] = '<div class="alerting-settings-form">';
        $form['#suffix'] = '</div>';

        $form['info'] = [
            '#markup' => '<p class="form-description">' .
                $this->t('Configura los webhooks de Slack y/o Microsoft Teams para recibir alertas en tiempo real.') .
                '</p>',
        ];

        // Slack Configuration.
        $form['slack'] = [
            '#type' => 'details',
            '#title' => $this->t('🔔 Configuración de Slack'),
            '#open' => TRUE,
        ];

        $form['slack']['slack_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar alertas de Slack'),
            '#default_value' => $config->get('slack_enabled') ?? FALSE,
        ];

        $form['slack']['slack_webhook_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Webhook URL de Slack'),
            '#description' => $this->t('Obtén la URL en: Slack App > Incoming Webhooks > Add New Webhook'),
            '#default_value' => $config->get('slack_webhook_url') ?? '',
            '#placeholder' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXX',
            '#states' => [
                'visible' => [
                    ':input[name="slack_enabled"]' => ['checked' => TRUE],
                ],
                'required' => [
                    ':input[name="slack_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        $form['slack']['slack_channel'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Canal de Slack (opcional)'),
            '#description' => $this->t('Deja vacío para usar el canal por defecto del webhook'),
            '#default_value' => $config->get('slack_channel') ?? '',
            '#placeholder' => '#alertas',
            '#states' => [
                'visible' => [
                    ':input[name="slack_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        // Teams Configuration.
        $form['teams'] = [
            '#type' => 'details',
            '#title' => $this->t('💬 Configuración de Microsoft Teams'),
            '#open' => TRUE,
        ];

        $form['teams']['teams_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar alertas de Teams'),
            '#default_value' => $config->get('teams_enabled') ?? FALSE,
        ];

        $form['teams']['teams_webhook_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Webhook URL de Teams'),
            '#description' => $this->t('Obtén la URL en: Canal > Conectores > Incoming Webhook'),
            '#default_value' => $config->get('teams_webhook_url') ?? '',
            '#placeholder' => 'https://outlook.office.com/webhook/...',
            '#states' => [
                'visible' => [
                    ':input[name="teams_enabled"]' => ['checked' => TRUE],
                ],
                'required' => [
                    ':input[name="teams_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        // Alert Types.
        $form['alerts'] = [
            '#type' => 'details',
            '#title' => $this->t('📋 Tipos de Alertas'),
            '#open' => TRUE,
        ];

        $form['alerts']['alert_financial_anomalies'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Anomalías financieras (FOC/FinOps)'),
            '#default_value' => $config->get('alert_financial_anomalies') ?? TRUE,
        ];

        $form['alerts']['alert_system_errors'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Errores del sistema'),
            '#default_value' => $config->get('alert_system_errors') ?? TRUE,
        ];

        $form['alerts']['alert_new_tenants'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Nuevos tenants registrados'),
            '#default_value' => $config->get('alert_new_tenants') ?? TRUE,
        ];

        $form['alerts']['alert_payments'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Pagos recibidos'),
            '#default_value' => $config->get('alert_payments') ?? FALSE,
        ];

        $form['alerts']['alert_trial_expiring'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Trials próximos a expirar'),
            '#default_value' => $config->get('alert_trial_expiring') ?? TRUE,
        ];

        // Test Button.
        $form['test'] = [
            '#type' => 'details',
            '#title' => $this->t('🧪 Probar Conexión'),
            '#open' => FALSE,
        ];

        $form['test']['test_alert'] = [
            '#type' => 'submit',
            '#value' => $this->t('Enviar alerta de prueba'),
            '#submit' => ['::sendTestAlert'],
            '#attributes' => ['class' => ['button--secondary']],
        ];

        // Estilos.
        $form['#attached']['html_head'][] = [
            [
                '#type' => 'html_tag',
                '#tag' => 'style',
                '#value' => '
          .alerting-settings-form {
            max-width: 800px;
            margin: 0 auto;
          }
          .alerting-settings-form .form-description {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            color: #475569;
          }
          .alerting-settings-form details {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
          }
          .alerting-settings-form details summary {
            font-weight: 600;
            cursor: pointer;
          }
        ',
            ],
            'alerting_settings_styles',
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('ecosistema_jaraba_core.alerting')
            ->set('slack_enabled', $form_state->getValue('slack_enabled'))
            ->set('slack_webhook_url', $form_state->getValue('slack_webhook_url'))
            ->set('slack_channel', $form_state->getValue('slack_channel'))
            ->set('teams_enabled', $form_state->getValue('teams_enabled'))
            ->set('teams_webhook_url', $form_state->getValue('teams_webhook_url'))
            ->set('alert_financial_anomalies', $form_state->getValue('alert_financial_anomalies'))
            ->set('alert_system_errors', $form_state->getValue('alert_system_errors'))
            ->set('alert_new_tenants', $form_state->getValue('alert_new_tenants'))
            ->set('alert_payments', $form_state->getValue('alert_payments'))
            ->set('alert_trial_expiring', $form_state->getValue('alert_trial_expiring'))
            ->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * Envía una alerta de prueba.
     */
    public function sendTestAlert(array &$form, FormStateInterface $form_state)
    {
        // Guardar primero la config.
        $this->submitForm($form, $form_state);

        // Enviar alerta de prueba.
        $this->alertingService->send(
            'Prueba de Conexión',
            '¡La integración de alertas está funcionando correctamente!',
            AlertingService::ALERT_SUCCESS,
            [
                ['title' => 'Plataforma', 'value' => 'Jaraba Impact Platform'],
                ['title' => 'Timestamp', 'value' => date('Y-m-d H:i:s')],
            ],
            'https://plataformadeecosistemas.es'
        );

        $this->messenger()->addStatus($this->t('Alerta de prueba enviada. Verifica tus canales de Slack/Teams.'));
    }

}
