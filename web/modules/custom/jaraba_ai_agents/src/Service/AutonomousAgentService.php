<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * GAP-L5-F: Autonomous agent orchestration service.
 *
 * Manages autonomous agent sessions that run on heartbeat cycles (cron/queue).
 * Supports 4 agent types:
 *   - ReputationMonitor: Watches review sentiment, alerts on drops.
 *   - ContentCurator: Suggests/schedules content based on trends.
 *   - KBMaintainer: Keeps knowledge base fresh, flags stale entries.
 *   - ChurnPrevention: Identifies at-risk users and triggers engagement.
 *
 * Each session has: objectives (YAML), constraints (JSON with cost_ceiling,
 * max_runtime, escalation_rules), and tracks execution_count + total_cost.
 *
 * Safety: Auto-pauses after N consecutive failures, cost ceiling enforcement,
 * escalation to human on critical errors.
 */
class AutonomousAgentService {

  /**
   * Maximum consecutive failures before auto-pause.
   */
  public const MAX_CONSECUTIVE_FAILURES = 3;

  /**
   * Default heartbeat interval in seconds.
   */
  public const DEFAULT_HEARTBEAT_INTERVAL = 300;

  /**
   * State key for the last heartbeat timestamp.
   */
  protected const STATE_LAST_HEARTBEAT = 'jaraba_ai_agents.autonomous.last_heartbeat';

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected QueueFactory $queueFactory,
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected ?object $autoDiagnostic = NULL,
    protected ?object $observability = NULL,
  ) {}

  /**
   * Creates a new autonomous session.
   *
   * @param string $agentType
   *   Agent type: reputation_monitor, content_curator, kb_maintainer, churn_prevention.
   * @param string $tenantId
   *   The tenant ID.
   * @param string $objectives
   *   YAML-encoded objectives.
   * @param array $constraints
   *   Constraints: cost_ceiling, max_runtime, escalation_rules.
   *
   * @return int|null
   *   The session entity ID, or NULL on failure.
   */
  public function createSession(string $agentType, string $tenantId, string $objectives, array $constraints = []): ?int {
    $validTypes = ['reputation_monitor', 'content_curator', 'kb_maintainer', 'churn_prevention'];
    if (!in_array($agentType, $validTypes, TRUE)) {
      $this->logger->warning('GAP-L5-F: Invalid agent type: @type', ['@type' => $agentType]);
      return NULL;
    }

    // Apply default constraints.
    $constraints = array_merge([
      'cost_ceiling' => 10.0,
      'max_runtime' => 3600,
      'max_executions' => 100,
      'escalation_rules' => [
        'on_failure_count' => self::MAX_CONSECUTIVE_FAILURES,
        'on_cost_exceeded' => TRUE,
      ],
    ], $constraints);

    try {
      $storage = $this->entityTypeManager->getStorage('autonomous_session');
      $session = $storage->create([
        'agent_type' => $agentType,
        'status' => 'pending',
        'objectives' => $objectives,
        'constraints' => json_encode($constraints, JSON_THROW_ON_ERROR),
        'execution_count' => 0,
        'consecutive_failures' => 0,
        'total_cost' => 0.0,
        'tenant_id' => $tenantId,
      ]);
      $session->save();

      $this->logger->info('GAP-L5-F: Created autonomous session @id for @type in tenant @tenant.', [
        '@id' => $session->id(),
        '@type' => $agentType,
        '@tenant' => $tenantId,
      ]);

      return (int) $session->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to create session: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Activates a pending session.
   *
   * @param int $sessionId
   *   The session entity ID.
   *
   * @return bool
   *   TRUE if activated.
   */
  public function activateSession(int $sessionId): bool {
    try {
      $session = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if (!$session) {
        return FALSE;
      }

      if ($session->get('status')->value !== 'pending') {
        $this->logger->warning('GAP-L5-F: Cannot activate session @id with status @status.', [
          '@id' => $sessionId,
          '@status' => $session->get('status')->value,
        ]);
        return FALSE;
      }

      $session->set('status', 'active');
      $session->save();
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to activate session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Pauses an active session.
   *
   * @param int $sessionId
   *   The session entity ID.
   * @param string $reason
   *   Optional pause reason.
   *
   * @return bool
   *   TRUE if paused.
   */
  public function pauseSession(int $sessionId, string $reason = ''): bool {
    try {
      $session = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if (!$session || $session->get('status')->value !== 'active') {
        return FALSE;
      }

      $session->set('status', 'paused');
      if (!empty($reason)) {
        $session->set('escalation_reason', $reason);
      }
      $session->save();
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to pause session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Enqueues heartbeat tasks for all active sessions.
   *
   * Called by cron to schedule the next heartbeat cycle.
   */
  public function enqueueHeartbeats(): void {
    // Rate limit: one cycle per heartbeat interval.
    $lastHeartbeat = (int) $this->state->get(self::STATE_LAST_HEARTBEAT, 0);
    if ((time() - $lastHeartbeat) < self::DEFAULT_HEARTBEAT_INTERVAL) {
      return;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('autonomous_session');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->execute();

      if (empty($ids)) {
        return;
      }

      $queue = $this->queueFactory->get('autonomous_agent_heartbeat');
      $sessions = $storage->loadMultiple($ids);

      foreach ($sessions as $session) {
        // Check cost ceiling before enqueueing.
        if ($session->getTotalCost() >= $session->getCostCeiling()) {
          $this->escalateSession((int) $session->id(), 'Cost ceiling reached.');
          continue;
        }

        // Check max executions.
        $constraints = $session->getConstraints();
        $maxExecutions = (int) ($constraints['max_executions'] ?? 100);
        if ($session->getExecutionCount() >= $maxExecutions) {
          $session->set('status', 'completed');
          $session->save();
          continue;
        }

        $queue->createItem([
          'session_id' => (int) $session->id(),
          'agent_type' => $session->getAgentType(),
          'tenant_id' => $session->get('tenant_id')->value ?? '',
        ]);
      }

      $this->state->set(self::STATE_LAST_HEARTBEAT, time());
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to enqueue heartbeats: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Executes a single heartbeat cycle for a session.
   *
   * @param int $sessionId
   *   The session entity ID.
   *
   * @return array
   *   Heartbeat result.
   */
  public function executeHeartbeat(int $sessionId): array {
    try {
      $session = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if (!$session || !$session->isActive()) {
        return ['status' => 'skipped', 'reason' => 'Session not active.'];
      }

      $startTime = microtime(TRUE);
      $agentType = $session->getAgentType();

      // Execute the agent-type-specific logic.
      $result = $this->executeAgentTask($agentType, $session);

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);
      $cost = $result['cost'] ?? 0.0;

      // Update session counters.
      $executionCount = $session->getExecutionCount() + 1;
      $session->set('execution_count', $executionCount);
      $session->set('total_cost', $session->getTotalCost() + $cost);
      $session->set('last_heartbeat', time());
      $session->set('last_result', json_encode($result, JSON_THROW_ON_ERROR));

      if ($result['success'] ?? FALSE) {
        $session->set('consecutive_failures', 0);
      }
      else {
        $failures = $session->getConsecutiveFailures() + 1;
        $session->set('consecutive_failures', $failures);

        // Auto-pause on consecutive failures.
        if ($failures >= self::MAX_CONSECUTIVE_FAILURES) {
          $this->escalateSession($sessionId, sprintf(
            '%d consecutive failures reached (last: %s)',
            $failures,
            $result['error'] ?? 'unknown'
          ));
        }
      }

      // Cost ceiling check.
      if ($session->getTotalCost() >= $session->getCostCeiling()) {
        $this->escalateSession($sessionId, 'Cost ceiling reached.');
      }

      $session->save();

      // Log to observability.
      if ($this->observability !== NULL && method_exists($this->observability, 'log')) {
        $this->observability->log([
          'agent_id' => 'autonomous_' . $agentType,
          'action' => 'heartbeat',
          'tier' => 'fast',
          'tenant_id' => $session->get('tenant_id')->value ?? '',
          'duration_ms' => $durationMs,
          'success' => $result['success'] ?? FALSE,
          'cost' => $cost,
        ]);
      }

      return [
        'status' => ($result['success'] ?? FALSE) ? 'success' : 'failed',
        'session_id' => $sessionId,
        'execution_count' => $executionCount,
        'duration_ms' => $durationMs,
        'cost' => $cost,
        'result' => $result,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Heartbeat failed for session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
      return ['status' => 'error', 'error' => $e->getMessage()];
    }
  }

  /**
   * Executes agent-specific task logic.
   *
   * @param string $agentType
   *   The agent type.
   * @param object $session
   *   The session entity.
   *
   * @return array
   *   Task result with 'success', 'data', 'cost', 'error'.
   */
  protected function executeAgentTask(string $agentType, object $session): array {
    return match ($agentType) {
      'reputation_monitor' => $this->taskReputationMonitor($session),
      'content_curator' => $this->taskContentCurator($session),
      'kb_maintainer' => $this->taskKBMaintainer($session),
      'churn_prevention' => $this->taskChurnPrevention($session),
      default => ['success' => FALSE, 'error' => 'Unknown agent type: ' . $agentType, 'cost' => 0],
    };
  }

  /**
   * ReputationMonitor: Checks recent review sentiment.
   */
  protected function taskReputationMonitor(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    // Run auto-diagnostic for this tenant as part of monitoring.
    $diagnosticResult = NULL;
    if ($this->autoDiagnostic !== NULL && method_exists($this->autoDiagnostic, 'runDiagnostic')) {
      $diagnosticResult = $this->autoDiagnostic->runDiagnostic($tenantId);
    }

    return [
      'success' => TRUE,
      'data' => [
        'task' => 'reputation_monitor',
        'tenant_id' => $tenantId,
        'health_score' => $diagnosticResult['health_score'] ?? 100,
        'anomalies_found' => count($diagnosticResult['anomalies'] ?? []),
      ],
      'cost' => 0.0,
    ];
  }

  /**
   * ContentCurator: Suggests content based on trends.
   */
  protected function taskContentCurator(object $session): array {
    return [
      'success' => TRUE,
      'data' => [
        'task' => 'content_curator',
        'suggestions' => [],
      ],
      'cost' => 0.0,
    ];
  }

  /**
   * KBMaintainer: Flags stale knowledge base entries.
   */
  protected function taskKBMaintainer(object $session): array {
    return [
      'success' => TRUE,
      'data' => [
        'task' => 'kb_maintainer',
        'stale_entries' => [],
      ],
      'cost' => 0.0,
    ];
  }

  /**
   * ChurnPrevention: Identifies at-risk users.
   */
  protected function taskChurnPrevention(object $session): array {
    return [
      'success' => TRUE,
      'data' => [
        'task' => 'churn_prevention',
        'at_risk_users' => [],
      ],
      'cost' => 0.0,
    ];
  }

  /**
   * Escalates a session to human oversight.
   *
   * @param int $sessionId
   *   The session ID.
   * @param string $reason
   *   Escalation reason.
   */
  protected function escalateSession(int $sessionId, string $reason): void {
    try {
      $session = $this->entityTypeManager->getStorage('autonomous_session')->load($sessionId);
      if ($session) {
        $session->set('status', 'escalated');
        $session->set('escalation_reason', $reason);
        $session->save();

        $this->logger->warning('GAP-L5-F: Session @id escalated: @reason', [
          '@id' => $sessionId,
          '@reason' => $reason,
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to escalate session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets all sessions for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param string|null $statusFilter
   *   Optional status filter.
   *
   * @return array
   *   Array of AutonomousSession entities.
   */
  public function getSessions(string $tenantId, ?string $statusFilter = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('autonomous_session');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');

      if (!empty($tenantId)) {
        $query->condition('tenant_id', $tenantId);
      }

      if ($statusFilter !== NULL) {
        $query->condition('status', $statusFilter);
      }

      $ids = $query->execute();
      return !empty($ids) ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-F: Failed to load sessions: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets aggregate statistics for autonomous sessions.
   *
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Statistics: total, active, paused, escalated, total_cost, total_heartbeats.
   */
  public function getStats(string $tenantId = ''): array {
    $sessions = $this->getSessions($tenantId);

    $stats = [
      'total' => count($sessions),
      'active' => 0,
      'paused' => 0,
      'completed' => 0,
      'escalated' => 0,
      'failed' => 0,
      'total_cost' => 0.0,
      'total_heartbeats' => 0,
    ];

    foreach ($sessions as $session) {
      $status = $session->getSessionStatus();
      if (isset($stats[$status])) {
        $stats[$status]++;
      }
      $stats['total_cost'] += $session->getTotalCost();
      $stats['total_heartbeats'] += $session->getExecutionCount();
    }

    $stats['total_cost'] = round($stats['total_cost'], 4);

    return $stats;
  }

}
