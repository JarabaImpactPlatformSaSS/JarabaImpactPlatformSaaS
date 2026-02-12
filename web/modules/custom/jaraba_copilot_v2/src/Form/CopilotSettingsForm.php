<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de configuración del Copiloto v2.
 *
 * Integra con el módulo Key para gestión segura de claves API.
 */
class CopilotSettingsForm extends ConfigFormBase
{

    /**
     * Config settings name.
     */
    const CONFIG_NAME = 'jaraba_copilot_v2.settings';

    /**
     * The key repository.
     *
     * @var \Drupal\key\KeyRepositoryInterface
     */
    protected KeyRepositoryInterface $keyRepository;

    /**
     * Constructs a CopilotSettingsForm object.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        TypedConfigManagerInterface $typedConfigManager,
        KeyRepositoryInterface $key_repository
    ) {
        parent::__construct($config_factory, $typedConfigManager);
        $this->keyRepository = $key_repository;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('config.factory'),
            $container->get('config.typed'),
            $container->get('key.repository')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return [static::CONFIG_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_copilot_v2_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config(static::CONFIG_NAME);

        // =========================================================================
        // API Configuration Section
        // =========================================================================
        $form['api'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de API'),
            '#open' => TRUE,
        ];

        $form['api']['claude_api_key'] = [
            '#type' => 'key_select',
            '#title' => $this->t('Clave API de Claude'),
            '#description' => $this->t('Selecciona la clave de API de Anthropic del repositorio de claves. <a href="/admin/config/system/keys/add">Añadir nueva clave</a>.'),
            '#default_value' => $config->get('claude_api_key'),
            '#required' => FALSE,
        ];

        $form['api']['claude_model'] = [
            '#type' => 'select',
            '#title' => $this->t('Modelo de Claude'),
            '#options' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recomendado)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (Más potente)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (Rápido y económico)',
            ],
            '#default_value' => $config->get('claude_model') ?? 'claude-3-5-sonnet-20241022',
        ];

        $form['api']['max_tokens'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo de Tokens'),
            '#description' => $this->t('Límite de tokens en la respuesta del Copiloto.'),
            '#default_value' => $config->get('max_tokens') ?? 2048,
            '#min' => 256,
            '#max' => 8192,
        ];

        // =========================================================================
        // Features Section
        // =========================================================================
        $form['features'] = [
            '#type' => 'details',
            '#title' => $this->t('Funcionalidades'),
            '#open' => TRUE,
        ];

        $form['features']['progressive_unlock'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar desbloqueo progresivo'),
            '#description' => $this->t('Las funcionalidades se desbloquean según la semana del programa (1-12).'),
            '#default_value' => $config->get('progressive_unlock') ?? TRUE,
        ];

        $form['features']['expert_modes'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar modos expertos (Fiscal/Laboral)'),
            '#description' => $this->t('Habilita los modos Experto Tributario y Experto Seguridad Social con base de conocimiento normativo.'),
            '#default_value' => $config->get('expert_modes') ?? TRUE,
        ];

        $form['features']['auto_mode_detection'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Detección automática de modo'),
            '#description' => $this->t('El Copiloto detecta automáticamente el modo apropiado según el mensaje del usuario.'),
            '#default_value' => $config->get('auto_mode_detection') ?? TRUE,
        ];

        // =========================================================================
        // Copilot Modes Section
        // =========================================================================
        $form['modes'] = [
            '#type' => 'details',
            '#title' => $this->t('Modos del Copiloto'),
            '#open' => FALSE,
            '#description' => $this->t('Configura qué modos están habilitados y su semana de desbloqueo.'),
        ];

        $modes = [
            'coach' => ['label' => 'Coach Emocional', 'default_week' => 0],
            'consultor' => ['label' => 'Consultor Tactico', 'default_week' => 0],
            'sparring' => ['label' => 'Sparring Partner', 'default_week' => 4],
            'cfo' => ['label' => 'CFO Sintetico', 'default_week' => 6],
            'fiscal' => ['label' => 'Experto Tributario', 'default_week' => 8],
            'laboral' => ['label' => 'Experto Seg. Social', 'default_week' => 8],
            'devil' => ['label' => 'Abogado del Diablo', 'default_week' => 10],
        ];

        foreach ($modes as $key => $mode) {
            $form['modes']["mode_{$key}"] = [
                '#type' => 'checkbox',
                '#title' => $this->t('@label', ['@label' => $mode['label']]),
                '#default_value' => $config->get("modes.{$key}.enabled") ?? TRUE,
            ];
        }

        // =========================================================================
        // Status Display Section
        // =========================================================================
        $form['status'] = [
            '#type' => 'details',
            '#title' => $this->t('Estado del Sistema'),
            '#open' => TRUE,
        ];

        // Check API key status
        $apiKeyId = $config->get('claude_api_key');
        $apiKeyStatus = $this->t('No configurada');
        if ($apiKeyId) {
            $key = $this->keyRepository->getKey($apiKeyId);
            if ($key && $key->getKeyValue()) {
                $apiKeyStatus = $this->t('Configurada (@key)', ['@key' => $apiKeyId]);
            } else {
                $apiKeyStatus = $this->t('Clave seleccionada pero vacia');
            }
        }

        $form['status']['api_status'] = [
            '#type' => 'item',
            '#title' => $this->t('Estado de la API de Claude'),
            '#markup' => $apiKeyStatus,
        ];

        $form['status']['normative_kb'] = [
            '#type' => 'item',
            '#title' => $this->t('Base de Conocimiento Normativo'),
            '#markup' => $this->getNormativeKBStatus(),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Gets the normative knowledge base status.
     */
    protected function getNormativeKBStatus(): string
    {
        try {
            $count = \Drupal::database()
                ->select('normative_knowledge_base', 'n')
                ->countQuery()
                ->execute()
                ->fetchField();

            return (string) $this->t('@count registros cargados', ['@count' => $count]);
        } catch (\Exception $e) {
            return (string) $this->t('Tabla no encontrada');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config(static::CONFIG_NAME)
            // API.
            ->set('claude_api_key', $form_state->getValue(['api', 'claude_api_key']))
            ->set('claude_model', $form_state->getValue(['api', 'claude_model']))
            ->set('max_tokens', (int) $form_state->getValue(['api', 'max_tokens']))
            // Features.
            ->set('progressive_unlock', (bool) $form_state->getValue(['features', 'progressive_unlock']))
            ->set('expert_modes', (bool) $form_state->getValue(['features', 'expert_modes']))
            ->set('auto_mode_detection', (bool) $form_state->getValue(['features', 'auto_mode_detection']))
            // Modes.
            ->set('modes.coach.enabled', (bool) $form_state->getValue(['modes', 'mode_coach']))
            ->set('modes.consultor.enabled', (bool) $form_state->getValue(['modes', 'mode_consultor']))
            ->set('modes.sparring.enabled', (bool) $form_state->getValue(['modes', 'mode_sparring']))
            ->set('modes.cfo.enabled', (bool) $form_state->getValue(['modes', 'mode_cfo']))
            ->set('modes.fiscal.enabled', (bool) $form_state->getValue(['modes', 'mode_fiscal']))
            ->set('modes.laboral.enabled', (bool) $form_state->getValue(['modes', 'mode_laboral']))
            ->set('modes.devil.enabled', (bool) $form_state->getValue(['modes', 'mode_devil']))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
