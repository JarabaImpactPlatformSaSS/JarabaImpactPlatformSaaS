<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de Rate Limiting y protección AI.
 *
 * Permite configurar desde la interfaz de administración:
 * - Límites de requests por tipo de endpoint
 * - Parámetros del circuit breaker para proveedores LLM
 * - Límite de context window para prompts
 */
class RateLimitSettingsForm extends ConfigFormBase
{

    const CONFIG_NAME = 'ecosistema_jaraba_core.rate_limits';

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
        return 'ecosistema_jaraba_core_rate_limit_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config(self::CONFIG_NAME);

        $form['#prefix'] = '<div class="rate-limit-settings-form">';
        $form['#suffix'] = '</div>';

        // =====================================================================
        // RATE LIMITING
        // =====================================================================
        $form['rate_limits'] = [
            '#type' => 'details',
            '#title' => $this->t('Rate Limiting por Tipo de Endpoint'),
            '#open' => TRUE,
            '#description' => $this->t('Configura el número máximo de requests permitidos por ventana de tiempo para cada tipo de endpoint.'),
        ];

        $endpointTypes = [
            'api' => ['label' => 'API General', 'default_requests' => 100, 'default_window' => 60],
            'api_authenticated' => ['label' => 'API Autenticado', 'default_requests' => 200, 'default_window' => 60],
            'ai' => ['label' => 'AI (RAG, Copilot, Agentes)', 'default_requests' => 20, 'default_window' => 60],
            'search' => ['label' => 'Búsqueda', 'default_requests' => 30, 'default_window' => 60],
            'insights' => ['label' => 'Insights/Analytics', 'default_requests' => 50, 'default_window' => 60],
        ];

        foreach ($endpointTypes as $type => $info) {
            $form['rate_limits'][$type] = [
                '#type' => 'fieldset',
                '#title' => $info['label'],
            ];
            $form['rate_limits'][$type]["rate_limit_{$type}_requests"] = [
                '#type' => 'number',
                '#title' => $this->t('Máximo de requests'),
                '#default_value' => $config->get("limits.{$type}.requests") ?? $info['default_requests'],
                '#min' => 1,
                '#max' => 10000,
                '#required' => TRUE,
            ];
            $form['rate_limits'][$type]["rate_limit_{$type}_window"] = [
                '#type' => 'number',
                '#title' => $this->t('Ventana de tiempo (segundos)'),
                '#default_value' => $config->get("limits.{$type}.window") ?? $info['default_window'],
                '#min' => 10,
                '#max' => 3600,
                '#required' => TRUE,
            ];
        }

        // =====================================================================
        // CIRCUIT BREAKER
        // =====================================================================
        $form['circuit_breaker'] = [
            '#type' => 'details',
            '#title' => $this->t('Circuit Breaker para Proveedores LLM'),
            '#open' => TRUE,
            '#description' => $this->t('Configura el circuit breaker que protege contra costes excesivos cuando un proveedor de IA falla repetidamente.'),
        ];

        $form['circuit_breaker']['circuit_breaker_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Umbral de fallos consecutivos'),
            '#description' => $this->t('Número de fallos consecutivos antes de desactivar temporalmente un proveedor.'),
            '#default_value' => $config->get('circuit_breaker.threshold') ?? 5,
            '#min' => 1,
            '#max' => 50,
            '#required' => TRUE,
        ];

        $form['circuit_breaker']['circuit_breaker_cooldown'] = [
            '#type' => 'number',
            '#title' => $this->t('Tiempo de cooldown (segundos)'),
            '#description' => $this->t('Tiempo que un proveedor queda desactivado tras alcanzar el umbral de fallos.'),
            '#default_value' => $config->get('circuit_breaker.cooldown') ?? 300,
            '#min' => 30,
            '#max' => 3600,
            '#required' => TRUE,
        ];

        // =====================================================================
        // CONTEXT WINDOW
        // =====================================================================
        $form['context_window'] = [
            '#type' => 'details',
            '#title' => $this->t('Límite de Context Window'),
            '#open' => TRUE,
            '#description' => $this->t('Configura el límite máximo de caracteres para el contexto inyectado en los prompts del sistema. Valores más altos permiten más contexto pero aumentan el consumo de tokens.'),
        ];

        $form['context_window']['max_context_chars'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo de caracteres de contexto'),
            '#description' => $this->t('Aproximadamente 4 caracteres = 1 token. Recomendado: 8000 (~2000 tokens).'),
            '#default_value' => $config->get('context_window.max_chars') ?? 8000,
            '#min' => 1000,
            '#max' => 32000,
            '#required' => TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $config = $this->config(self::CONFIG_NAME);

        // Rate limits.
        foreach (['api', 'api_authenticated', 'ai', 'search', 'insights'] as $type) {
            $config->set("limits.{$type}.requests", (int) $form_state->getValue("rate_limit_{$type}_requests"));
            $config->set("limits.{$type}.window", (int) $form_state->getValue("rate_limit_{$type}_window"));
        }

        // Circuit breaker.
        $config->set('circuit_breaker.threshold', (int) $form_state->getValue('circuit_breaker_threshold'));
        $config->set('circuit_breaker.cooldown', (int) $form_state->getValue('circuit_breaker_cooldown'));

        // Context window.
        $config->set('context_window.max_chars', (int) $form_state->getValue('max_context_chars'));

        $config->save();

        parent::submitForm($form, $form_state);
    }

}
