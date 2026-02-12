<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analytics de onboarding.
 *
 * Proporciona metricas de completacion, drop-off y rendimiento
 * de los flujos de onboarding por tenant y globalmente.
 */
class OnboardingAnalyticsService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene tasas de completacion de onboarding.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para datos globales.
   *
   * @return array
   *   Array con total_started, total_completed, completion_rate, avg_time_seconds.
   */
  public function getCompletionRates(?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $query = $storage->getQuery()->accessCheck(FALSE);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $allIds = $query->execute();
      $totalStarted = count($allIds);

      if ($totalStarted === 0) {
        return [
          'total_started' => 0,
          'total_completed' => 0,
          'completion_rate' => 0.0,
          'avg_time_seconds' => 0,
        ];
      }

      // Contar completados.
      $completedQuery = $storage->getQuery()->accessCheck(FALSE);
      $completedQuery->condition('progress_percentage', 100);
      if ($tenantId !== NULL) {
        $completedQuery->condition('tenant_id', $tenantId);
      }
      $completedIds = $completedQuery->execute();
      $totalCompleted = count($completedIds);

      // Calcular tiempo medio de completacion.
      $avgTimeSeconds = 0;
      if (!empty($completedIds)) {
        $completedEntities = $storage->loadMultiple($completedIds);
        $totalTime = 0;
        $counted = 0;

        /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress $progress */
        foreach ($completedEntities as $progress) {
          $startedAt = (int) $progress->get('started_at')->value;
          $completedAt = (int) $progress->get('completed_at')->value;
          if ($startedAt > 0 && $completedAt > $startedAt) {
            $totalTime += ($completedAt - $startedAt);
            $counted++;
          }
        }

        $avgTimeSeconds = $counted > 0 ? (int) round($totalTime / $counted) : 0;
      }

      return [
        'total_started' => $totalStarted,
        'total_completed' => $totalCompleted,
        'completion_rate' => $totalStarted > 0 ? round(($totalCompleted / $totalStarted) * 100, 2) : 0.0,
        'avg_time_seconds' => $avgTimeSeconds,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo tasas de completacion: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_started' => 0,
        'total_completed' => 0,
        'completion_rate' => 0.0,
        'avg_time_seconds' => 0,
      ];
    }
  }

  /**
   * Obtiene los pasos donde los usuarios abandonan el onboarding.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para datos globales.
   *
   * @return array
   *   Array de pasos con step_id, started_count, completed_count, dropoff_rate.
   */
  public function getDropoffSteps(?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('user_onboarding_progress');
      $query = $storage->getQuery()->accessCheck(FALSE);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $progresses = $storage->loadMultiple($ids);
      $stepCounts = [];

      // Contar cuantos usuarios han completado cada paso.
      /** @var \Drupal\jaraba_onboarding\Entity\UserOnboardingProgress $progress */
      foreach ($progresses as $progress) {
        $completedSteps = $progress->getCompletedSteps();
        foreach ($completedSteps as $stepId) {
          if (!isset($stepCounts[$stepId])) {
            $stepCounts[$stepId] = 0;
          }
          $stepCounts[$stepId]++;
        }
      }

      $totalUsers = count($progresses);
      $result = [];
      $previousCount = $totalUsers;

      // Ordenar por frecuencia descendente para detectar drop-off.
      arsort($stepCounts);

      foreach ($stepCounts as $stepId => $completedCount) {
        $dropoffRate = $previousCount > 0
          ? round((1 - ($completedCount / $previousCount)) * 100, 2)
          : 0.0;

        $result[] = [
          'step_id' => $stepId,
          'started_count' => $previousCount,
          'completed_count' => $completedCount,
          'dropoff_rate' => $dropoffRate,
        ];

        $previousCount = $completedCount;
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo pasos de drop-off: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
