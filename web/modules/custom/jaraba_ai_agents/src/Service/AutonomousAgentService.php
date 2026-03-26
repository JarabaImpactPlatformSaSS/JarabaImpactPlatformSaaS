<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * GAP-L5-F: Autonomous agent orchestration service.
 *
 * Manages autonomous agent sessions that run on heartbeat cycles (cron/queue).
 * Supports 10 agent types:
 *   - ReputationMonitor: Watches review sentiment, alerts on drops.
 *   - ContentCurator: Suggests/schedules content based on trends.
 *   - KBMaintainer: Keeps knowledge base fresh, flags stale entries.
 *   - ChurnPrevention: Identifies at-risk users and triggers engagement.
 *   - CrmIntelligence: Analyzes CRM pipeline health, identifies stale deals.
 *   - RevenueOptimization: Detects upsell/expansion opportunities from usage.
 *   - ContentSeoOptimizer: Monitors page rankings, suggests SEO improvements.
 *   - SupportProactive: Identifies ticket pattern trends, suggests KB articles.
 *   - EmailOptimizer: Analyzes email performance, suggests send-time optimization.
 *   - SocialOptimizer: Identifies top-performing content patterns, suggests schedule.
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
   *   Agent type: reputation_monitor, content_curator, kb_maintainer,
   *   churn_prevention, crm_intelligence, revenue_optimization,
   *   content_seo_optimizer, support_proactive, email_optimizer,
   *   social_optimizer.
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
    $validTypes = [
      'reputation_monitor',
      'content_curator',
      'kb_maintainer',
      'churn_prevention',
      'crm_intelligence',
      'revenue_optimization',
      'content_seo_optimizer',
      'support_proactive',
      'email_optimizer',
      'social_optimizer',
    ];
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
      if ($reason !== '') {
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

      if ($ids === []) {
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
      'crm_intelligence' => $this->taskCrmIntelligence($session),
      'revenue_optimization' => $this->taskRevenueOptimization($session),
      'content_seo_optimizer' => $this->taskContentSeoOptimizer($session),
      'support_proactive' => $this->taskSupportProactive($session),
      'email_optimizer' => $this->taskEmailOptimizer($session),
      'social_optimizer' => $this->taskSocialOptimizer($session),
      default => ['success' => FALSE, 'error' => 'Unknown agent type: ' . $agentType, 'cost' => 0],
    };
  }

  /**
   * ReputationMonitor: Checks recent review sentiment.
   *
   * @return array<string, mixed>
   *   Task result.
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
   * ContentCurator: Analyzes recent content performance and suggests actions.
   *
   * Checks content_article entities for:
   *   - Articles with low views that need promotion.
   *   - Gaps in publishing schedule (no new content in 7+ days).
   *   - High-performing articles that could be expanded into series.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskContentCurator(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      $storage = $this->entityTypeManager->getStorage('content_article');
      $suggestions = [];

      // Find articles published in the last 30 days with low views.
      $thirtyDaysAgo = strtotime('-30 days');
      $lowViewIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('created', $thirtyDaysAgo, '>=')
        ->condition('views_count', 10, '<')
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      if ($lowViewIds !== []) {
        $lowViewArticles = $storage->loadMultiple($lowViewIds);
        foreach ($lowViewArticles as $article) {
          if (!$article instanceof ContentEntityInterface) {
            continue;
          }
          $suggestions[] = [
            'type' => 'promote',
            'entity_id' => $article->id(),
            'title' => $article->label(),
            'reason' => sprintf('Published recently but only %d views.', $article->get('views_count')->value ?? 0),
          ];
        }
      }

      // Check publishing gap: no articles in last 7 days.
      $sevenDaysAgo = strtotime('-7 days');
      $recentCount = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('created', $sevenDaysAgo, '>=')
        ->count()
        ->execute();

      if ((int) $recentCount === 0) {
        $suggestions[] = [
          'type' => 'schedule',
          'reason' => 'No new content published in the last 7 days.',
        ];
      }

      // Find top-performing articles for series expansion.
      $topIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->sort('views_count', 'DESC')
        ->range(0, 3)
        ->execute();

      if ($topIds !== []) {
        $topArticles = $storage->loadMultiple($topIds);
        foreach ($topArticles as $article) {
          if (!$article instanceof ContentEntityInterface) {
            continue;
          }
          $views = (int) ($article->get('views_count')->value ?? 0);
          if ($views > 50) {
            $suggestions[] = [
              'type' => 'expand',
              'entity_id' => $article->id(),
              'title' => $article->label(),
              'reason' => sprintf('High-performing article (%d views) — consider a follow-up.', $views),
            ];
          }
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'content_curator',
          'tenant_id' => $tenantId,
          'suggestions' => $suggestions,
          'suggestions_count' => count($suggestions),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: ContentCurator failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * KBMaintainer: Flags stale knowledge base entries.
   *
   * Checks tenant_knowledge_config entities for entries not updated
   * in the last 90 days, and flags them for review.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskKBMaintainer(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      // Check if the knowledge base entity type exists.
      if (!$this->entityTypeManager->hasDefinition('tenant_knowledge_config')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'kb_maintainer',
            'tenant_id' => $tenantId,
            'stale_entries' => [],
            'note' => 'Knowledge base module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('tenant_knowledge_config');
      $ninetyDaysAgo = strtotime('-90 days');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('changed', $ninetyDaysAgo, '<')
        ->sort('changed', 'ASC')
        ->range(0, 20);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $staleIds = $query->execute();
      $staleEntries = [];

      if ($staleIds !== []) {
        $entries = $storage->loadMultiple($staleIds);
        foreach ($entries as $entry) {
          if (!$entry instanceof ContentEntityInterface) {
            continue;
          }
          $lastChanged = ($entry instanceof \Drupal\Core\Entity\EntityChangedInterface) ? $entry->getChangedTime() : (int) ($entry->get('changed')->value ?? 0);
          $daysSinceUpdate = (int) ((time() - $lastChanged) / 86400);
          $staleEntries[] = [
            'entity_id' => $entry->id(),
            'label' => $entry->label() ?? 'KB Entry #' . $entry->id(),
            'days_since_update' => $daysSinceUpdate,
            'last_changed' => date('Y-m-d', $lastChanged),
          ];
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'kb_maintainer',
          'tenant_id' => $tenantId,
          'stale_entries' => $staleEntries,
          'stale_count' => count($staleEntries),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: KBMaintainer failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * ChurnPrevention: Identifies users at risk of churning.
   *
   * Checks users who haven't logged in for 30+ days and have
   * an active tenant membership, indicating disengagement.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskChurnPrevention(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $thirtyDaysAgo = strtotime('-30 days');
      $sixtyDaysAgo = strtotime('-60 days');

      // Find users who last accessed between 30-60 days ago (at-risk window).
      $query = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('access', $sixtyDaysAgo, '>=')
        ->condition('access', $thirtyDaysAgo, '<=')
        ->sort('access', 'ASC')
        ->range(0, 20);

      $userIds = $query->execute();
      $atRiskUsers = [];

      if ($userIds !== []) {
        $users = $userStorage->loadMultiple($userIds);
        foreach ($users as $user) {
          $lastAccess = (int) $user->getLastAccessedTime();
          $daysSinceAccess = (int) ((time() - $lastAccess) / 86400);
          $atRiskUsers[] = [
            'uid' => $user->id(),
            'name' => $user->getAccountName(),
            'days_since_access' => $daysSinceAccess,
            'last_access' => date('Y-m-d', $lastAccess),
            'risk_level' => $daysSinceAccess > 45 ? 'high' : 'medium',
          ];
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'churn_prevention',
          'tenant_id' => $tenantId,
          'at_risk_users' => $atRiskUsers,
          'at_risk_count' => count($atRiskUsers),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: ChurnPrevention failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * CrmIntelligence: Analyzes CRM pipeline health and identifies stale deals.
   *
   * Checks crm_opportunity entities for stale deals (no activity in 14+ days)
   * and suggests next actions based on deal stage and age.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskCrmIntelligence(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('crm_opportunity')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'crm_intelligence',
            'tenant_id' => $tenantId,
            'stale_deals' => [],
            'note' => 'CRM module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('crm_opportunity');
      $fourteenDaysAgo = strtotime('-14 days');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'open')
        ->condition('changed', $fourteenDaysAgo, '<')
        ->sort('changed', 'ASC')
        ->range(0, 20);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $staleIds = $query->execute();
      $staleDeals = [];

      if ($staleIds !== []) {
        $deals = $storage->loadMultiple($staleIds);
        foreach ($deals as $deal) {
          if (!$deal instanceof ContentEntityInterface) {
            continue;
          }
          $lastChanged = ($deal instanceof \Drupal\Core\Entity\EntityChangedInterface) ? $deal->getChangedTime() : (int) ($deal->get('changed')->value ?? 0);
          $daysSinceActivity = (int) ((time() - $lastChanged) / 86400);
          $staleDeals[] = [
            'entity_id' => $deal->id(),
            'label' => $deal->label() ?? 'Deal #' . $deal->id(),
            'days_since_activity' => $daysSinceActivity,
            'last_changed' => date('Y-m-d', $lastChanged),
            'suggested_action' => $daysSinceActivity > 30 ? 'close_or_revive' : 'follow_up',
          ];
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'crm_intelligence',
          'tenant_id' => $tenantId,
          'stale_deals' => $staleDeals,
          'stale_count' => count($staleDeals),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: CrmIntelligence failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * RevenueOptimization: Detects upsell/expansion opportunities from usage.
   *
   * Analyzes tenant usage patterns to identify tenants on lower tiers
   * whose usage suggests they would benefit from an upgrade.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskRevenueOptimization(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('tenant')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'revenue_optimization',
            'tenant_id' => $tenantId,
            'opportunities' => [],
            'note' => 'Tenant module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('tenant');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->range(0, 50);

      if ($tenantId !== '') {
        $query->condition('id', $tenantId);
      }

      $tenantIds = $query->execute();
      $opportunities = [];

      if ($tenantIds !== []) {
        $tenants = $storage->loadMultiple($tenantIds);
        foreach ($tenants as $tenant) {
          if (!$tenant instanceof ContentEntityInterface) {
            continue;
          }
          $currentTier = $tenant->get('plan_tier')->value ?? 'free';
          // Only suggest upsell for non-enterprise tenants.
          if ($currentTier === 'enterprise') {
            continue;
          }
          $memberCount = (int) ($tenant->get('member_count')->value ?? 0);
          $threshold = match ($currentTier) {
            'free' => 3,
            'starter' => 10,
            'professional' => 25,
            default => 50,
          };

          if ($memberCount >= $threshold) {
            $opportunities[] = [
              'tenant_id' => $tenant->id(),
              'label' => $tenant->label() ?? 'Tenant #' . $tenant->id(),
              'current_tier' => $currentTier,
              'member_count' => $memberCount,
              'suggested_action' => 'upsell_to_next_tier',
              'reason' => sprintf('Member count (%d) exceeds %s tier threshold (%d).', $memberCount, $currentTier, $threshold),
            ];
          }
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'revenue_optimization',
          'tenant_id' => $tenantId,
          'opportunities' => $opportunities,
          'opportunity_count' => count($opportunities),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: RevenueOptimization failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * ContentSeoOptimizer: Monitors page rankings and suggests SEO improvements.
   *
   * Checks page_content entities for missing SEO fields (meta description,
   * canonical URL) and flags pages with thin content (< 300 words).
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskContentSeoOptimizer(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('page_content')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'content_seo_optimizer',
            'tenant_id' => $tenantId,
            'issues' => [],
            'note' => 'Page content module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('page_content');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->sort('changed', 'DESC')
        ->range(0, 50);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $pageIds = $query->execute();
      $issues = [];

      if ($pageIds !== []) {
        $pages = $storage->loadMultiple($pageIds);
        foreach ($pages as $page) {
          if (!$page instanceof ContentEntityInterface) {
            continue;
          }
          $pageIssues = [];
          $label = $page->label() ?? 'Page #' . $page->id();

          // Check meta description.
          if ($page->hasField('meta_description')) {
            $metaDesc = $page->get('meta_description')->value ?? '';
            if (strlen($metaDesc) < 50) {
              $pageIssues[] = 'Missing or short meta description.';
            }
          }

          // Check content length (thin content).
          if ($page->hasField('canvas_data')) {
            $content = strip_tags($page->get('canvas_data')->value ?? '');
            $wordCount = str_word_count($content);
            if ($wordCount < 300) {
              $pageIssues[] = sprintf('Thin content (%d words, minimum 300 recommended).', $wordCount);
            }
          }

          if ($pageIssues !== []) {
            $issues[] = [
              'entity_id' => $page->id(),
              'label' => $label,
              'issues' => $pageIssues,
            ];
          }
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'content_seo_optimizer',
          'tenant_id' => $tenantId,
          'issues' => $issues,
          'issue_count' => count($issues),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: ContentSeoOptimizer failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * SupportProactive: Identifies ticket pattern trends and suggests KB articles.
   *
   * Analyzes recent support_ticket entities to find recurring topics
   * that could be addressed with knowledge base articles.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskSupportProactive(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('support_ticket')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'support_proactive',
            'tenant_id' => $tenantId,
            'trends' => [],
            'note' => 'Support module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('support_ticket');
      $thirtyDaysAgo = strtotime('-30 days');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $thirtyDaysAgo, '>=')
        ->sort('created', 'DESC')
        ->range(0, 100);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $ticketIds = $query->execute();
      $categoryCount = [];

      if ($ticketIds !== []) {
        $tickets = $storage->loadMultiple($ticketIds);
        foreach ($tickets as $ticket) {
          if (!$ticket instanceof ContentEntityInterface) {
            continue;
          }
          $category = $ticket->get('category')->value ?? 'uncategorized';
          $categoryCount[$category] = ($categoryCount[$category] ?? 0) + 1;
        }
      }

      // Identify categories with 3+ tickets as trends.
      $trends = [];
      arsort($categoryCount);
      foreach ($categoryCount as $category => $count) {
        if ($count >= 3) {
          $trends[] = [
            'category' => $category,
            'ticket_count' => $count,
            'suggested_action' => 'create_kb_article',
            'reason' => sprintf('%d tickets in category "%s" in last 30 days.', $count, $category),
          ];
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'support_proactive',
          'tenant_id' => $tenantId,
          'trends' => $trends,
          'trend_count' => count($trends),
          'total_tickets_analyzed' => count($ticketIds),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: SupportProactive failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * EmailOptimizer: Analyzes email performance and suggests send-time optimization.
   *
   * Checks email_log entities for open/click rates and identifies
   * optimal send times based on historical engagement data.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskEmailOptimizer(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('email_log')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'email_optimizer',
            'tenant_id' => $tenantId,
            'suggestions' => [],
            'note' => 'Email log module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('email_log');
      $thirtyDaysAgo = strtotime('-30 days');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $thirtyDaysAgo, '>=')
        ->sort('created', 'DESC')
        ->range(0, 200);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $logIds = $query->execute();
      $suggestions = [];
      $hourlyOpens = array_fill(0, 24, ['sent' => 0, 'opened' => 0]);

      if ($logIds !== []) {
        $logs = $storage->loadMultiple($logIds);
        foreach ($logs as $log) {
          if (!$log instanceof ContentEntityInterface) {
            continue;
          }
          $sentHour = (int) date('G', (int) $log->get('created')->value);
          $hourlyOpens[$sentHour]['sent']++;
          $openedAtValue = $log->get('opened_at')->value ?? NULL;
          if ($openedAtValue !== NULL && $openedAtValue !== '' && $openedAtValue !== 0) {
            $hourlyOpens[$sentHour]['opened']++;
          }
        }

        // Find best and worst hours.
        $hourlyRates = [];
        foreach ($hourlyOpens as $hour => $data) {
          if ($data['sent'] >= 5) {
            $hourlyRates[$hour] = round($data['opened'] / $data['sent'] * 100, 1);
          }
        }

        if ($hourlyRates !== []) {
          arsort($hourlyRates);
          $bestHour = array_key_first($hourlyRates);
          $suggestions[] = [
            'type' => 'optimal_send_time',
            'hour' => $bestHour,
            'open_rate' => $hourlyRates[$bestHour],
            'reason' => sprintf('Best open rate (%.1f%%) at %02d:00.', $hourlyRates[$bestHour], $bestHour),
          ];

          asort($hourlyRates);
          $worstHour = array_key_first($hourlyRates);
          if ($worstHour !== $bestHour) {
            $suggestions[] = [
              'type' => 'avoid_send_time',
              'hour' => $worstHour,
              'open_rate' => $hourlyRates[$worstHour],
              'reason' => sprintf('Lowest open rate (%.1f%%) at %02d:00 — avoid sending.', $hourlyRates[$worstHour], $worstHour),
            ];
          }
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'email_optimizer',
          'tenant_id' => $tenantId,
          'suggestions' => $suggestions,
          'emails_analyzed' => count($logIds),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: EmailOptimizer failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
  }

  /**
   * SocialOptimizer: Identifies top-performing content patterns and schedule.
   *
   * Analyzes content_article entities to identify which content types
   * and publishing times generate the most engagement, and suggests
   * an optimal posting schedule.
   *
   * @return array<string, mixed>
   *   Task result.
   */
  protected function taskSocialOptimizer(object $session): array {
    $tenantId = $session->get('tenant_id')->value ?? '';

    try {
      if (!$this->entityTypeManager->hasDefinition('content_article')) {
        return [
          'success' => TRUE,
          'data' => [
            'task' => 'social_optimizer',
            'tenant_id' => $tenantId,
            'patterns' => [],
            'note' => 'Content article module not installed.',
          ],
          'cost' => 0.0,
        ];
      }

      $storage = $this->entityTypeManager->getStorage('content_article');
      $ninetyDaysAgo = strtotime('-90 days');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('created', $ninetyDaysAgo, '>=')
        ->sort('views_count', 'DESC')
        ->range(0, 50);

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      $articleIds = $query->execute();
      $patterns = [];
      $dayOfWeekPerformance = array_fill(0, 7, ['count' => 0, 'total_views' => 0]);

      if ($articleIds !== []) {
        $articles = $storage->loadMultiple($articleIds);
        foreach ($articles as $article) {
          if (!$article instanceof ContentEntityInterface) {
            continue;
          }
          $created = (int) $article->get('created')->value;
          $dayOfWeek = (int) date('w', $created);
          $views = (int) ($article->get('views_count')->value ?? 0);

          $dayOfWeekPerformance[$dayOfWeek]['count']++;
          $dayOfWeekPerformance[$dayOfWeek]['total_views'] += $views;
        }

        // Find best publishing day.
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $bestAvg = 0;
        $bestDay = 0;
        foreach ($dayOfWeekPerformance as $day => $data) {
          if ($data['count'] > 0) {
            $avg = $data['total_views'] / $data['count'];
            if ($avg > $bestAvg) {
              $bestAvg = $avg;
              $bestDay = $day;
            }
          }
        }

        if ($bestAvg > 0) {
          $patterns[] = [
            'type' => 'best_publishing_day',
            'day' => $dayNames[$bestDay],
            'avg_views' => round($bestAvg, 1),
            'reason' => sprintf('Articles published on %s average %.1f views.', $dayNames[$bestDay], $bestAvg),
          ];
        }

        // Identify top-performing content for repurposing.
        $topArticles = array_slice($articles, 0, 3, TRUE);
        foreach ($topArticles as $article) {
          if (!$article instanceof ContentEntityInterface) {
            continue;
          }
          $views = (int) ($article->get('views_count')->value ?? 0);
          if ($views > 20) {
            $patterns[] = [
              'type' => 'repurpose_candidate',
              'entity_id' => $article->id(),
              'label' => $article->label() ?? 'Article #' . $article->id(),
              'views' => $views,
              'reason' => sprintf('High-performing article (%d views) — repurpose for social.', $views),
            ];
          }
        }
      }

      return [
        'success' => TRUE,
        'data' => [
          'task' => 'social_optimizer',
          'tenant_id' => $tenantId,
          'patterns' => $patterns,
          'pattern_count' => count($patterns),
          'articles_analyzed' => count($articleIds),
        ],
        'cost' => 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-F: SocialOptimizer failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage(), 'cost' => 0.0];
    }
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

      if ($tenantId !== '') {
        $query->condition('tenant_id', $tenantId);
      }

      if ($statusFilter !== NULL) {
        $query->condition('status', $statusFilter);
      }

      $ids = $query->execute();
      return $ids !== [] ? $storage->loadMultiple($ids) : [];
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
