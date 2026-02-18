<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Service for SLA credit calculation and processing.
 *
 * Structure: Stateless service with DI for entity manager, tenant context,
 *   config, and logger.
 * Logic: Processes credits at end of each measurement period by comparing
 *   actual uptime against the SLA agreement's credit policy thresholds.
 *   Creates SlaMeasurement records as an immutable audit trail.
 */
class SlaCreditEngineService {

  /**
   * Constructs a SlaCreditEngineService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calculates and records credits for a measurement period.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param int $year
   *   The year.
   * @param int $month
   *   The month (1-12).
   *
   * @return array
   *   Credit calculation details with keys:
   *   - measurement_id: The created measurement entity ID.
   *   - uptime_pct: Calculated uptime percentage.
   *   - sla_met: Whether SLA target was met.
   *   - credit_pct: Credit percentage applied.
   *   - credit_amount: Absolute credit amount (pct of monthly fee).
   *   - agreement_id: The SLA agreement ID.
   */
  public function processCredits(int $tenantId, int $year, int $month): array {
    try {
      // Find the active SLA agreement for this tenant.
      $agreementIds = $this->entityTypeManager->getStorage('sla_agreement')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('is_active', TRUE)
        ->range(0, 1)
        ->execute();

      if (empty($agreementIds)) {
        return [
          'error' => 'No active SLA agreement found for tenant.',
          'tenant_id' => $tenantId,
        ];
      }

      $agreementId = (int) reset($agreementIds);
      $agreement = $this->entityTypeManager->getStorage('sla_agreement')
        ->load($agreementId);

      if (!$agreement) {
        return ['error' => 'Agreement could not be loaded.'];
      }

      $uptimeTarget = (float) $agreement->get('uptime_target')->value;
      $creditPolicy = json_decode($agreement->get('credit_policy')->value ?? '[]', TRUE) ?? [];

      // Calculate period boundaries.
      $periodStart = sprintf('%04d-%02d-01T00:00:00', $year, $month);
      $lastDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
      $periodEnd = sprintf('%04d-%02d-%02dT23:59:59', $year, $month, $lastDay);
      $totalMinutes = $lastDay * 24 * 60;

      // Calculate downtime from incidents.
      $downtimeMinutes = $this->calculateDowntime($tenantId, $periodStart, $periodEnd);
      $uptimePct = round((($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100, 3);
      $slaMet = $uptimePct >= $uptimeTarget;

      // Determine credit percentage.
      $creditPct = $this->determineCreditPct($uptimePct, $creditPolicy);

      // Collect incident IDs for the period.
      $incidentIds = $this->getIncidentIdsForPeriod($tenantId, $periodStart, $periodEnd);

      // Create the measurement record (append-only).
      $measurementStorage = $this->entityTypeManager->getStorage('sla_measurement');
      $measurement = $measurementStorage->create([
        'tenant_id' => $tenantId,
        'agreement_id' => $agreementId,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'total_minutes' => $totalMinutes,
        'downtime_minutes' => $downtimeMinutes,
        'uptime_pct' => $uptimePct,
        'sla_met' => $slaMet,
        'credit_amount' => $creditPct,
        'incidents' => json_encode($incidentIds),
        'excluded_maintenance_minutes' => 0,
      ]);
      $measurement->save();

      $this->logger->notice('SLA credits processed for tenant @tenant, period @period: uptime=@uptime%, credit=@credit%', [
        '@tenant' => $tenantId,
        '@period' => sprintf('%04d-%02d', $year, $month),
        '@uptime' => $uptimePct,
        '@credit' => $creditPct,
      ]);

      return [
        'measurement_id' => (int) $measurement->id(),
        'uptime_pct' => $uptimePct,
        'sla_met' => $slaMet,
        'credit_pct' => $creditPct,
        'credit_amount' => $creditPct,
        'agreement_id' => $agreementId,
        'period' => sprintf('%04d-%02d', $year, $month),
        'total_minutes' => $totalMinutes,
        'downtime_minutes' => $downtimeMinutes,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to process SLA credits for tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Returns all credit records (measurements) for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Array of credit history records.
   */
  public function getCreditsHistory(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('sla_measurement');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('period_start', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $measurements = $storage->loadMultiple($ids);
      $history = [];

      foreach ($measurements as $measurement) {
        $history[] = [
          'id' => (int) $measurement->id(),
          'agreement_id' => (int) ($measurement->get('agreement_id')->target_id ?? 0),
          'period_start' => $measurement->get('period_start')->value ?? '',
          'period_end' => $measurement->get('period_end')->value ?? '',
          'total_minutes' => (int) ($measurement->get('total_minutes')->value ?? 0),
          'downtime_minutes' => (float) ($measurement->get('downtime_minutes')->value ?? 0),
          'uptime_pct' => (float) ($measurement->get('uptime_pct')->value ?? 100),
          'sla_met' => (bool) ($measurement->get('sla_met')->value ?? TRUE),
          'credit_amount' => (float) ($measurement->get('credit_amount')->value ?? 0),
          'created' => $measurement->get('created')->value ?? '',
        ];
      }

      return $history;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load credit history for tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calculates total downtime minutes from incidents in a period.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $periodStart
   *   Period start datetime.
   * @param string $periodEnd
   *   Period end datetime.
   *
   * @return float
   *   Total downtime minutes.
   */
  protected function calculateDowntime(int $tenantId, string $periodStart, string $periodEnd): float {
    $start = new \DateTimeImmutable($periodStart);
    $end = new \DateTimeImmutable($periodEnd);

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

        $effectiveStart = max($incidentStart->getTimestamp(), $start->getTimestamp());
        $effectiveEnd = min($incidentEnd->getTimestamp(), $end->getTimestamp());

        if ($effectiveEnd > $effectiveStart) {
          $downtimeMinutes += ($effectiveEnd - $effectiveStart) / 60;
        }
      }
    }

    return round($downtimeMinutes, 2);
  }

  /**
   * Gets incident IDs for a period.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $periodStart
   *   Period start datetime.
   * @param string $periodEnd
   *   Period end datetime.
   *
   * @return array
   *   Array of incident IDs.
   */
  protected function getIncidentIdsForPeriod(int $tenantId, string $periodStart, string $periodEnd): array {
    try {
      $ids = $this->entityTypeManager->getStorage('sla_incident')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('started_at', $periodEnd, '<')
        ->condition('started_at', $periodStart, '>=')
        ->execute();

      return array_values(array_map('intval', $ids));
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Determines credit percentage based on uptime and policy thresholds.
   *
   * @param float $uptimePct
   *   Actual uptime percentage.
   * @param array $creditPolicy
   *   Array of threshold/credit_pct pairs.
   *
   * @return float
   *   Credit percentage.
   */
  protected function determineCreditPct(float $uptimePct, array $creditPolicy): float {
    usort($creditPolicy, fn(array $a, array $b) => ($b['threshold'] ?? 0) <=> ($a['threshold'] ?? 0));

    foreach ($creditPolicy as $tier) {
      $threshold = (float) ($tier['threshold'] ?? 0);
      $creditPct = (float) ($tier['credit_pct'] ?? 0);

      if ($uptimePct >= $threshold) {
        return $creditPct;
      }
    }

    $last = end($creditPolicy);
    return (float) ($last['credit_pct'] ?? 0);
  }

}
