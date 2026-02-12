<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_onboarding\Service\OnboardingChecklistService;
use Drupal\jaraba_onboarding\Service\OnboardingGamificationService;
use Drupal\jaraba_onboarding\Service\OnboardingOrchestratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para onboarding.
 *
 * Expone endpoints para consultar progreso, completar pasos
 * y obtener el checklist del usuario.
 */
class OnboardingApiController extends ControllerBase {

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
   * GET /api/v1/onboarding/progress
   *
   * Obtiene el progreso de onboarding del usuario actual.
   */
  public function getProgress(Request $request): JsonResponse {
    try {
      $userId = (int) $this->currentUser->id();
      $progress = $this->orchestrator->getProgress($userId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'user_id' => $userId,
          'progress' => $progress,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API error obteniendo progreso: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error obteniendo progreso de onboarding.',
      ], 500);
    }
  }

  /**
   * POST /api/v1/onboarding/progress/{progress_id}/step
   *
   * Completa un paso de onboarding.
   */
  public function completeStep(int $progress_id, Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || empty($content['step_id'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Campo requerido: step_id.',
        ], 400);
      }

      $stepId = $content['step_id'];
      $result = $this->orchestrator->completeStep($progress_id, $stepId);

      if (!$result) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No se pudo completar el paso.',
        ], 422);
      }

      // Otorgar logro por el paso completado.
      $userId = (int) $this->currentUser->id();
      $this->gamification->awardStepCompletion($userId, $stepId);

      // Obtener datos actualizados.
      $progress = $this->orchestrator->getProgress($userId);
      $achievements = $this->gamification->getAchievements($userId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'step_id' => $stepId,
          'progress' => $progress,
          'achievements' => $achievements,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API error completando paso: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error completando paso de onboarding.',
      ], 500);
    }
  }

  /**
   * GET /api/v1/onboarding/checklist
   *
   * Obtiene el checklist de onboarding del usuario actual.
   */
  public function getChecklist(Request $request): JsonResponse {
    try {
      $userId = (int) $this->currentUser->id();
      $checklistItems = $this->checklist->getChecklist($userId);
      $isComplete = $this->checklist->isChecklistComplete($userId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'user_id' => $userId,
          'items' => $checklistItems,
          'is_complete' => $isComplete,
          'total_items' => count($checklistItems),
          'completed_items' => count(array_filter($checklistItems, fn(array $item) => $item['completed'])),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API error obteniendo checklist: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error obteniendo checklist de onboarding.',
      ], 500);
    }
  }

}
