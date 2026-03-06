<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    $stats = $this->buildProgramStats();
    $phaseDistribution = $this->getPhaseDistribution();
    $mentorUtilization = $this->getMentorUtilization();
    $recentActivity = $this->getRecentActivity();

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
        'contexts' => ['user'],
        'tags' => ['programa_participante_ei_list', 'mentoring_session_list'],
        'max-age' => 600,
      ],
    ];
  }

  /**
   * Builds aggregate program statistics.
   */
  protected function buildProgramStats(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Total participants.
      $totalIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      // Active (not in baja).
      $activeIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'baja', '!=')
        ->execute();

      // In insertion phase.
      $insertionIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'insercion')
        ->execute();

      // Completed sessions.
      $completedSessions = 0;
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $completedSessions = (int) $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'completed')
          ->count()
          ->execute();
      }

      // Pending solicitudes.
      $pendingSolicitudes = 0;
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $pendingSolicitudes = (int) $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'pendiente')
          ->count()
          ->execute();
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
  protected function getPhaseDistribution(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $phases = ['atencion' => 0, 'insercion' => 0, 'baja' => 0];

      foreach (array_keys($phases) as $phase) {
        $phases[$phase] = (int) $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('fase_actual', $phase)
          ->count()
          ->execute();
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
  protected function getMentorUtilization(): array {
    if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('mentor_profile');
      $activeMentors = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->execute();

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
  protected function getRecentActivity(): array {
    $activity = [];

    try {
      // Recent completed sessions.
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessionIds = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'completed')
          ->sort('changed', 'DESC')
          ->range(0, 5)
          ->execute();

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

      // Recent phase transitions.
      $participanteIds = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->sort('changed', 'DESC')
        ->range(0, 5)
        ->execute();

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
