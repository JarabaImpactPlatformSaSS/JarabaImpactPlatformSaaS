<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Revenue metrics service — MRR, ARR, churn, retention.
 *
 * GAP-REVENUE-DASH: Calculates core SaaS revenue KPIs from
 * billing_invoice entities and tenant subscription status.
 *
 * TENANT-001: Metrics are platform-wide (admin only).
 * TRANSLATABLE-FIELDDATA-001: Queries use _field_data tables.
 * DATETIME-ARITHMETIC-001: created/changed = INT Unix timestamps.
 */
class RevenueMetricsService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly ?SyntheticCfoService $syntheticCfo = NULL,
  ) {}

  /**
   * Calculates current MRR from paid invoices.
   *
   * MRR = sum of the last paid invoice amount per active tenant,
   * normalized to monthly.
   *
   * @return array{mrr: float, currency: string, active_subscriptions: int}
   */
  public function calculateMrr(): array {
    try {
      // Get latest paid invoice per tenant.
      $query = $this->database->select('billing_invoice_field_data', 'bi');
      $query->addField('bi', 'tenant_id');
      $query->addExpression('MAX(bi.id)', 'latest_id');
      $query->condition('bi.status', 'paid');
      $query->groupBy('bi.tenant_id');
      $subquery = $query;

      // Now get the total of those latest invoices.
      $outer = $this->database->select('billing_invoice_field_data', 'bi2');
      $outer->addExpression('SUM(bi2.total)', 'total_mrr');
      $outer->addExpression('COUNT(bi2.id)', 'count');
      $outer->join($subquery, 'latest', 'bi2.id = latest.latest_id');

      $result = $outer->execute()->fetchAssoc();

      return [
        'mrr' => (float) ($result['total_mrr'] ?? 0) / 100,
        'currency' => 'EUR',
        'active_subscriptions' => (int) ($result['count'] ?? 0),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('MRR calculation error: @error', ['@error' => $e->getMessage()]);
      return ['mrr' => 0, 'currency' => 'EUR', 'active_subscriptions' => 0];
    }
  }

  /**
   * Calculates ARR (Annual Recurring Revenue).
   *
   * @return array{arr: float, mrr: float}
   */
  public function calculateArr(): array {
    $mrr = $this->calculateMrr();
    return [
      'arr' => $mrr['mrr'] * 12,
      'mrr' => $mrr['mrr'],
    ];
  }

  /**
   * Calculates monthly revenue over a time range.
   *
   * @param int $months
   *   Number of months to look back.
   *
   * @return array<string, float>
   *   Revenue keyed by 'YYYY-MM'.
   */
  public function getMonthlyRevenue(int $months = 12): array {
    $result = [];
    try {
      $since = strtotime("-{$months} months", time());

      $query = $this->database->select('billing_invoice_field_data', 'bi');
      $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(bi.created), '%Y-%m')", 'month');
      $query->addExpression('SUM(bi.total)', 'revenue');
      $query->condition('bi.status', 'paid');
      $query->condition('bi.created', $since, '>=');
      $query->groupBy('month');
      $query->orderBy('month', 'ASC');

      $rows = $query->execute()->fetchAll();
      foreach ($rows as $row) {
        $result[$row->month] = (float) $row->revenue / 100;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Monthly revenue error: @error', ['@error' => $e->getMessage()]);
    }
    return $result;
  }

  /**
   * Calculates churn rate for a given month.
   *
   * Churn = tenants that moved to cancelled/suspended during the month
   * divided by total active tenants at the start of the month.
   *
   * @param string $month
   *   Month in 'YYYY-MM' format. Defaults to current month.
   *
   * @return array{churn_rate: float, churned_tenants: int, start_tenants: int}
   */
  public function calculateChurnRate(string $month = ''): array {
    if (!$month) {
      $month = date('Y-m');
    }

    try {
      $monthStart = strtotime($month . '-01');
      $monthEnd = strtotime('+1 month', $monthStart);

      // Tenants that were active at the start of the month.
      $activeAtStart = $this->database->select('tenant_field_data', 't')
        ->condition('t.subscription_status', ['active', 'trial'], 'IN')
        ->condition('t.created', $monthStart, '<')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Tenants that churned during the month.
      $churned = $this->database->select('tenant_field_data', 't')
        ->condition('t.subscription_status', ['cancelled', 'suspended'], 'IN')
        ->condition('t.changed', $monthStart, '>=')
        ->condition('t.changed', $monthEnd, '<')
        ->countQuery()
        ->execute()
        ->fetchField();

      $activeAtStart = (int) $activeAtStart;
      $churned = (int) $churned;
      $churnRate = $activeAtStart > 0 ? ($churned / $activeAtStart) * 100 : 0;

      return [
        'churn_rate' => round($churnRate, 2),
        'churned_tenants' => $churned,
        'start_tenants' => $activeAtStart,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Churn calculation error: @error', ['@error' => $e->getMessage()]);
      return ['churn_rate' => 0, 'churned_tenants' => 0, 'start_tenants' => 0];
    }
  }

  /**
   * Gets tenant distribution by subscription status.
   *
   * @return array<string, int>
   *   Count per status.
   */
  public function getTenantDistribution(): array {
    try {
      $query = $this->database->select('tenant_field_data', 't');
      $query->addField('t', 'subscription_status', 'status');
      $query->addExpression('COUNT(*)', 'count');
      $query->groupBy('t.subscription_status');

      $rows = $query->execute()->fetchAll();
      $distribution = [];
      foreach ($rows as $row) {
        $distribution[$row->status ?: 'unknown'] = (int) $row->count;
      }
      return $distribution;
    }
    catch (\Throwable $e) {
      $this->logger->error('Tenant distribution error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets revenue by plan.
   *
   * @return array<string, array{name: string, revenue: float, count: int}>
   */
  public function getRevenueByPlan(): array {
    try {
      $query = $this->database->select('billing_invoice_field_data', 'bi');
      $query->join('tenant_field_data', 't', 'bi.tenant_id = t.id');
      $query->leftJoin('subscription_plan_field_data', 'sp', 't.subscription_plan = sp.id');
      $query->addField('sp', 'id', 'plan_id');
      $query->addExpression("COALESCE(sp.name, 'Unknown')", 'plan_name');
      $query->addExpression('SUM(bi.total)', 'revenue');
      $query->addExpression('COUNT(DISTINCT bi.tenant_id)', 'tenant_count');
      $query->condition('bi.status', 'paid');
      // Last 30 days.
      $query->condition('bi.created', strtotime('-30 days'), '>=');
      $query->groupBy('sp.id');
      $query->groupBy('sp.name');

      $rows = $query->execute()->fetchAll();
      $result = [];
      foreach ($rows as $row) {
        $result[$row->plan_id ?: 'none'] = [
          'name' => $row->plan_name,
          'revenue' => (float) $row->revenue / 100,
          'count' => (int) $row->tenant_count,
        ];
      }
      return $result;
    }
    catch (\Throwable $e) {
      $this->logger->error('Revenue by plan error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets a full dashboard snapshot.
   *
   * @return array
   *   All revenue KPIs in one call.
   */
  public function getDashboardSnapshot(): array {
    $mrr = $this->calculateMrr();
    $churn = $this->calculateChurnRate();
    $distribution = $this->getTenantDistribution();
    $monthlyRevenue = $this->getMonthlyRevenue(12);

    $totalActive = ($distribution['active'] ?? 0) + ($distribution['trial'] ?? 0);

    return [
      'mrr' => $mrr['mrr'],
      'arr' => $mrr['mrr'] * 12,
      'active_subscriptions' => $mrr['active_subscriptions'],
      'total_active_tenants' => $totalActive,
      'churn_rate' => $churn['churn_rate'],
      'churned_tenants' => $churn['churned_tenants'],
      'tenant_distribution' => $distribution,
      'monthly_revenue' => $monthlyRevenue,
      'revenue_by_plan' => $this->getRevenueByPlan(),
      'commerce_revenue' => $this->getCommerceRevenue(),
      'currency' => $mrr['currency'],
      'generated_at' => date('c'),
    ];
  }

  /**
   * GAP-M07: Tracks a commerce sale event for revenue attribution.
   *
   * Called from CheckoutService when a marketplace order completes.
   *
   * @param int $tenantId
   *   The tenant that owns the marketplace.
   * @param float $amount
   *   The total order amount in EUR.
   */
  public function trackCommerceRevenue(int $tenantId, float $amount): void {
    try {
      $this->database->merge('billing_expansion_events')
        ->keys(['tenant_id' => $tenantId, 'event_type' => 'commerce_sale', 'event_date' => date('Y-m-d')])
        ->fields([
          'tenant_id' => $tenantId,
          'event_type' => 'commerce_sale',
          'event_date' => date('Y-m-d'),
          'amount' => $amount,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->expression('amount', 'amount + :inc', [':inc' => $amount])
        ->execute();
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-M07: Error tracking commerce revenue for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * GAP-M07: Gets total commerce revenue from FinancialTransaction (FOC).
   *
   * Queries financial_transaction entities where source_system = 'comercioconecta'
   * for the current period.
   *
   * @param int $months
   *   Number of months to look back.
   *
   * @return array{total: float, count: int, period: string}
   */
  public function getCommerceRevenue(int $months = 1): array {
    try {
      if (!$this->entityTypeManager->hasDefinition('financial_transaction')) {
        return ['total' => 0, 'count' => 0, 'period' => 'N/A'];
      }

      $startDate = strtotime("-{$months} months");
      $query = $this->database->select('financial_transaction', 'ft');
      $query->addExpression('SUM(ft.amount)', 'total');
      $query->addExpression('COUNT(ft.id)', 'count');
      $query->condition('ft.source_system', 'comercioconecta');
      $query->condition('ft.created', $startDate, '>=');
      $result = $query->execute()->fetchAssoc();

      return [
        'total' => (float) ($result['total'] ?? 0),
        'count' => (int) ($result['count'] ?? 0),
        'period' => sprintf('Last %d month(s)', $months),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-M07: Error querying commerce revenue: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['total' => 0, 'count' => 0, 'period' => 'error'];
    }
  }

}
