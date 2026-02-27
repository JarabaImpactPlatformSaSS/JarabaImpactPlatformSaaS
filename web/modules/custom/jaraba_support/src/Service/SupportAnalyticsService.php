<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Support analytics and metrics service.
 *
 * Provides aggregated statistics for the support dashboard using
 * direct database queries for performance.
 */
final class SupportAnalyticsService {

  public function __construct(
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets overview statistics for the support dashboard.
   */
  public function getOverviewStats(array $filters = []): array {
    try {
      $total = $this->countTickets([], $filters);
      $openStatuses = ['new', 'ai_handling', 'open', 'escalated', 'reopened'];
      $openCount = $this->countTickets(['status' => $openStatuses], $filters);
      $pendingCount = $this->countTickets(['status' => ['pending_customer', 'pending_internal']], $filters);
      $resolvedCount = $this->countTickets(['status' => ['resolved', 'closed']], $filters);
      $slaBreachedCount = $this->countTickets(['sla_breached' => 1], $filters);
      $todayResolved = $this->countTicketsResolvedToday($filters);

      // SLA compliance.
      $slaCompliance = $total > 0 ? round((($total - $slaBreachedCount) / $total) * 100, 1) : 100.0;

      // Average CSAT score.
      $csatScore = $this->getAverageCsat($filters);

      // Avg response time.
      $avgResponse = $this->getAverageResponseTime($filters);

      return [
        'total_tickets' => $total,
        'total_queue' => $openCount + $pendingCount,
        'open_tickets' => $openCount,
        'pending_tickets' => $pendingCount,
        'resolved_tickets' => $resolvedCount,
        'tickets_resolved_today' => $todayResolved,
        'sla_compliance' => $slaCompliance,
        'csat_score' => $csatScore,
        'avg_response_time' => $this->formatHours($avgResponse),
        'avg_resolution_time' => '--',
        'my_open' => 0,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Analytics getOverviewStats failed: @msg', ['@msg' => $e->getMessage()]);
      return [
        'total_tickets' => 0,
        'open_tickets' => 0,
        'total_queue' => 0,
        'pending_tickets' => 0,
        'resolved_tickets' => 0,
        'tickets_resolved_today' => 0,
        'sla_compliance' => 0.0,
        'csat_score' => 0.0,
        'avg_response_time' => '--',
        'avg_resolution_time' => '--',
        'my_open' => 0,
      ];
    }
  }

  /**
   * Gets ticket volume grouped by time period.
   */
  public function getVolumeByPeriod(string $period = 'day', array $filters = []): array {
    try {
      $dateFormat = match ($period) {
        'week' => '%Y-W%v',
        'month' => '%Y-%m',
        default => '%Y-%m-%d',
      };

      $query = $this->database->select('support_ticket_field_data', 't');
      $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(t.created), :format)", 'period', [':format' => $dateFormat]);
      $query->addExpression('COUNT(*)', 'count');
      $this->applyFilters($query, $filters);
      $query->groupBy('period');
      $query->orderBy('period', 'DESC');
      $query->range(0, 30);

      return $query->execute()->fetchAllKeyed();
    }
    catch (\Exception $e) {
      $this->logger->error('Analytics getVolumeByPeriod failed: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets agent performance metrics.
   *
   * @param int|array $filters
   *   Agent UID (int) or array of filters.
   */
  public function getAgentPerformance(int|array $filters = []): array {
    try {
      if (is_int($filters)) {
        $agentUid = $filters;
        $filters = ['assignee_uid' => $agentUid];
      }

      $query = $this->database->select('support_ticket_field_data', 't');
      $query->addField('t', 'assignee_uid', 'agent_uid');
      $query->addExpression('COUNT(*)', 'tickets_handled');
      $query->addExpression("AVG(t.satisfaction_rating)", 'csat_avg');
      $query->addExpression("SUM(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 ELSE 0 END)", 'resolved_count');
      $query->condition('t.assignee_uid', 0, '>');
      $this->applyFilters($query, $filters);
      $query->groupBy('t.assignee_uid');

      $results = $query->execute()->fetchAllAssoc('agent_uid', \PDO::FETCH_ASSOC);

      if (empty($results)) {
        return [
          'tickets_handled' => 0,
          'csat_avg' => 0,
          'resolution_rate' => 0,
          'avg_response_time' => '--',
        ];
      }

      // If single agent, return flat array.
      if (isset($filters['assignee_uid'])) {
        $row = reset($results);
        $handled = (int) ($row['tickets_handled'] ?? 0);
        $resolved = (int) ($row['resolved_count'] ?? 0);
        return [
          'tickets_handled' => $handled,
          'csat_avg' => round((float) ($row['csat_avg'] ?? 0), 1),
          'resolution_rate' => $handled > 0 ? round(($resolved / $handled) * 100, 1) : 0,
          'avg_response_time' => '--',
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Analytics getAgentPerformance failed: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets ticket distribution by category.
   */
  public function getCategoryDistribution(array $filters = []): array {
    try {
      $query = $this->database->select('support_ticket_field_data', 't');
      $query->addField('t', 'category');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('t.category', '', '<>');
      $this->applyFilters($query, $filters);
      $query->groupBy('t.category');
      $query->orderBy('count', 'DESC');

      return $query->execute()->fetchAllKeyed();
    }
    catch (\Exception $e) {
      $this->logger->error('Analytics getCategoryDistribution failed: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Counts tickets matching conditions.
   */
  private function countTickets(array $conditions, array $filters = []): int {
    $query = $this->database->select('support_ticket_field_data', 't');
    $query->addExpression('COUNT(*)', 'count');

    foreach ($conditions as $field => $value) {
      if (is_array($value)) {
        $query->condition('t.' . $field, $value, 'IN');
      }
      else {
        $query->condition('t.' . $field, $value);
      }
    }

    $this->applyFilters($query, $filters);
    return (int) $query->execute()->fetchField();
  }

  /**
   * Counts tickets resolved today.
   */
  private function countTicketsResolvedToday(array $filters): int {
    $todayStart = strtotime('today midnight');
    $query = $this->database->select('support_ticket_field_data', 't');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('t.resolved_at', $todayStart, '>=');
    $this->applyFilters($query, $filters);
    return (int) $query->execute()->fetchField();
  }

  /**
   * Gets average CSAT score.
   */
  private function getAverageCsat(array $filters): float {
    $query = $this->database->select('support_ticket_field_data', 't');
    $query->addExpression('AVG(t.satisfaction_rating)', 'avg_csat');
    $query->condition('t.satisfaction_rating', 0, '>');
    $this->applyFilters($query, $filters);
    return round((float) ($query->execute()->fetchField() ?? 0), 1);
  }

  /**
   * Gets average first response time in hours.
   */
  private function getAverageResponseTime(array $filters): float {
    $query = $this->database->select('support_ticket_field_data', 't');
    $query->addExpression('AVG(t.first_responded_at - t.created)', 'avg_seconds');
    $query->condition('t.first_responded_at', 0, '>');
    $this->applyFilters($query, $filters);
    $avgSeconds = (float) ($query->execute()->fetchField() ?? 0);
    return $avgSeconds > 0 ? $avgSeconds / 3600 : 0;
  }

  /**
   * Applies common filters to a query.
   */
  private function applyFilters($query, array $filters): void {
    if (!empty($filters['tenant_id'])) {
      $query->condition('t.tenant_id', $filters['tenant_id']);
    }
    if (!empty($filters['assignee_uid'])) {
      $query->condition('t.assignee_uid', $filters['assignee_uid']);
    }
    if (!empty($filters['vertical'])) {
      $query->condition('t.vertical', $filters['vertical']);
    }
    if (!empty($filters['date_from'])) {
      $query->condition('t.created', strtotime($filters['date_from']), '>=');
    }
    if (!empty($filters['date_to'])) {
      $query->condition('t.created', strtotime($filters['date_to']), '<=');
    }
  }

  /**
   * Formats hours into human-readable string.
   */
  private function formatHours(float $hours): string {
    if ($hours <= 0) {
      return '--';
    }
    if ($hours < 1) {
      return round($hours * 60) . 'min';
    }
    return round($hours, 1) . 'h';
  }

}
