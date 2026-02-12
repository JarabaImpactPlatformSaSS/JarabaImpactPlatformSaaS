<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_onboarding\Service\OnboardingChecklistService;
use Drupal\jaraba_onboarding\Service\OnboardingGamificationService;
use Drupal\jaraba_onboarding\Service\OnboardingOrchestratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del dashboard de onboarding para el frontend.
 *
 * Muestra el progreso del usuario, el checklist interactivo
 * y los logros obtenidos durante el onboarding.
 */
class OnboardingDashboardController extends ControllerBase {

  public function __construct(
    protected OnboardingOrchestratorService $orchestrator,
    protected OnboardingChecklistService $checklist,
    protected OnboardingGamificationService $gamification,
    AccountProxyInterface $currentUser,
  ) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_onboarding.orchestrator'),
      $container->get('jaraba_onboarding.checklist'),
      $container->get('jaraba_onboarding.gamification'),
      $container->get('current_user'),
    );
  }

  /**
   * Renderiza el dashboard de onboarding del usuario actual.
   *
   * @return array
   *   Render array con el template del dashboard.
   */
  public function dashboard(): array {
    $userId = (int) $this->currentUser->id();

    try {
      $progress = $this->orchestrator->getProgress($userId);
      $checklistItems = $this->checklist->getChecklist($userId);
      $achievements = $this->gamification->getAchievements($userId);

      // Calcular porcentaje global.
      $progressPercentage = 0;
      if (!empty($progress)) {
        $latestProgress = reset($progress);
        $progressPercentage = $latestProgress['progress_percentage'] ?? 0;
      }

      $build = [
        '#theme' => 'jaraba_onboarding_dashboard',
        '#user' => [
          'id' => $userId,
          'name' => $this->currentUser->getDisplayName(),
        ],
        '#progress' => $progress,
        '#checklist' => $checklistItems,
        '#achievements' => $achievements,
        '#attached' => [
          'library' => [
            'jaraba_onboarding/dashboard',
            'jaraba_onboarding/checklist',
          ],
          'drupalSettings' => [
            'jarabaOnboarding' => [
              'userId' => $userId,
              'progressPercentage' => $progressPercentage,
              'checklist' => $checklistItems,
              'achievements' => $achievements,
            ],
          ],
        ],
        '#cache' => [
          'contexts' => ['user'],
          'tags' => ['user:' . $userId],
        ],
      ];

      return $build;
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('Error renderizando dashboard de onboarding: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('No se pudo cargar el dashboard de onboarding. Por favor, intenta de nuevo mas tarde.'),
      ];
    }
  }

}
