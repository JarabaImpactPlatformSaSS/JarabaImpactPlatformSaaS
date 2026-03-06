<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard for coordinadores in Andalucía +ei.
 *
 * Provides a bird's-eye view of the program: participant stats,
 * phase distribution, mentor utilization, STO readiness,
 * and insertion rates.
 */
class CoordinadorDashboardController extends ControllerBase {

  /**
   * Constructs a CoordinadorDashboardController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the coordinador dashboard.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $tenantId = $this->resolveTenantGroupId();
    $stats = $this->buildProgramStats($tenantId);
    $phaseDistribution = $this->getPhaseDistribution($tenantId);
    $mentorUtilization = $this->getMentorUtilization($tenantId);
    $recentActivity = $this->getRecentActivity($tenantId);

    return [
      '#theme' => 'coordinador_dashboard',
      '#stats' => $stats,
      '#phase_distribution' => $phaseDistribution,
      '#mentor_utilization' => $mentorUtilization,
      '#recent_activity' => $recentActivity,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.site'],
        'tags' => ['programa_participante_ei_list', 'mentoring_session_list'],
        'max-age' => 600,
      ],
    ];
  }

  /**
   * Resolves the current tenant Group ID.
   */
  protected function resolveTenantGroupId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
    }

    return NULL;
  }

  /**
   * Adds tenant condition to an entity query (TENANT-001).
   */
  protected function addTenantCondition(mixed $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

  /**
   * Builds aggregate program statistics.
   */
  protected function buildProgramStats(?int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Total participants (TENANT-001).
      $totalQuery = $storage->getQuery()->accessCheck(TRUE);
      $this->addTenantCondition($totalQuery, $tenantId);
      $totalIds = $totalQuery->execute();

      // Active (not in baja).
      $activeQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');
      $this->addTenantCondition($activeQuery, $tenantId);
      $activeIds = $activeQuery->execute();

      // In insertion phase.
      $insertionQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'insercion');
      $this->addTenantCondition($insertionQuery, $tenantId);
      $insertionIds = $insertionQuery->execute();

      // Completed sessions (TENANT-001).
      $completedSessions = 0;
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessQuery = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'completed')
          ->count();
        $this->addTenantCondition($sessQuery, $tenantId);
        $completedSessions = (int) $sessQuery->execute();
      }

      // Pending solicitudes (TENANT-001).
      $pendingSolicitudes = 0;
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $solQuery = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'pendiente')
          ->count();
        $this->addTenantCondition($solQuery, $tenantId);
        $pendingSolicitudes = (int) $solQuery->execute();
      }

      return [
        'total_participants' => count($totalIds),
        'active_participants' => count($activeIds),
        'insertion_phase' => count($insertionIds),
        'completed_sessions' => $completedSessions,
        'pending_solicitudes' => $pendingSolicitudes,
        'insertion_rate' => count($totalIds) > 0
          ? round((count($insertionIds) / count($totalIds)) * 100, 1)
          : 0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error building program stats: @msg', ['@msg' => $e->getMessage()]);
      return [
        'total_participants' => 0,
        'active_participants' => 0,
        'insertion_phase' => 0,
        'completed_sessions' => 0,
        'pending_solicitudes' => 0,
        'insertion_rate' => 0,
      ];
    }
  }

  /**
   * Gets participant distribution by phase.
   */
  protected function getPhaseDistribution(?int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $phases = ['atencion' => 0, 'insercion' => 0, 'baja' => 0];

      foreach (array_keys($phases) as $phase) {
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('fase_actual', $phase)
          ->count();
        $this->addTenantCondition($query, $tenantId);
        $phases[$phase] = (int) $query->execute();
      }

      return $phases;
    }
    catch (\Throwable $e) {
      return ['atencion' => 0, 'insercion' => 0, 'baja' => 0];
    }
  }

  /**
   * Gets mentor utilization stats.
   */
  protected function getMentorUtilization(?int $tenantId): array {
    if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('mentor_profile');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active');
      $this->addTenantCondition($query, $tenantId);
      $activeMentors = $query->execute();

      $mentors = [];
      foreach ($storage->loadMultiple($activeMentors) as $mentor) {
        $mentors[] = [
          'id' => (int) $mentor->id(),
          'name' => $mentor->getDisplayName(),
          'total_sessions' => (int) ($mentor->get('total_sessions')->value ?? 0),
          'average_rating' => $mentor->getAverageRating(),
          'is_available' => $mentor->isAvailable(),
        ];
      }

      // Sort by total_sessions descending.
      usort($mentors, fn($a, $b) => $b['total_sessions'] <=> $a['total_sessions']);

      return array_slice($mentors, 0, 10);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Gets recent activity items.
   */
  protected function getRecentActivity(?int $tenantId): array {
    $activity = [];

    try {
      // Recent completed sessions (TENANT-001).
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessQuery = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'completed')
          ->sort('changed', 'DESC')
          ->range(0, 5);
        $this->addTenantCondition($sessQuery, $tenantId);
        $sessionIds = $sessQuery->execute();

        foreach ($this->entityTypeManager->getStorage('mentoring_session')->loadMultiple($sessionIds) as $session) {
          $mentee = $session->get('mentee_id')->entity;
          $activity[] = [
            'type' => 'session_completed',
            'label' => sprintf('Sesión #%d completada', $session->get('session_number')->value ?? 1),
            'detail' => $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
            'timestamp' => $session->get('changed')->value,
          ];
        }
      }

      // Recent phase transitions (TENANT-001).
      $partQuery = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(TRUE)
        ->sort('changed', 'DESC')
        ->range(0, 5);
      $this->addTenantCondition($partQuery, $tenantId);
      $participanteIds = $partQuery->execute();

      foreach ($this->entityTypeManager->getStorage('programa_participante_ei')->loadMultiple($participanteIds) as $p) {
        $activity[] = [
          'type' => 'participant_update',
          'label' => sprintf('%s - Fase: %s', $p->label() ?? '-', $p->get('fase_actual')->value ?? '-'),
          'detail' => $p->get('carril')->value ?? '',
          'timestamp' => $p->get('changed')->value,
        ];
      }

      // Sort by timestamp.
      usort($activity, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

      return array_slice($activity, 0, 10);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
