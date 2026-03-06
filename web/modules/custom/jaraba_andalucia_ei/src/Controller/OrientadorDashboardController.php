<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteCompletenessService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard for orientadores (career counselors) in Andalucía +ei.
 *
 * Shows assigned participants, pending service sheets,
 * session calendar, and document review queue.
 */
class OrientadorDashboardController extends ControllerBase {

  /**
   * Constructs an OrientadorDashboardController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ExpedienteCompletenessService $completenessService,
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
      $container->get('jaraba_andalucia_ei.expediente_completeness'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the orientador dashboard.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $userId = (int) $this->currentUser()->id();

    // Get mentor profile for this user.
    $mentorProfile = $this->getMentorProfile($userId);

    // Get assigned participants via mentoring sessions.
    $participants = $this->getAssignedParticipants($userId);

    // Get pending service sheets.
    $pendingSheets = $this->getPendingServiceSheets($userId);

    // Get upcoming sessions.
    $upcomingSessions = $this->getUpcomingSessions($userId);

    // Get participant completeness summaries.
    $completenessMap = [];
    foreach ($participants as $p) {
      $completenessMap[$p['id']] = $this->completenessService->getCompleteness($p['id']);
    }

    return [
      '#theme' => 'orientador_dashboard',
      '#mentor_profile' => $mentorProfile,
      '#participants' => $participants,
      '#pending_sheets' => $pendingSheets,
      '#upcoming_sessions' => $upcomingSessions,
      '#completeness_map' => $completenessMap,
      '#stats' => [
        'total_participants' => count($participants),
        'pending_signatures' => count($pendingSheets),
        'upcoming_count' => count($upcomingSessions),
      ],
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['mentoring_session_list', 'programa_participante_ei_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Gets mentor profile for the user.
   */
  protected function getMentorProfile(int $userId): ?array {
    if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
      return NULL;
    }

    try {
      $ids = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId)
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $profile = $this->entityTypeManager->getStorage('mentor_profile')->load(reset($ids));
        return $profile ? [
          'id' => (int) $profile->id(),
          'display_name' => $profile->getDisplayName(),
          'certification_level' => $profile->getCertificationLevel(),
          'total_sessions' => (int) ($profile->get('total_sessions')->value ?? 0),
          'average_rating' => $profile->getAverageRating(),
        ] : NULL;
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading mentor profile: @msg', ['@msg' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Gets participants assigned to this mentor via mentoring sessions.
   */
  protected function getAssignedParticipants(int $userId): array {
    try {
      // Find mentor_profile for this user.
      $mentorIds = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($mentorIds)) {
        return [];
      }

      // Find unique mentee IDs from sessions.
      $sessionIds = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentor_id', $mentorIds, 'IN')
        ->execute();

      $menteeUserIds = [];
      foreach ($this->entityTypeManager->getStorage('mentoring_session')->loadMultiple($sessionIds) as $session) {
        $menteeId = $session->get('mentee_id')->target_id;
        if ($menteeId) {
          $menteeUserIds[$menteeId] = $menteeId;
        }
      }

      if (empty($menteeUserIds)) {
        return [];
      }

      // Resolve to participantes.
      $participanteIds = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', array_values($menteeUserIds), 'IN')
        ->execute();

      $participants = [];
      foreach ($this->entityTypeManager->getStorage('programa_participante_ei')->loadMultiple($participanteIds) as $p) {
        $participants[] = [
          'id' => (int) $p->id(),
          'nombre' => $p->label(),
          'fase' => $p->get('fase_actual')->value ?? 'atencion',
          'carril' => $p->get('carril')->value ?? '',
        ];
      }

      return $participants;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading assigned participants: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets pending service sheets that need mentor signature.
   */
  protected function getPendingServiceSheets(int $userId): array {
    try {
      $mentorIds = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($mentorIds)) {
        return [];
      }

      $sessionIds = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentor_id', $mentorIds, 'IN')
        ->condition('status', 'completed')
        ->condition('firma_orientador_status', 'pending')
        ->sort('scheduled_start', 'DESC')
        ->range(0, 20)
        ->execute();

      $sheets = [];
      foreach ($this->entityTypeManager->getStorage('mentoring_session')->loadMultiple($sessionIds) as $session) {
        $mentee = $session->get('mentee_id')->entity;
        $sheets[] = [
          'session_id' => (int) $session->id(),
          'session_number' => (int) ($session->get('session_number')->value ?? 1),
          'mentee_name' => $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
          'scheduled_start' => $session->get('scheduled_start')->value,
          'firma_participante' => $session->get('firma_participante_status')->value ?? 'pending',
          'firma_orientador' => $session->get('firma_orientador_status')->value ?? 'pending',
        ];
      }

      return $sheets;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Gets upcoming sessions for this mentor.
   */
  protected function getUpcomingSessions(int $userId): array {
    try {
      $mentorIds = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($mentorIds)) {
        return [];
      }

      $now = (new \DateTime())->format('Y-m-d\TH:i:s');
      $sessionIds = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentor_id', $mentorIds, 'IN')
        ->condition('status', ['scheduled', 'confirmed'], 'IN')
        ->condition('scheduled_start', $now, '>')
        ->sort('scheduled_start', 'ASC')
        ->range(0, 10)
        ->execute();

      $sessions = [];
      foreach ($this->entityTypeManager->getStorage('mentoring_session')->loadMultiple($sessionIds) as $session) {
        $mentee = $session->get('mentee_id')->entity;
        $sessions[] = [
          'id' => (int) $session->id(),
          'session_number' => (int) ($session->get('session_number')->value ?? 1),
          'mentee_name' => $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
          'scheduled_start' => $session->get('scheduled_start')->value,
          'session_type' => $session->get('session_type')->value ?? 'followup',
          'meeting_url' => $session->getMeetingUrl(),
          'can_join' => $session->canJoin(),
        ];
      }

      return $sessions;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
