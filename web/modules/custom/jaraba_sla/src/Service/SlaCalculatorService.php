<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Service for SLA uptime calculations and compliance checks.
 *
 * Structure: Stateless service with DI for entity manager, tenant context, and database.
 * Logic: Calculates uptime percentages from incident data, generates monthly reports
 *   with per-component breakdowns, and determines credit percentages based on
 *   the SLA agreement's credit policy thresholds.
 */
class SlaCalculatorService {

  /**
   * Constructs a SlaCalculatorService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly Connection $database,
  ) {}

  /**
   * Calculates uptime percentage from incident data for a period.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $periodStart
   *   ISO 8601 datetime string for period start.
   * @param string $periodEnd
   *   ISO 8601 datetime string for period end.
   *
   * @return array
   *   Array with keys:
   *   - total_minutes: Total minutes in period.
   *   - downtime_minutes: Total downtime.
   *   - uptime_pct: Uptime percentage (0-100).
   *   - sla_met: Whether SLA was met (requires agreement context).
   */
  public function calculateUptime(int $tenantId, string $periodStart, string $periodEnd): array {
    try {
      $start = new \DateTimeImmutable($periodStart);
      $end = new \DateTimeImmutable($periodEnd);
      $totalMinutes = (int) (($end->getTimestamp() - $start->getTimestamp()) / 60);

      if ($totalMinutes <= 0) {
        return [
          'total_minutes' => 0,
          'downtime_minutes' => 0.0,
          'uptime_pct' => 100.0,
          'sla_met' => TRUE,
        ];
      }

      // Query resolved incidents in the period for this tenant.
      $incidentStorage = $this->entityTypeManager->getStorage('sla_incident');
      $query = $incidentStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('started_at', $periodEnd, '<')
        ->condition('status', ['resolved', 'postmortem'], 'IN');

      $ids = $query->execute();
      $downtimeMinutes = 0.0;

      if (!empty($ids)) {
        $incidents = $incidentStorage->loadMultiple($ids);
        foreach ($incidents as $incident) {
          $incidentStart = new \DateTimeImmutable($incident->get('started_at')->value);
          $resolvedAt = $incident->get('resolved_at')->value;
          $incidentEnd = $resolvedAt ? new \DateTimeImmutable($resolvedAt) : $end;

          // Clamp to period boundaries.
          $effectiveStart = max($incidentStart->getTimestamp(), $start->getTimestamp());
          $effectiveEnd = min($incidentEnd->getTimestamp(), $end->getTimestamp());

          if ($effectiveEnd > $effectiveStart) {
            $downtimeMinutes += ($effectiveEnd - $effectiveStart) / 60;
          }
        }
      }

      $downtimeMinutes = round($downtimeMinutes, 2);
      $uptimePct = round((($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100, 3);

      // Check SLA compliance against the tenant's active agreement.
      $slaMet = TRUE;
      $agreements = $this->entityTypeManager->getStorage('sla_agreement')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('is_active', TRUE)
        ->range(0, 1)
        ->execute();

      if (!empty($agreements)) {
        $agreement = $this->entityTypeManager->getStorage('sla_agreement')
          ->load(reset($agreements));
        if ($agreement) {
          $target = (float) $agreement->get('uptime_target')->value;
          $slaMet = $uptimePct >= $target;
        }
      }

      return [
        'total_minutes' => $totalMinutes,
        'downtime_minutes' => $downtimeMinutes,
        'uptime_pct' => $uptimePct,
        'sla_met' => $slaMet,
      ];
    }
    catch (\Exception $e) {
      return [
        'total_minutes' => 0,
        'downtime_minutes' => 0.0,
        'uptime_pct' => 100.0,
        'sla_met' => TRUE,
      ];
    }
  }

  /**
   * Generates a full monthly SLA report with per-component breakdown.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param int $year
   *   The year (e.g. 2026).
   * @param int $month
   *   The month (1-12).
   *
   * @return array
   *   Report array with overall metrics and per-component breakdown.
   */
  public function getMonthlyReport(int $tenantId, int $year, int $month): array {
    $periodStart = sprintf('%04d-%02d-01T00:00:00', $year, $month);
    $lastDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $periodEnd = sprintf('%04d-%02d-%02dT23:59:59', $year, $month, $lastDay);

    $overall = $this->calculateUptime($tenantId, $periodStart, $periodEnd);

    $components = ['web_app', 'api', 'database', 'redis', 'email', 'ai_copilot', 'payment'];
    $componentBreakdown = [];

    foreach ($components as $component) {
      $componentBreakdown[$component] = $this->calculateComponentUptime(
        $tenantId,
        $component,
        $periodStart,
        $periodEnd,
        $overall['total_minutes']
      );
    }

    // Determine credit if SLA was not met.
    $creditPct = 0.0;
    $agreements = $this->entityTypeManager->getStorage('sla_agreement')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->range(0, 1)
      ->execute();

    if (!empty($agreements)) {
      $agreement = $this->entityTypeManager->getStorage('sla_agreement')
        ->load(reset($agreements));
      if ($agreement) {
        $creditPolicy = json_decode($agreement->get('credit_policy')->value ?? '[]', TRUE) ?? [];
        $creditPct = $this->calculateCredit($overall['uptime_pct'], $creditPolicy);
      }
    }

    return [
      'tenant_id' => $tenantId,
      'period' => sprintf('%04d-%02d', $year, $month),
      'period_start' => $periodStart,
      'period_end' => $periodEnd,
      'overall' => $overall,
      'components' => $componentBreakdown,
      'credit_pct' => $creditPct,
    ];
  }

  /**
   * Checks if the current period meets the SLA target for an agreement.
   *
   * @param int $agreementId
   *   The SLA agreement entity ID.
   *
   * @return array
   *   Compliance check result with keys: compliant, uptime_pct, target, gap.
   */
  public function checkSlaCompliance(int $agreementId): array {
    try {
      $agreement = $this->entityTypeManager->getStorage('sla_agreement')
        ->load($agreementId);

      if (!$agreement) {
        return [
          'compliant' => FALSE,
          'error' => 'Agreement not found.',
        ];
      }

      $tenantId = (int) $agreement->get('tenant_id')->target_id;
      $target = (float) $agreement->get('uptime_target')->value;

      // Current month calculation.
      $now = new \DateTimeImmutable();
      $periodStart = $now->format('Y-m-01T00:00:00');
      $periodEnd = $now->format('Y-m-t\T23:59:59');

      $uptime = $this->calculateUptime($tenantId, $periodStart, $periodEnd);

      return [
        'compliant' => $uptime['uptime_pct'] >= $target,
        'uptime_pct' => $uptime['uptime_pct'],
        'target' => $target,
        'gap' => round($uptime['uptime_pct'] - $target, 3),
        'downtime_minutes' => $uptime['downtime_minutes'],
        'period' => $now->format('Y-m'),
      ];
    }
    catch (\Exception $e) {
      return [
        'compliant' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Determines credit percentage based on uptime and policy thresholds.
   *
   * The credit policy is an array of threshold/credit_pct pairs sorted
   * descending by threshold. The first threshold the uptime falls below
   * determines the credit.
   *
   * @param float $uptimePct
   *   The actual uptime percentage.
   * @param array $creditPolicy
   *   Array of ['threshold' => float, 'credit_pct' => float] entries.
   *
   * @return float
   *   The credit percentage to apply.
   */
  public function calculateCredit(float $uptimePct, array $creditPolicy): float {
    // Sort by threshold descending so we find the highest applicable bracket.
    usort($creditPolicy, fn(array $a, array $b) => ($b['threshold'] ?? 0) <=> ($a['threshold'] ?? 0));

    foreach ($creditPolicy as $tier) {
      $threshold = (float) ($tier['threshold'] ?? 0);
      $creditPct = (float) ($tier['credit_pct'] ?? 0);

      if ($uptimePct < $threshold) {
        continue;
      }

      return $creditPct;
    }

    // If we exhausted all tiers, return the last (highest credit).
    $last = end($creditPolicy);
    return (float) ($last['credit_pct'] ?? 0);
  }

  /**
   * Calculates uptime for a specific component in a period.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $component
   *   The component name.
   * @param string $periodStart
   *   Period start datetime.
   * @param string $periodEnd
   *   Period end datetime.
   * @param int $totalMinutes
   *   Total minutes in the period.
   *
   * @return array
   *   Component uptime data.
   */
  protected function calculateComponentUptime(int $tenantId, string $component, string $periodStart, string $periodEnd, int $totalMinutes): array {
    if ($totalMinutes <= 0) {
      return [
        'component' => $component,
        'uptime_pct' => 100.0,
        'downtime_minutes' => 0.0,
        'incidents' => 0,
      ];
    }

    try {
      $start = new \DateTimeImmutable($periodStart);
      $end = new \DateTimeImmutable($periodEnd);

      $incidentStorage = $this->entityTypeManager->getStorage('sla_incident');
      $query = $incidentStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('component', $component)
        ->condition('started_at', $periodEnd, '<')
        ->condition('status', ['resolved', 'postmortem'], 'IN');

      $ids = $query->execute();
      $downtimeMinutes = 0.0;
      $incidentCount = 0;

      if (!empty($ids)) {
        $incidents = $incidentStorage->loadMultiple($ids);
        foreach ($incidents as $incident) {
          $incidentStart = new \DateTimeImmutable($incident->get('started_at')->value);
          $resolvedAt = $incident->get('resolved_at')->value;
          $incidentEnd = $resolvedAt ? new \DateTimeImmutable($resolvedAt) : $end;

          $effectiveStart = max($incidentStart->getTimestamp(), $start->getTimestamp());
          $effectiveEnd = min($incidentEnd->getTimestamp(), $end->getTimestamp());

          if ($effectiveEnd > $effectiveStart) {
            $downtimeMinutes += ($effectiveEnd - $effectiveStart) / 60;
            $incidentCount++;
          }
        }
      }

      $downtimeMinutes = round($downtimeMinutes, 2);
      $uptimePct = round((($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100, 3);

      return [
        'component' => $component,
        'uptime_pct' => $uptimePct,
        'downtime_minutes' => $downtimeMinutes,
        'incidents' => $incidentCount,
      ];
    }
    catch (\Exception $e) {
      return [
        'component' => $component,
        'uptime_pct' => 100.0,
        'downtime_minutes' => 0.0,
        'incidents' => 0,
      ];
    }
  }

}
