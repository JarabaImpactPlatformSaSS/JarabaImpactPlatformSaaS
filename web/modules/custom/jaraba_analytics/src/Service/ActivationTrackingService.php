<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Servicio de tracking de activacion de usuarios.
 *
 * Determina si un usuario ha completado los criterios de activacion
 * definidos en ActivationCriteriaConfig para su vertical.
 */
class ActivationTrackingService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Determina si un usuario esta activado en un vertical.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario a evaluar.
   * @param string $vertical
   *   Vertical canonico (VERTICAL-CANONICAL-001).
   *
   * @return bool
   *   TRUE si el usuario cumple todos los criterios de activacion.
   */
  public function isUserActivated(AccountInterface $user, string $vertical): bool {
    $progress = $this->getActivationProgress($user, $vertical);
    foreach ($progress as $criterion) {
      if (!$criterion['met']) {
        return FALSE;
      }
    }
    return $progress !== [];
  }

  /**
   * Obtiene el progreso de activacion de un usuario.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario.
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Array de criterios con su estado de cumplimiento.
   */
  public function getActivationProgress(AccountInterface $user, string $vertical): array {
    $config = $this->loadCriteriaConfig($vertical);
    if (!$config) {
      return [];
    }

    $criteria = $config->getCriteria();
    $progress = [];

    foreach ($criteria as $criterion) {
      $eventType = $criterion['event_type'] ?? '';
      $minCount = (int) ($criterion['min_count'] ?? 1);
      $withinDays = (int) ($criterion['within_days'] ?? 30);

      $count = $this->countUserEvents($user, $eventType, $withinDays);

      $progress[] = [
        'event_type' => $eventType,
        'required' => $minCount,
        'actual' => $count,
        'met' => $count >= $minCount,
        'within_days' => $withinDays,
      ];
    }

    return $progress;
  }

  /**
   * Calcula la tasa de activacion para un vertical/tenant.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Tasa de activacion (0-1).
   */
  public function calculateActivationRate(string $vertical, string $tenantId): float {
    $storage = $this->entityTypeManager->getStorage('analytics_event');

    // Contar usuarios unicos en el vertical/tenant.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId);
    $totalEvents = $query->count()->execute();

    if ($totalEvents === 0) {
      return 0.0;
    }

    // Obtener usuarios unicos del tenant via user entity.
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $userQuery = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1);
      $totalUsers = $userQuery->count()->execute();
    }
    catch (\Throwable) {
      return 0.0;
    }

    if ($totalUsers === 0) {
      return 0.0;
    }

    // Contar activados (usuarios que cumplen criterios).
    $activatedCount = 0;
    $users = $userStorage->loadMultiple(
      $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->range(0, 1000)
        ->execute()
    );

    foreach ($users as $user) {
      if ($this->isUserActivated($user, $vertical)) {
        $activatedCount++;
      }
    }

    return $totalUsers > 0 ? (float) ($activatedCount / $totalUsers) : 0.0;
  }

  /**
   * Carga la configuracion de criterios para un vertical.
   */
  protected function loadCriteriaConfig(string $vertical): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('activation_criteria');
      $entities = $storage->loadByProperties(['vertical' => $vertical]);
      return $entities !== [] ? reset($entities) : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Cuenta eventos de un usuario en un periodo.
   */
  protected function countUserEvents(AccountInterface $user, string $eventType, int $withinDays): int {
    try {
      $storage = $this->entityTypeManager->getStorage('analytics_event');
      $sinceTimestamp = strtotime("-{$withinDays} days");

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $user->id())
        ->condition('event_type', $eventType)
        ->condition('created', $sinceTimestamp, '>=');

      return $query->count()->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
