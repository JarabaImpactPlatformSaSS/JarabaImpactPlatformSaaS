<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * Support health score service.
 *
 * Calculates a composite health score (0-100) for a tenant's support
 * experience based on ticket volume trends, SLA compliance, CSAT scores,
 * and escalation rates. Triggers churn alerts when the score drops
 * below critical thresholds.
 */
final class SupportHealthScoreService {

  /**
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param object|null $customerSuccess
   *   Optional customer success service (ecosistema_jaraba_core.customer_success).
   */
  public function __construct(
    protected Connection $database,
    protected LoggerInterface $logger,
    protected ?object $customerSuccess,
  ) {}

  /**
   * Calculates the support health score for a tenant.
   *
   * Considers:
   * - Ticket volume trend (increasing = worse)
   * - SLA compliance rate
   * - Average CSAT score
   * - Escalation rate
   * - Reopening rate
   *
   * @param int $tenantId
   *   The tenant ID (group entity).
   *
   * @return int
   *   Health score from 0 (critical) to 100 (excellent).
   */
  public function calculateSupportScore(int $tenantId): int {
    try {
      $now = time();
      $thirtyDaysAgo = $now - (30 * 86400);
      $sixtyDaysAgo = $now - (60 * 86400);
      $ninetyDaysAgo = $now - (90 * 86400);

      // --- a. Volume trend (0-20) ---
      // Last 30 days ticket count.
      $recentCount = (int) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.created', $thirtyDaysAgo, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Previous 30 days ticket count (30-60 days ago).
      $previousCount = (int) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.created', $sixtyDaysAgo, '>=')
        ->condition('t.created', $thirtyDaysAgo, '<')
        ->countQuery()
        ->execute()
        ->fetchField();

      $volumeScore = 20;
      if ($previousCount > 0) {
        $changeRate = ($recentCount - $previousCount) / $previousCount;
        if ($changeRate < -0.01) {
          // Decreasing.
          $volumeScore = 20;
        }
        elseif ($changeRate <= 0.10) {
          // Stable (±10%).
          $volumeScore = 15;
        }
        elseif ($changeRate <= 0.25) {
          // Increasing < 25%.
          $volumeScore = 10;
        }
        elseif ($changeRate <= 0.50) {
          // Increasing > 25% but <= 50%.
          $volumeScore = 5;
        }
        else {
          // Increasing > 50%.
          $volumeScore = 0;
        }
      }
      elseif ($recentCount === 0) {
        // No tickets at all — perfect.
        $volumeScore = 20;
      }

      // --- b. SLA compliance (0-20) ---
      $totalTickets90d = (int) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.created', $ninetyDaysAgo, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      $slaScore = 20;
      if ($totalTickets90d > 0) {
        $nonBreached = (int) $this->database->select('support_ticket', 't')
          ->condition('t.tenant_id', $tenantId)
          ->condition('t.created', $ninetyDaysAgo, '>=')
          ->condition('t.sla_breached', 0)
          ->countQuery()
          ->execute()
          ->fetchField();

        $slaRate = $nonBreached / $totalTickets90d;
        if ($slaRate >= 0.95) {
          $slaScore = 20;
        }
        elseif ($slaRate >= 0.90) {
          $slaScore = 15;
        }
        elseif ($slaRate >= 0.80) {
          $slaScore = 10;
        }
        elseif ($slaRate >= 0.70) {
          $slaScore = 5;
        }
        else {
          $slaScore = 0;
        }
      }

      // --- c. CSAT score (0-20) ---
      $csatAvg = (float) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.created', $ninetyDaysAgo, '>=')
        ->condition('t.satisfaction_rating', 0, '>')
        ->addExpression('AVG(t.satisfaction_rating)', 'avg_csat')
        ->execute()
        ->fetchField();

      if ($csatAvg >= 4.5) {
        $csatScore = 20;
      }
      elseif ($csatAvg >= 4.0) {
        $csatScore = 15;
      }
      elseif ($csatAvg >= 3.5) {
        $csatScore = 10;
      }
      elseif ($csatAvg >= 3.0) {
        $csatScore = 5;
      }
      else {
        // Covers < 3.0 and also 0.0 (no ratings).
        $csatScore = 0;
      }

      // --- d. Escalation rate (0-20) ---
      $escalationScore = 20;
      if ($totalTickets90d > 0) {
        $escalatedCount = (int) $this->database->select('ticket_event_log', 'e')
          ->join('support_ticket', 't', 't.id = e.ticket_id')
          ->condition('t.tenant_id', $tenantId)
          ->condition('t.created', $ninetyDaysAgo, '>=')
          ->condition('e.event_type', 'status_changed')
          ->condition('e.new_value', 'escalated')
          ->addExpression('COUNT(DISTINCT e.ticket_id)', 'cnt')
          ->execute()
          ->fetchField();

        $escalationRate = $escalatedCount / $totalTickets90d;
        if ($escalationRate <= 0.05) {
          $escalationScore = 20;
        }
        elseif ($escalationRate <= 0.10) {
          $escalationScore = 15;
        }
        elseif ($escalationRate <= 0.20) {
          $escalationScore = 10;
        }
        elseif ($escalationRate <= 0.30) {
          $escalationScore = 5;
        }
        else {
          $escalationScore = 0;
        }
      }

      // --- e. Resolution speed (0-20) ---
      $avgResolutionSeconds = (float) $this->database->select('support_ticket', 't')
        ->condition('t.tenant_id', $tenantId)
        ->condition('t.created', $ninetyDaysAgo, '>=')
        ->condition('t.resolved_at', 0, '>')
        ->addExpression('AVG(t.resolved_at - t.created)', 'avg_resolution')
        ->execute()
        ->fetchField();

      if ($avgResolutionSeconds <= 0) {
        // No resolved tickets — neutral, give benefit of the doubt.
        $resolutionScore = 15;
      }
      elseif ($avgResolutionSeconds <= 4 * 3600) {
        $resolutionScore = 20;
      }
      elseif ($avgResolutionSeconds <= 8 * 3600) {
        $resolutionScore = 15;
      }
      elseif ($avgResolutionSeconds <= 24 * 3600) {
        $resolutionScore = 10;
      }
      elseif ($avgResolutionSeconds <= 48 * 3600) {
        $resolutionScore = 5;
      }
      else {
        $resolutionScore = 0;
      }

      // Sum and clamp.
      $total = $volumeScore + $slaScore + $csatScore + $escalationScore + $resolutionScore;

      return max(0, min(100, $total));
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to calculate support score for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);

      // Benefit of the doubt.
      return 100;
    }
  }

  /**
   * Evaluates whether a churn alert should be triggered for a ticket's tenant.
   *
   * Called after negative support interactions (SLA breach, low CSAT,
   * repeated escalations). Delegates to CustomerSuccessService if available.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket that may trigger a churn alert.
   */
  public function triggerChurnAlertIfNeeded(SupportTicketInterface $ticket): void {
    try {
      $tenantId = $ticket->get('tenant_id')->target_id ?? NULL;
      if (!$tenantId) {
        return;
      }
      $tenantId = (int) $tenantId;

      $score = $this->calculateSupportScore($tenantId);

      if ($score < 40) {
        // Critical threshold — churn risk.
        if ($this->customerSuccess) {
          $this->customerSuccess->createAlert([
            'type' => 'churn_risk_support',
            'tenant_id' => $tenantId,
            'score' => $score,
            'ticket_id' => $ticket->id(),
          ]);
        }

        $this->logger->warning('Churn risk detected for tenant @tenant (support score: @score, ticket: @ticket).', [
          '@tenant' => $tenantId,
          '@score' => $score,
          '@ticket' => $ticket->id(),
        ]);
      }
      elseif ($score < 60) {
        // Warning threshold — declining support health.
        $this->logger->notice('Declining support health for tenant @tenant (score: @score, ticket: @ticket).', [
          '@tenant' => $tenantId,
          '@score' => $score,
          '@ticket' => $ticket->id(),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to evaluate churn alert for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'unknown',
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
