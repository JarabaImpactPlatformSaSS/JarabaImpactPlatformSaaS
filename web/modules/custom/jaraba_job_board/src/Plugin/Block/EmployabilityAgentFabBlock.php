<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jaraba_job_board\Service\EmployabilityMenuService;
use Drupal\jaraba_candidate\Agent\CareerCoachAgent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a floating action button for AI agents.
 *
 * @Block(
 *   id = "employability_agent_fab",
 *   admin_label = @Translation("FAB Agentes IA Empleabilidad"),
 *   category = @Translation("Empleabilidad")
 * )
 */
class EmployabilityAgentFabBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The employability menu service.
     */
    protected EmployabilityMenuService $menuService;

    /**
     * The career coach agent (for candidates).
     */
    protected ?CareerCoachAgent $careerCoach;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EmployabilityMenuService $menu_service,
        ?CareerCoachAgent $career_coach = NULL
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->menuService = $menu_service;
        $this->careerCoach = $career_coach;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        $career_coach = NULL;
        if ($container->has('jaraba_candidate.agent.career_coach')) {
            $career_coach = $container->get('jaraba_candidate.agent.career_coach');
        }

        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('jaraba_job_board.employability_menu'),
            $career_coach
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        $role = $this->menuService->detectUserRole();

        if ($role === 'anonymous') {
            return [];
        }

        // Get current user's name for personalization
        $currentUser = \Drupal::currentUser();
        $userName = $currentUser->getDisplayName();

        // Get first name if possible (split by space)
        $nameParts = explode(' ', $userName);
        $firstName = $nameParts[0] ?? $userName;

        $agentConfig = $this->getAgentConfigForRole($role, $firstName);

        // For candidates, add onboarding diagnosis from CareerCoachAgent
        $onboarding = NULL;
        $softSuggestion = NULL;
        if ($role === 'candidate' && $this->careerCoach) {
            $onboarding = $this->careerCoach->getOnboardingMessage();
            $softSuggestion = $this->careerCoach->getSoftSuggestion();
        }

        return [
            '#theme' => 'employability_agent_fab',
            '#agent' => $agentConfig,
            '#role' => $role,
            '#user_name' => $firstName,
            '#cache' => [
                'contexts' => ['user.roles', 'user.permissions', 'user'],
                'tags' => ['employability_agent'],
            ],
            '#attached' => [
                'library' => ['jaraba_job_board/agent_fab'],
                'drupalSettings' => [
                    'employabilityAgent' => [
                        'onboarding' => $onboarding,
                        'softSuggestion' => $softSuggestion,
                    ],
                ],
            ],
        ];
    }


    /**
     * Gets agent configuration by role.
     */
    protected function getAgentConfigForRole(string $role, string $userName = ''): array
    {
        $greeting_suffix = $userName ? ", {$userName}" : '';

        $agents = [
            'candidate' => [
                'id' => 'career_coach',
                'name' => $this->t('Coach de Carrera'),
                'icon_category' => 'business',
                'icon_name' => 'target',
                'color' => '#0ea5e9',
                'greeting' => $this->t('¡Hola@suffix! Soy tu Coach de Carrera. ¿En qué puedo ayudarte hoy?', ['@suffix' => $greeting_suffix]),
                'actions' => [
                    ['id' => 'analyze_profile', 'label' => $this->t('Analizar mi perfil'), 'icon_category' => 'analytics', 'icon_name' => 'gauge'],
                    ['id' => 'improve_cv', 'label' => $this->t('Mejorar mi CV'), 'icon_category' => 'business', 'icon_name' => 'diagnostic'],
                    ['id' => 'interview_prep', 'label' => $this->t('Preparar entrevista'), 'icon_category' => 'ui', 'icon_name' => 'users'],
                    ['id' => 'suggest_courses', 'label' => $this->t('Recomendar formación'), 'icon_category' => 'ui', 'icon_name' => 'book'],
                    ['id' => 'motivation', 'label' => $this->t('Motivación'), 'icon_category' => 'verticals', 'icon_name' => 'rocket'],
                ],
            ],
            'employer' => [
                'id' => 'recruiter_assistant',
                'name' => $this->t('Asistente de Selección'),
                'icon_category' => 'business',
                'icon_name' => 'job',
                'color' => '#059669',
                'greeting' => $this->t('¡Hola@suffix! Soy tu Asistente de Selección. ¿Cómo puedo ayudarte a encontrar talento?', ['@suffix' => $greeting_suffix]),
                'actions' => [
                    ['id' => 'screen_candidates', 'label' => $this->t('Filtrar candidatos'), 'icon_category' => 'ui', 'icon_name' => 'search'],
                    ['id' => 'rank_applicants', 'label' => $this->t('Rankear postulantes'), 'icon_category' => 'business', 'icon_name' => 'achievement'],
                    ['id' => 'optimize_jd', 'label' => $this->t('Mejorar oferta'), 'icon_category' => 'ai', 'icon_name' => 'sparkle'],
                    ['id' => 'suggest_questions', 'label' => $this->t('Preguntas de entrevista'), 'icon_category' => 'ai', 'icon_name' => 'lightbulb'],
                    ['id' => 'process_analytics', 'label' => $this->t('Analizar proceso'), 'icon_category' => 'analytics', 'icon_name' => 'gauge'],
                ],
            ],
            'student' => [
                'id' => 'learning_tutor',
                'name' => $this->t('Tutor de Aprendizaje'),
                'icon_category' => 'ui',
                'icon_name' => 'book',
                'color' => '#f59e0b',
                'greeting' => $this->t('¡Hola@suffix! Soy tu Tutor personal. ¿Qué te gustaría aprender hoy?', ['@suffix' => $greeting_suffix]),
                'actions' => [
                    ['id' => 'ask_question', 'label' => $this->t('Tengo una duda'), 'icon_category' => 'ai', 'icon_name' => 'lightbulb'],
                    ['id' => 'explain_concept', 'label' => $this->t('Explícame esto'), 'icon_category' => 'ai', 'icon_name' => 'brain'],
                    ['id' => 'suggest_path', 'label' => $this->t('Mi ruta de aprendizaje'), 'icon_category' => 'ui', 'icon_name' => 'map'],
                    ['id' => 'study_tips', 'label' => $this->t('Técnicas de estudio'), 'icon_category' => 'ai', 'icon_name' => 'brain'],
                    ['id' => 'motivation_boost', 'label' => $this->t('Necesito motivación'), 'icon_category' => 'verticals', 'icon_name' => 'rocket'],
                ],
            ],
        ];

        return $agents[$role] ?? $agents['candidate'];
    }

}
