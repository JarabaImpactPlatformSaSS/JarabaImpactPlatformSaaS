<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
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
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
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
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
      $container->has('ecosistema_jaraba_core.daily_actions_registry')
        ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
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
    $tenantId = $this->resolveTenantGroupId();

    // Get mentor profile for this user.
    $mentorProfile = $this->getMentorProfile($userId);

    // Get assigned participants via mentoring sessions.
    $participants = $this->getAssignedParticipants($userId, $tenantId);

    // Get pending service sheets.
    $pendingSheets = $this->getPendingServiceSheets($userId, $tenantId);

    // Get upcoming sessions.
    $upcomingSessions = $this->getUpcomingSessions($userId, $tenantId);

    // Get participant completeness summaries.
    $completenessMap = [];
    foreach ($participants as $p) {
      $completenessMap[$p['id']] = $this->completenessService->getCompleteness($p['id']);
    }

    // SETUP-WIZARD-DAILY-001: Wizard + daily actions data.
    $setupWizard = NULL;
    $dailyActions = [];
    if ($this->wizardRegistry && $tenantId) {
      $setupWizard = $this->wizardRegistry->hasWizard('orientador_ei')
        ? $this->wizardRegistry->getStepsForWizard('orientador_ei', $tenantId)
        : NULL;
      $dailyActions = $this->dailyActionsRegistry?->getActionsForDashboard('orientador_ei', $tenantId) ?? [];
    }

    return [
      '#theme' => 'orientador_dashboard',
      '#mentor_profile' => $mentorProfile,
      '#participants' => $participants,
      '#pending_sheets' => $pendingSheets,
      '#upcoming_sessions' => $upcomingSessions,
      '#completeness_map' => $completenessMap,
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
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
        'contexts' => ['user', 'url.site'],
        'tags' => ['mentoring_session_list', 'programa_participante_ei_list'],
        'max-age' => 300,
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
      if ($tenant) {
        // TenantContextService returns TenantInterface.
        // tenant_id fields reference 'group'. We need TenantBridgeService
        // but the tenant ID itself is often used as group ref in this vertical.
        return (int) $tenant->id();
      }
    }
    catch (\Throwable) {
    }

    return NULL;
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
  protected function getAssignedParticipants(int $userId, ?int $tenantId): array {
    try {
      // Find mentor_profile for this user.
      $mentorQuery = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId);

      if ($tenantId) {
        $mentorQuery->condition('tenant_id', $tenantId);
      }

      $mentorIds = $mentorQuery->execute();
      if (empty($mentorIds)) {
        return [];
      }

      // Find unique mentee IDs from sessions (TENANT-001).
      $sessionQuery = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('mentor_id', $mentorIds, 'IN');

      if ($tenantId) {
        $sessionQuery->condition('tenant_id', $tenantId);
      }

      $sessionIds = $sessionQuery->execute();

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

      // Resolve to participantes (TENANT-001).
      $participanteQuery = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', array_values($menteeUserIds), 'IN');

      if ($tenantId) {
        $participanteQuery->condition('tenant_id', $tenantId);
      }

      $participanteIds = $participanteQuery->execute();

      $participants = [];
      foreach ($this->entityTypeManager->getStorage('programa_participante_ei')->loadMultiple($participanteIds) as $p) {
        $participants[] = [
          'id' => (int) $p->id(),
          'nombre' => $p->label(),
          'fase' => $p->get('fase_actual')->value ?? 'acogida',
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
  protected function getPendingServiceSheets(int $userId, ?int $tenantId): array {
    try {
      $mentorQuery = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId);

      if ($tenantId) {
        $mentorQuery->condition('tenant_id', $tenantId);
      }

      $mentorIds = $mentorQuery->execute();
      if (empty($mentorIds)) {
        return [];
      }

      $sessionQuery = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('mentor_id', $mentorIds, 'IN')
        ->condition('status', 'completed')
        ->condition('firma_orientador_status', 'pending')
        ->sort('scheduled_start', 'DESC')
        ->range(0, 20);

      if ($tenantId) {
        $sessionQuery->condition('tenant_id', $tenantId);
      }

      $sessionIds = $sessionQuery->execute();

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
  protected function getUpcomingSessions(int $userId, ?int $tenantId): array {
    try {
      $mentorQuery = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId);

      if ($tenantId) {
        $mentorQuery->condition('tenant_id', $tenantId);
      }

      $mentorIds = $mentorQuery->execute();
      if (empty($mentorIds)) {
        return [];
      }

      $now = (new \DateTime())->format('Y-m-d\TH:i:s');
      $sessionQuery = $this->entityTypeManager->getStorage('mentoring_session')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('mentor_id', $mentorIds, 'IN')
        ->condition('status', ['scheduled', 'confirmed'], 'IN')
        ->condition('scheduled_start', $now, '>')
        ->sort('scheduled_start', 'ASC')
        ->range(0, 10);

      if ($tenantId) {
        $sessionQuery->condition('tenant_id', $tenantId);
      }

      $sessionIds = $sessionQuery->execute();

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
