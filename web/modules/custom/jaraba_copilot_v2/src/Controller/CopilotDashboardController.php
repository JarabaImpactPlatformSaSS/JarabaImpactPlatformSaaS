<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_copilot_v2\Service\BmcValidationService;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;

/**
 * Controller for the entrepreneur copilot dashboard.
 */
class CopilotDashboardController extends ControllerBase
{

    /**
     * The feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * The BMC validation service.
     */
    protected BmcValidationService $bmcValidation;

    /**
     * Constructs a CopilotDashboardController object.
     */
    public function __construct(
        FeatureUnlockService $featureUnlock,
        BmcValidationService $bmcValidation,
        EntityTypeManagerInterface $entityTypeManager,
    ) {
        $this->featureUnlock = $featureUnlock;
        $this->bmcValidation = $bmcValidation;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_copilot_v2.feature_unlock'),
            $container->get('jaraba_copilot_v2.bmc_validation'),
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Displays the entrepreneur dashboard.
     *
     * @return array
     *   A render array.
     */
    public function dashboard(): array
    {
        $unlockStatus = $this->featureUnlock->getUnlockStatus();
        $availableModes = $this->featureUnlock->getAvailableCopilotModes();

        // Cargar ultimos milestones del emprendedor.
        $userId = (string) $this->currentUser()->id();
        $profile = $this->loadUserProfile($userId);
        $milestones = [];
        if ($profile) {
            $milestones = $this->loadRecentMilestones((int) $profile->id());
        }

        return [
            '#theme' => 'copilot_dashboard',
            '#unlock_status' => $unlockStatus,
            '#available_modes' => $availableModes,
            '#milestones' => $milestones,
            '#attached' => [
                'library' => ['jaraba_copilot_v2/dashboard'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['entrepreneur_profile'],
            ],
        ];
    }

    /**
     * Displays the BMC Dashboard with validation semaphores.
     *
     * @return array
     *   A render array.
     */
    public function bmcDashboard(): array
    {
        $userId = (string) $this->currentUser()->id();
        $validation = $this->bmcValidation->getValidationState($userId);
        $profile = $this->loadUserProfile($userId);

        $impactPoints = $profile ? (int) ($profile->get('impact_points')->value ?? 0) : 0;
        $level = $this->calculateLevel($impactPoints);

        return [
            '#theme' => 'bmc_dashboard',
            '#blocks' => $validation['blocks'] ?? [],
            '#overall_percentage' => $validation['overall_percentage'] ?? 0,
            '#total_hypotheses' => $validation['total_hypotheses'] ?? 0,
            '#validated_hypotheses' => $validation['validated_hypotheses'] ?? 0,
            '#profile' => $profile,
            '#impact_points' => $impactPoints,
            '#level' => $level,
            '#next_level_points' => $this->getNextLevelThreshold($level),
            '#attached' => [
                'library' => ['jaraba_copilot_v2/bmc-dashboard'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['entrepreneur_profile', 'hypothesis_list'],
            ],
        ];
    }

    /**
     * Displays the Hypothesis Manager page.
     *
     * @return array
     *   A render array.
     */
    public function hypothesisManager(): array
    {
        $userId = (string) $this->currentUser()->id();
        $profile = $this->loadUserProfile($userId);
        $profileId = $profile ? $profile->id() : NULL;

        $hypotheses = [];
        if ($profileId) {
            $storage = $this->entityTypeManager->getStorage('hypothesis');
            $ids = $storage->getQuery()
                ->condition('profile_id', $profileId)
                ->sort('created', 'DESC')
                ->accessCheck(TRUE)
                ->execute();
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                $hypotheses[] = [
                    'id' => $entity->id(),
                    'statement' => $entity->get('statement')->value ?? '',
                    'type' => $entity->get('type')->value ?? 'DESIRABILITY',
                    'bmc_block' => $entity->get('bmc_block')->value ?? '',
                    'status' => $entity->get('status')->value ?? 'DRAFT',
                    'importance' => (int) ($entity->get('importance')->value ?? 5),
                    'confidence' => (int) ($entity->get('confidence')->value ?? 5),
                    'evidence' => (int) ($entity->get('evidence')->value ?? 5),
                    'ice_score' => (int) ($entity->get('importance')->value ?? 5)
                        * (int) ($entity->get('confidence')->value ?? 5)
                        * (int) ($entity->get('evidence')->value ?? 5),
                ];
            }
        }

        $bmcBlocks = ['CS', 'VP', 'CH', 'CR', 'RS', 'KR', 'KA', 'KP', 'C$'];

        return [
            '#theme' => 'hypothesis_manager',
            '#hypotheses' => $hypotheses,
            '#filters' => [
                'types' => ['DESIRABILITY', 'VIABILITY', 'FEASIBILITY'],
                'statuses' => ['DRAFT', 'TESTING', 'VALIDATED', 'INVALIDATED'],
                'bmc_blocks' => $bmcBlocks,
            ],
            '#total' => count($hypotheses),
            '#profile_id' => $profileId,
            '#attached' => [
                'library' => ['jaraba_copilot_v2/hypothesis-manager'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['hypothesis_list'],
            ],
        ];
    }

    /**
     * Displays the Experiment Lifecycle page.
     *
     * @return array
     *   A render array.
     */
    public function experimentLifecycle(): array
    {
        $userId = (string) $this->currentUser()->id();
        $profile = $this->loadUserProfile($userId);
        $profileId = $profile ? $profile->id() : NULL;

        $experiments = [];
        $stats = [
            'total' => 0,
            'planned' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        if ($profileId) {
            $storage = $this->entityTypeManager->getStorage('experiment');
            $ids = $storage->getQuery()
                ->condition('profile_id', $profileId)
                ->sort('created', 'DESC')
                ->accessCheck(TRUE)
                ->execute();
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                $status = $entity->get('status')->value ?? 'PLANNED';
                $experiments[] = [
                    'id' => $entity->id(),
                    'title' => $entity->get('title')->value ?? '',
                    'type' => $entity->get('experiment_type')->value ?? '',
                    'status' => $status,
                    'hypothesis_id' => $entity->get('hypothesis_id')->value ?? '',
                    'decision' => $entity->get('decision')->value ?? '',
                    'observations' => $entity->get('observations')->value ?? '',
                    'metric' => $entity->get('metric')->value ?? '',
                    'success_criteria' => $entity->get('success_criteria')->value ?? '',
                    'points_awarded' => (int) ($entity->get('points_awarded')->value ?? 0),
                    'created' => $entity->get('created')->value ?? '',
                ];

                $stats['total']++;
                $statusKey = strtolower($status);
                if (isset($stats[$statusKey])) {
                    $stats[$statusKey]++;
                }
            }
        }

        return [
            '#theme' => 'experiment_lifecycle',
            '#experiments' => $experiments,
            '#stats' => $stats,
            '#profile_id' => $profileId,
            '#attached' => [
                'library' => ['jaraba_copilot_v2/experiment-lifecycle'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['experiment_list'],
            ],
        ];
    }

    /**
     * Loads recent milestones for an entrepreneur profile.
     */
    protected function loadRecentMilestones(int $profileId, int $limit = 10): array {
        try {
            $database = \Drupal::database();
            if (!$database->schema()->tableExists('entrepreneur_milestone')) {
                return [];
            }
            return $database->select('entrepreneur_milestone', 'm')
                ->fields('m')
                ->condition('entrepreneur_id', $profileId)
                ->orderBy('created', 'DESC')
                ->range(0, $limit)
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Loads the entrepreneur profile for a user.
     */
    protected function loadUserProfile(string $userId): mixed
    {
        $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
        $ids = $storage->getQuery()
            ->condition('user_id', $userId)
            ->range(0, 1)
            ->accessCheck(TRUE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Calculates the user level from impact points.
     */
    protected function calculateLevel(int $points): int
    {
        if ($points >= 2000) {
            return 5;
        }
        if ($points >= 1000) {
            return 4;
        }
        if ($points >= 500) {
            return 3;
        }
        if ($points >= 100) {
            return 2;
        }
        return 1;
    }

    /**
     * Gets the points threshold for the next level.
     */
    protected function getNextLevelThreshold(int $currentLevel): int
    {
        $thresholds = [1 => 100, 2 => 500, 3 => 1000, 4 => 2000, 5 => 5000];
        return $thresholds[$currentLevel] ?? 5000;
    }

}
