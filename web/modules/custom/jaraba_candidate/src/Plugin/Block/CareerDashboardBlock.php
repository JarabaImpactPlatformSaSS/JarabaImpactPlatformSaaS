<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_candidate\Agent\CareerCoachAgent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a career dashboard block for the user profile page.
 *
 * @Block(
 *   id = "career_dashboard",
 *   admin_label = @Translation("Dashboard de Carrera Candidato"),
 *   category = @Translation("Empleabilidad")
 * )
 */
class CareerDashboardBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The career coach agent.
     */
    protected ?CareerCoachAgent $careerCoach;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        ?CareerCoachAgent $career_coach,
        AccountProxyInterface $current_user
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->careerCoach = $career_coach;
        $this->currentUser = $current_user;
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
            $career_coach,
            $container->get('current_user')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        // Only show for candidates.
        if (!$this->currentUser->hasPermission('access content')) {
            return [];
        }

        // Initialize with defaults.
        $phaseName = 'Evaluando';
        $phaseEmoji = 'business:target';
        $phaseNumber = 1;
        $completeness = 0;
        $tutorMessage = '';
        $gaps = [];
        $itineraries = [];
        $suggestedProducts = [];
        $nextActionLabel = '';
        $nextActionUrl = '/my-profile/edit';
        $nextActionIcon = 'actions:edit';

        if ($this->careerCoach) {
            // Get diagnosis from CareerCoachAgent.
            $diagnosis = $this->careerCoach->diagnoseCareerStage();
            $phaseName = $diagnosis['phase_name'] ?? 'Evaluando';
            $phaseEmoji = $diagnosis['phase_emoji'] ?? 'business:target';
            $phaseNumber = $diagnosis['phase'] ?? 1;
            $completeness = $diagnosis['completeness'] ?? 0;

            // Get enhanced data.
            $gaps = $this->careerCoach->detectGaps();
            $itineraries = $this->careerCoach->getItinerariesForGaps($gaps);
            $suggestedProducts = $this->careerCoach->getSuggestedProducts($phaseNumber, $gaps);

            // Get natural language tutor message.
            $tutorMessage = $this->careerCoach->getTutorMessage(
                $this->currentUser->getDisplayName(),
                $phaseNumber,
                $completeness,
                $gaps
            );

            // Next action based on phase.
            if (isset($diagnosis['next_action'])) {
                $nextActionLabel = $diagnosis['next_action']['label'] ?? '';
                $nextActionUrl = $diagnosis['next_action']['url'] ?? '/my-profile/edit';
                $nextActionIcon = $diagnosis['next_action']['icon'] ?? 'actions:edit';
            }
        }

        $quickActions = $this->getQuickActions();

        return [
            '#theme' => 'career_dashboard',
            '#phase_name' => $phaseName,
            '#phase_emoji' => $phaseEmoji,
            '#phase_number' => $phaseNumber,
            '#completeness' => $completeness,
            '#tutor_message' => $tutorMessage,
            '#gaps' => $gaps,
            '#itineraries' => $itineraries,
            '#suggested_products' => $suggestedProducts,
            '#next_action_label' => $nextActionLabel,
            '#next_action_url' => $nextActionUrl,
            '#next_action_icon' => $nextActionIcon,
            '#quick_actions' => $quickActions,
            '#user_name' => $this->currentUser->getDisplayName(),
            '#cache' => [
                'contexts' => ['user', 'user.roles'],
                'tags' => ['user:' . $this->currentUser->id()],
                'max-age' => 300,
            ],
            '#attached' => [
                'library' => ['jaraba_candidate/career_dashboard'],
            ],
        ];
    }

    /**
     * Gets quick action links for the dashboard.
     */
    protected function getQuickActions(): array
    {
        return [
            [
                'id' => 'complete_profile',
                'label' => $this->t('Completar perfil'),
                'url' => '/my-profile/edit',
                'icon' => 'actions:edit',
                'color' => 'primary',
            ],
            [
                'id' => 'view_jobs',
                'label' => $this->t('Buscar ofertas'),
                'url' => '/jobs',
                'icon' => 'business:job',
                'color' => 'secondary',
            ],
            [
                'id' => 'my_courses',
                'label' => $this->t('Mis cursos'),
                'url' => '/courses',
                'icon' => 'ui:graduation',
                'color' => 'accent',
            ],
            [
                'id' => 'applications',
                'label' => $this->t('Mis candidaturas'),
                'url' => '/my-applications',
                'icon' => 'ui:inbox',
                'color' => 'info',
            ],
        ];
    }

}
