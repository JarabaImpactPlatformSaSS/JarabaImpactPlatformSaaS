<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_lms\Service\GamificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for gamification features.
 */
class GamificationController extends ControllerBase
{

    /**
     * Gamification service.
     *
     * @var \Drupal\jaraba_lms\Service\GamificationService
     */
    protected GamificationService $gamificationService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->gamificationService = $container->get('jaraba_lms.gamification');
        return $instance;
    }

    /**
     * Displays user's gamification profile.
     */
    public function profile(): array
    {
        $userId = (int) $this->currentUser()->id();

        // Record streak
        $this->gamificationService->recordStreak($userId);

        $stats = $this->gamificationService->getUserStats($userId);
        $progress = $this->gamificationService->getLevelProgress($userId);

        return [
            '#theme' => 'gamification_profile',
            '#stats' => $stats,
            '#progress' => $progress,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/premium-components'],
            ],
        ];
    }

    /**
     * Displays weekly leaderboard.
     */
    public function leaderboard(): array
    {
        $leaderboard = $this->gamificationService->getLeaderboard(NULL, 20);
        $currentUserId = (int) $this->currentUser()->id();

        // Find current user position
        $userPosition = NULL;
        foreach ($leaderboard as $idx => $entry) {
            if ($entry['user_id'] === $currentUserId) {
                $userPosition = $idx + 1;
                break;
            }
        }

        return [
            '#theme' => 'gamification_leaderboard',
            '#leaderboard' => $leaderboard,
            '#current_user_id' => $currentUserId,
            '#user_position' => $userPosition,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/premium-components'],
            ],
        ];
    }

    /**
     * API endpoint for gamification stats.
     */
    public function apiStats(): JsonResponse
    {
        $userId = (int) $this->currentUser()->id();

        $stats = $this->gamificationService->getUserStats($userId);
        $progress = $this->gamificationService->getLevelProgress($userId);

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'stats' => $stats,
                'progress' => $progress,
            ],
        ]);
    }

}
