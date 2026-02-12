<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gamificacion y recompensas de onboarding.
 *
 * Gestiona la asignacion de logros y recompensas cuando
 * los usuarios completan pasos del flujo de onboarding.
 */
class OnboardingGamificationService {

  /**
   * Mapeo de pasos a logros.
   *
   * @var array<string, array{label: string, points: int}>
   */
  protected const STEP_ACHIEVEMENTS = [
    'profile_complete' => ['label' => 'Perfil Completo', 'points' => 10],
    'first_login' => ['label' => 'Primer Inicio de Sesion', 'points' => 5],
    'tour_complete' => ['label' => 'Tour Completado', 'points' => 15],
    'first_action' => ['label' => 'Primera Accion', 'points' => 20],
    'team_invite' => ['label' => 'Equipo Invitado', 'points' => 25],
    'integration_setup' => ['label' => 'Integracion Configurada', 'points' => 30],
    'onboarding_complete' => ['label' => 'Onboarding Completo', 'points' => 50],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Otorga recompensa por completar un paso de onboarding.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $stepId
   *   Identificador del paso completado.
   */
  public function awardStepCompletion(int $userId, string $stepId): void {
    try {
      $achievement = self::STEP_ACHIEVEMENTS[$stepId] ?? NULL;

      if (!$achievement) {
        $this->logger->info('No hay logro definido para el paso @step.', ['@step' => $stepId]);
        return;
      }

      // Verificar si el logro ya fue otorgado.
      $existing = $this->getAchievements($userId);
      foreach ($existing as $existing_achievement) {
        if (($existing_achievement['step_id'] ?? '') === $stepId) {
          return;
        }
      }

      $this->logger->info('Logro "@label" (+@points pts) otorgado al usuario @user por paso @step.', [
        '@label' => $achievement['label'],
        '@points' => $achievement['points'],
        '@user' => $userId,
        '@step' => $stepId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error otorgando logro para usuario @user, paso @step: @error', [
        '@user' => $userId,
        '@step' => $stepId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene los logros de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array de logros con label, points, step_id.
   */
  public function getAchievements(int $userId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $progresses = $storage->loadMultiple($ids);
      $achievements = [];

      /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress $progress */
      foreach ($progresses as $progress) {
        $completedSteps = $progress->getCompletedSteps();
        foreach ($completedSteps as $stepId) {
          if (isset(self::STEP_ACHIEVEMENTS[$stepId])) {
            $achievements[] = [
              'step_id' => $stepId,
              'label' => self::STEP_ACHIEVEMENTS[$stepId]['label'],
              'points' => self::STEP_ACHIEVEMENTS[$stepId]['points'],
            ];
          }
        }
      }

      return $achievements;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo logros para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
