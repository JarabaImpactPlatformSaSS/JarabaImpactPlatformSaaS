<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_job_board\Agent\RecruiterAssistantAgent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium Recruiter Dashboard Block.
 *
 * Displays proactive AI-driven insights for employers on their profile page.
 *
 * @Block(
 *   id = "recruiter_dashboard_block",
 *   admin_label = @Translation("Recruiter Dashboard"),
 *   category = @Translation("Job Board")
 * )
 */
class RecruiterDashboardBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The recruiter assistant agent.
     */
    protected ?RecruiterAssistantAgent $recruiterAgent;

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
        AccountProxyInterface $current_user,
        ?RecruiterAssistantAgent $recruiter_agent = NULL
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->currentUser = $current_user;
        $this->recruiterAgent = $recruiter_agent;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ): static {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('current_user'),
            $container->get('jaraba_job_board.recruiter_assistant', ContainerInterface::NULL_ON_INVALID_REFERENCE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        // Only show for employers.
        if (!$this->currentUser->hasPermission('create job_posting')) {
            return [];
        }

        // Initialize defaults.
        $phaseName = $this->t('Evaluando');
        $phaseIconCategory = 'business';
        $phaseIconName = 'target';
        $phaseNumber = 1;
        $healthScore = 0;
        $assistantMessage = '';
        $gaps = [];
        $optimizationPaths = [];
        $suggestedTools = [];
        $metrics = [];

        if ($this->recruiterAgent) {
            // Get health analysis.
            $health = $this->recruiterAgent->analyzeRecruitingHealth();
            $phaseName = (string) ($health['phase_name'] ?? $this->t('Evaluando'));
            $phaseIconCategory = $health['phase_icon_category'] ?? 'business';
            $phaseIconName = $health['phase_icon_name'] ?? 'target';
            $phaseNumber = $health['phase'] ?? 1;
            $healthScore = $health['health_score'] ?? 0;
            $metrics = $health['metrics'] ?? [];

            // Get gaps and optimization paths.
            $gaps = $this->recruiterAgent->detectRecruitingGaps();
            $optimizationPaths = $this->recruiterAgent->getOptimizationPaths($gaps);
            $suggestedTools = $this->recruiterAgent->getSuggestedTools($gaps);

            // Get personalized message.
            $assistantMessage = $this->recruiterAgent->getAssistantMessage(
                $this->currentUser->getDisplayName(),
                $metrics,
                $gaps
            );
        }

        $quickActions = $this->getQuickActions();

        return [
            '#theme' => 'recruiter_dashboard',
            '#phase_name' => $phaseName,
            '#phase_icon_category' => $phaseIconCategory,
            '#phase_icon_name' => $phaseIconName,
            '#phase_number' => $phaseNumber,
            '#health_score' => $healthScore,
            '#metrics' => $metrics,
            '#assistant_message' => $assistantMessage,
            '#gaps' => $gaps,
            '#optimization_paths' => $optimizationPaths,
            '#suggested_tools' => $suggestedTools,
            '#quick_actions' => $quickActions,
            '#user_name' => $this->currentUser->getDisplayName(),
            '#cache' => [
                'contexts' => ['user', 'user.roles'],
                'tags' => ['user:' . $this->currentUser->id()],
                'max-age' => 300,
            ],
            '#attached' => [
                'library' => ['jaraba_job_board/recruiter_dashboard'],
            ],
        ];
    }

    /**
     * Gets quick action links for employers.
     */
    protected function getQuickActions(): array
    {
        return [
            [
                'label' => t('Crear oferta'),
                'url' => '/admin/content/jobs/add',
                'icon_category' => 'business',
                'icon_name' => 'diagnostic',
                'color' => 'primary',
            ],
            [
                'label' => t('Ver candidaturas'),
                'url' => '/admin/content/applications',
                'icon_category' => 'ui',
                'icon_name' => 'users',
                'color' => 'secondary',
            ],
            [
                'label' => t('Mis ofertas'),
                'url' => '/admin/content/jobs',
                'icon_category' => 'business',
                'icon_name' => 'diagnostic',
                'color' => 'info',
            ],
            [
                'label' => t('Analíticas'),
                'url' => '/my-company/analytics',
                'icon_category' => 'analytics',
                'icon_name' => 'chart-bar',
                'color' => 'accent',
            ],
        ];
    }

}
