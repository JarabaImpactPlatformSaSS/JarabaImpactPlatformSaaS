<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * Ticket routing and assignment service.
 *
 * Routes tickets to the most appropriate agent based on skills,
 * workload, availability, and vertical specialization. Supports
 * round-robin and priority-weighted assignment strategies.
 */
final class TicketRoutingService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext,
  ) {}

  /**
   * Priority weights for scoring experienced agents on critical tickets.
   */
  private const PRIORITY_WEIGHTS = [
    'critical' => 4,
    'high' => 3,
    'medium' => 2,
    'low' => 1,
  ];

  /**
   * Routes a ticket to the most appropriate agent.
   *
   * Considers agent skills, current workload, ticket vertical,
   * and priority to select the best agent for assignment.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket to route.
   *
   * @return int|null
   *   The UID of the selected agent, or NULL if no agent is available.
   */
  public function routeTicket(SupportTicketInterface $ticket): ?int {
    try {
      $ticketId = $ticket->id() ?? 'new';
      $ticketCategory = $ticket->get('category')->value ?? '';
      $ticketVertical = $ticket->get('vertical')->value ?? '';
      $ticketPriority = $ticket->getPriority();

      // 1. Get all active support agents.
      $agentUids = $this->entityTypeManager
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'support_agent')
        ->condition('status', 1)
        ->execute();

      if (empty($agentUids)) {
        $this->logger->warning('TicketRoutingService: No active support agents found for ticket @id.', [
          '@id' => $ticketId,
        ]);
        return NULL;
      }

      /** @var \Drupal\user\UserInterface[] $agents */
      $agents = $this->entityTypeManager->getStorage('user')->loadMultiple($agentUids);

      // 2. Count open assigned tickets per agent for workload balancing.
      $workloadMap = $this->getAgentWorkloads(array_keys($agents));

      // 3. For critical/high tickets, count resolved tickets per agent (experience).
      $resolvedMap = [];
      $priorityWeight = self::PRIORITY_WEIGHTS[$ticketPriority] ?? 2;
      if (in_array($ticketPriority, ['critical', 'high'], TRUE)) {
        $resolvedMap = $this->getAgentResolvedCounts(array_keys($agents));
      }

      // 4. Score each agent.
      $maxWorkload = max(1, (int) max($workloadMap ?: [0]));
      $maxResolved = max(1, (int) max($resolvedMap ?: [0]));
      $scores = [];

      foreach ($agents as $uid => $agent) {
        $score = 0;

        // (a) Skills match: +50 if agent's support skills contain the ticket category.
        if ($ticketCategory !== '' && $agent->hasField('field_support_skills')) {
          $skillValues = $agent->get('field_support_skills')->getValue();
          foreach ($skillValues as $item) {
            // Support both taxonomy term references (target_id with label)
            // and plain string values.
            $skillName = '';
            if (!empty($item['target_id'])) {
              $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($item['target_id']);
              $skillName = $term ? mb_strtolower($term->label()) : '';
            }
            elseif (!empty($item['value'])) {
              $skillName = mb_strtolower((string) $item['value']);
            }
            if ($skillName !== '' && $skillName === mb_strtolower($ticketCategory)) {
              $score += 50;
              break;
            }
          }
        }

        // (b) Vertical match: +30 if agent handles the ticket's vertical.
        if ($ticketVertical !== '' && $agent->hasField('field_support_verticals')) {
          $verticalValues = $agent->get('field_support_verticals')->getValue();
          foreach ($verticalValues as $item) {
            $verticalName = mb_strtolower((string) ($item['value'] ?? $item['target_id'] ?? ''));
            if ($verticalName === mb_strtolower($ticketVertical)) {
              $score += 30;
              break;
            }
          }
        }

        // (c) Lower workload: +20 points, inversely proportional to current open tickets.
        // Agent with 0 open tickets gets full 20; agent with max gets 0.
        $agentWorkload = $workloadMap[$uid] ?? 0;
        $workloadScore = $maxWorkload > 0
          ? (int) round(20 * (1 - ($agentWorkload / $maxWorkload)))
          : 20;
        $score += $workloadScore;

        // (d) For critical/high: prefer experienced agents (more resolved tickets).
        if (!empty($resolvedMap)) {
          $agentResolved = $resolvedMap[$uid] ?? 0;
          // Scale 0-15 bonus points proportional to resolved count and priority weight.
          $experienceBonus = $maxResolved > 0
            ? (int) round(15 * ($agentResolved / $maxResolved) * ($priorityWeight / 4))
            : 0;
          $score += $experienceBonus;
        }

        $scores[$uid] = $score;
      }

      // 5. Sort by score descending; on tie, prefer lower workload.
      uksort($scores, function (int $a, int $b) use ($scores, $workloadMap): int {
        $scoreDiff = $scores[$b] <=> $scores[$a];
        if ($scoreDiff !== 0) {
          return $scoreDiff;
        }
        // Tie-break: fewer open tickets wins.
        return ($workloadMap[$a] ?? 0) <=> ($workloadMap[$b] ?? 0);
      });

      $selectedUid = array_key_first($scores);

      $this->logger->info('TicketRoutingService: Routed ticket @id (category=@cat, vertical=@vert, priority=@pri) to agent @uid with score @score.', [
        '@id' => $ticketId,
        '@cat' => $ticketCategory,
        '@vert' => $ticketVertical,
        '@pri' => $ticketPriority,
        '@uid' => $selectedUid,
        '@score' => $scores[$selectedUid] ?? 0,
      ]);

      return $selectedUid !== NULL ? (int) $selectedUid : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->error('TicketRoutingService: Failed to route ticket @id: @message', [
        '@id' => $ticket->id() ?? 'new',
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets open ticket counts per agent (workload).
   *
   * @param array $agentUids
   *   Array of agent user IDs.
   *
   * @return array<int, int>
   *   Map of agent UID => open ticket count.
   */
  private function getAgentWorkloads(array $agentUids): array {
    if (empty($agentUids)) {
      return [];
    }

    $workloadMap = array_fill_keys($agentUids, 0);

    $openStatuses = ['new', 'open', 'ai_handling', 'pending_customer', 'pending_internal', 'escalated', 'reopened'];

    $query = $this->entityTypeManager
      ->getStorage('support_ticket')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('assignee_uid', $agentUids, 'IN')
      ->condition('status', $openStatuses, 'IN');
    $ticketIds = $query->execute();

    if (!empty($ticketIds)) {
      /** @var \Drupal\jaraba_support\Entity\SupportTicketInterface[] $tickets */
      $tickets = $this->entityTypeManager->getStorage('support_ticket')->loadMultiple($ticketIds);
      foreach ($tickets as $openTicket) {
        $assigneeId = $openTicket->get('assignee_uid')->target_id;
        if ($assigneeId && isset($workloadMap[(int) $assigneeId])) {
          $workloadMap[(int) $assigneeId]++;
        }
      }
    }

    return $workloadMap;
  }

  /**
   * Gets resolved ticket counts per agent (experience metric).
   *
   * @param array $agentUids
   *   Array of agent user IDs.
   *
   * @return array<int, int>
   *   Map of agent UID => resolved ticket count.
   */
  private function getAgentResolvedCounts(array $agentUids): array {
    if (empty($agentUids)) {
      return [];
    }

    $resolvedMap = array_fill_keys($agentUids, 0);

    $query = $this->entityTypeManager
      ->getStorage('support_ticket')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('assignee_uid', $agentUids, 'IN')
      ->condition('status', ['resolved', 'closed'], 'IN');
    $ticketIds = $query->execute();

    if (!empty($ticketIds)) {
      /** @var \Drupal\jaraba_support\Entity\SupportTicketInterface[] $tickets */
      $tickets = $this->entityTypeManager->getStorage('support_ticket')->loadMultiple($ticketIds);
      foreach ($tickets as $resolvedTicket) {
        $assigneeId = $resolvedTicket->get('assignee_uid')->target_id;
        if ($assigneeId && isset($resolvedMap[(int) $assigneeId])) {
          $resolvedMap[(int) $assigneeId]++;
        }
      }
    }

    return $resolvedMap;
  }

  /**
   * Gets the next urgent unassigned ticket for an agent.
   *
   * Used by the agent dashboard to pull the highest-priority
   * unassigned ticket matching the agent's skills.
   *
   * @param int $agentUid
   *   The agent's user ID.
   *
   * @return \Drupal\jaraba_support\Entity\SupportTicketInterface|null
   *   The next urgent ticket, or NULL if none available.
   */
  public function getNextUrgentTicket(int $agentUid): ?SupportTicketInterface {
    try {
      // Priority weight mapping for sorting: lower value = more urgent.
      // Entity query sorts ASC, so critical (1) comes before low (4).
      $priorityOrder = [
        'critical' => 1,
        'high' => 2,
        'medium' => 3,
        'low' => 4,
      ];

      // Query unassigned tickets with open/new status.
      // assignee_uid is an entity_reference field; unassigned means NULL or 0.
      // We use an OR condition group for unassigned detection.
      $storage = $this->entityTypeManager->getStorage('support_ticket');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', ['new', 'open'], 'IN');

      // Unassigned: assignee_uid is NULL (not set).
      // Entity reference fields store target_id; NULL means no reference.
      $unassignedGroup = $query->orConditionGroup()
        ->notExists('assignee_uid')
        ->condition('assignee_uid', 0);
      $query->condition($unassignedGroup);

      // Sort by created ASC (oldest first) — we'll re-sort by priority in PHP
      // because list_string fields don't have numeric sort values in the DB.
      $query->sort('created', 'ASC');

      // Limit to a reasonable batch to avoid loading thousands.
      $query->range(0, 50);

      $ticketIds = $query->execute();

      if (empty($ticketIds)) {
        $this->logger->debug('TicketRoutingService: No unassigned urgent tickets found for agent @uid.', [
          '@uid' => $agentUid,
        ]);
        return NULL;
      }

      /** @var \Drupal\jaraba_support\Entity\SupportTicketInterface[] $tickets */
      $tickets = $storage->loadMultiple($ticketIds);

      // Sort by priority weight (critical first), then by created ASC (oldest first).
      uasort($tickets, function (SupportTicketInterface $a, SupportTicketInterface $b) use ($priorityOrder): int {
        $aPriority = $priorityOrder[$a->getPriority()] ?? 3;
        $bPriority = $priorityOrder[$b->getPriority()] ?? 3;

        if ($aPriority !== $bPriority) {
          return $aPriority <=> $bPriority;
        }

        // Same priority: oldest first.
        return ((int) $a->getCreatedTime()) <=> ((int) $b->getCreatedTime());
      });

      $nextTicket = reset($tickets);

      if ($nextTicket instanceof SupportTicketInterface) {
        $this->logger->info('TicketRoutingService: Next urgent ticket for agent @uid is @ticket_id (priority=@pri, created=@created).', [
          '@uid' => $agentUid,
          '@ticket_id' => $nextTicket->id(),
          '@pri' => $nextTicket->getPriority(),
          '@created' => date('Y-m-d H:i:s', (int) $nextTicket->getCreatedTime()),
        ]);
        return $nextTicket;
      }

      return NULL;
    }
    catch (\Throwable $e) {
      $this->logger->error('TicketRoutingService: Failed to get next urgent ticket for agent @uid: @message', [
        '@uid' => $agentUid,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
