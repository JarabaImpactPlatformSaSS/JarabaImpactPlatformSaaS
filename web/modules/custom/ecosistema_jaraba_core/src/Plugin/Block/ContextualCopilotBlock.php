<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\CopilotContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contextual copilot FAB block for all avatars.
 *
 * Configurable greeting, quick actions and agent context per placement.
 * Replaces separate FAB blocks for each vertical.
 *
 * @Block(
 *   id = "contextual_copilot",
 *   admin_label = @Translation("Copiloto Contextual (FAB)"),
 *   category = @Translation("Jaraba - IA"),
 * )
 */
class ContextualCopilotBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The current user.
     */
    protected AccountInterface $currentUser;

    /**
     * The copilot context service.
     */
    protected CopilotContextService $copilotContext;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        $instance = new static($configuration, $plugin_id, $plugin_definition);
        $instance->currentUser = $container->get('current_user');
        $instance->copilotContext = $container->get('ecosistema_jaraba_core.copilot_context');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration(): array
    {
        return [
            'agent_context' => 'general_copilot',
            'greeting' => '¡Hola! 👋 ¿En qué puedo ayudarte?',
            'avatar_type' => 'general',
            'fab_color' => '#FF8C42',
            'quick_actions' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state): array
    {
        $config = $this->getConfiguration();

        $form['avatar_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Tipo de Avatar'),
            '#options' => [
                'general' => $this->t('General'),
                'jobseeker' => $this->t('Job Seeker (Candidato)'),
                'recruiter' => $this->t('Recruiter (Empleador)'),
                'entrepreneur' => $this->t('Entrepreneur (Emprendedor)'),
                'producer' => $this->t('Producer (Productor/Comercio)'),
                'mentor' => $this->t('Mentor'),
            ],
            '#default_value' => $config['avatar_type'],
            '#description' => $this->t('Seleccionar preset o "General" para configuración manual.'),
        ];

        $form['agent_context'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Agent Context ID'),
            '#default_value' => $config['agent_context'],
            '#description' => $this->t('Identificador del agente para analytics.'),
        ];

        $form['greeting'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Mensaje de Bienvenida'),
            '#default_value' => $config['greeting'],
            '#rows' => 2,
        ];

        $form['fab_color'] = [
            '#type' => 'color',
            '#title' => $this->t('Color del FAB'),
            '#default_value' => $config['fab_color'],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state): void
    {
        $this->configuration['avatar_type'] = $form_state->getValue('avatar_type');
        $this->configuration['agent_context'] = $form_state->getValue('agent_context');
        $this->configuration['greeting'] = $form_state->getValue('greeting');
        $this->configuration['fab_color'] = $form_state->getValue('fab_color');
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        $config = $this->getConfiguration();

        // Primero intentar detección dinámica de avatar
        $copilotContext = $this->copilotContext->getContext();

        // Si la configuración del bloque es 'general' o vacía, usar detección dinámica
        $avatar_type = $config['avatar_type'];
        if ($avatar_type === 'general' && $copilotContext['avatar'] !== 'general') {
            $avatar_type = $copilotContext['avatar'];
        }

        // Get preset configuration based on avatar type.
        $preset = $this->getAvatarPreset($avatar_type);

        // Merge preset with manual config (manual wins if set).
        $greeting = !empty($config['greeting']) ? $config['greeting'] : $preset['greeting'];
        $agent_context = !empty($config['agent_context']) ? $config['agent_context'] : $preset['agent_context'];
        $quick_actions = $preset['quick_actions'];
        $fab_color = !empty($config['fab_color']) ? $config['fab_color'] : $preset['fab_color'];

        // Personalizar greeting si hay nombre de usuario
        if ($copilotContext['user_name'] && str_contains($greeting, '¡Hola!')) {
            $greeting = str_replace('¡Hola!', '¡Hola, ' . $copilotContext['user_name'] . '!', $greeting);
        }

        return [
            '#theme' => 'contextual_copilot_fab',
            '#agent_context' => $agent_context,
            '#greeting' => $greeting,
            '#avatar_type' => $avatar_type,
            '#fab_color' => $fab_color,
            '#quick_actions' => $quick_actions,
            '#user' => $this->currentUser,
            '#copilot_context' => $copilotContext,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/contextual-copilot',
                ],
                'drupalSettings' => [
                    'contextualCopilot' => [
                        'agentContext' => $agent_context,
                        'avatarType' => $avatar_type,
                        'userId' => $this->currentUser->id(),
                        'userName' => $copilotContext['user_name'] ?? '',
                        'tenantId' => $copilotContext['tenant_id'],
                        'tenantName' => $copilotContext['tenant_name'],
                        'vertical' => $copilotContext['vertical'],
                        'plan' => $copilotContext['plan'],
                        'isAuthenticated' => $copilotContext['is_authenticated'],
                    ],
                ],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.path'],
                'max-age' => 0,
            ],
        ];
    }

    /**
     * Gets preset configuration for avatar type.
     */
    protected function getAvatarPreset(string $avatar_type): array
    {
        $presets = [
            'jobseeker' => [
                'agent_context' => 'career_copilot',
                'greeting' => $this->t('¡Hola! 👋 Soy tu copiloto de carrera. ¿Buscas empleo o mejorar tu perfil?'),
                'fab_color' => '#00A9A5',
                'quick_actions' => [
                    ['action' => 'search_jobs', 'icon' => '🔍', 'label' => $this->t('Buscar ofertas')],
                    ['action' => 'improve_cv', 'icon' => '📄', 'label' => $this->t('Mejorar CV')],
                    ['action' => 'recommendations', 'icon' => '🎯', 'label' => $this->t('Recomendaciones')],
                    ['action' => 'interview_prep', 'icon' => '🎤', 'label' => $this->t('Preparar entrevista')],
                ],
            ],
            'recruiter' => [
                'agent_context' => 'recruiter_copilot',
                'greeting' => $this->t('¡Hola! 👋 Soy tu asistente de reclutamiento. ¿Buscas talento?'),
                'fab_color' => '#233D63',
                'quick_actions' => [
                    ['action' => 'search_candidates', 'icon' => '👥', 'label' => $this->t('Buscar candidatos')],
                    ['action' => 'post_job', 'icon' => '📝', 'label' => $this->t('Publicar oferta')],
                    ['action' => 'screen_applications', 'icon' => '📋', 'label' => $this->t('Filtrar candidaturas')],
                    ['action' => 'analytics', 'icon' => '📊', 'label' => $this->t('Ver analytics')],
                ],
            ],
            'entrepreneur' => [
                'agent_context' => 'entrepreneur_copilot',
                'greeting' => $this->t('¡Hola! 👋 Soy tu copiloto de emprendimiento. ¿Validamos tu idea?'),
                'fab_color' => '#FF8C42',
                'quick_actions' => [
                    ['action' => 'analyze_canvas', 'icon' => '🔍', 'label' => $this->t('Analizar Canvas')],
                    ['action' => 'generate_canvas', 'icon' => '✨', 'label' => $this->t('Generar Canvas')],
                    ['action' => 'next_step', 'icon' => '🚀', 'label' => $this->t('Próximo paso')],
                    ['action' => 'find_mentor', 'icon' => '👨‍🏫', 'label' => $this->t('Buscar mentor')],
                ],
            ],
            'producer' => [
                'agent_context' => 'producer_copilot',
                'greeting' => $this->t('¡Hola! 👋 Soy tu asistente de comercio. ¿Quieres vender más?'),
                'fab_color' => '#556B2F',
                'quick_actions' => [
                    ['action' => 'add_product', 'icon' => '📦', 'label' => $this->t('Añadir producto')],
                    ['action' => 'view_orders', 'icon' => '🛒', 'label' => $this->t('Ver pedidos')],
                    ['action' => 'optimize_listing', 'icon' => '✨', 'label' => $this->t('Optimizar ficha')],
                    ['action' => 'analytics', 'icon' => '📊', 'label' => $this->t('Ver ventas')],
                ],
            ],
            'mentor' => [
                'agent_context' => 'mentor_copilot',
                'greeting' => $this->t('¡Hola! 👋 Soy tu asistente de mentoría. ¿Cómo ayudo hoy?'),
                'fab_color' => '#8B5CF6',
                'quick_actions' => [
                    ['action' => 'view_mentees', 'icon' => '👥', 'label' => $this->t('Mis mentorizados')],
                    ['action' => 'schedule_session', 'icon' => '📅', 'label' => $this->t('Programar sesión')],
                    ['action' => 'review_canvas', 'icon' => '📋', 'label' => $this->t('Revisar Canvas')],
                    ['action' => 'send_feedback', 'icon' => '💬', 'label' => $this->t('Enviar feedback')],
                ],
            ],
            'general' => [
                'agent_context' => 'general_copilot',
                'greeting' => $this->t('¡Hola! 👋 ¿En qué puedo ayudarte hoy?'),
                'fab_color' => '#FF8C42',
                'quick_actions' => [
                    ['action' => 'help', 'icon' => '❓', 'label' => $this->t('Ayuda')],
                    ['action' => 'explore', 'icon' => '🔍', 'label' => $this->t('Explorar')],
                ],
            ],
        ];

        return $presets[$avatar_type] ?? $presets['general'];
    }

}
