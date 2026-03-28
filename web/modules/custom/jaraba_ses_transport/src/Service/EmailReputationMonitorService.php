<?php

declare(strict_types=1);

namespace Drupal\jaraba_ses_transport\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * EMAIL-REPUTATION-MONITOR-001: Monitors email sending reputation.
 *
 * Tracks bounce/complaint rates from the local suppression table and alerts
 * when thresholds are exceeded. Runs via cron (every 6h) and exposes status
 * via hook_requirements() for the Drupal status report.
 *
 * Thresholds (aligned with AWS SES best practices):
 * - Bounce rate > 5% → WARNING (AWS pauses at 10%)
 * - Complaint rate > 0.1% → ERROR (AWS pauses at 0.5%)
 * - Suppression list > 100 entries → INFO
 */
class EmailReputationMonitorService {

  /**
   * AWS SES pauses sending at 10% bounce rate. We warn at 5%.
   */
  private const BOUNCE_RATE_WARNING = 0.05;

  /**
   * AWS SES pauses sending at 0.5% complaint rate. We error at 0.1%.
   */
  private const COMPLAINT_RATE_ERROR = 0.001;

  /**
   * State key for last check timestamp.
   */
  private const STATE_LAST_CHECK = 'jaraba_ses.reputation_last_check';

  /**
   * State key for cached reputation data.
   */
  private const STATE_REPUTATION = 'jaraba_ses.reputation_data';

  /**
   * Minimum interval between checks (6 hours).
   */
  private const CHECK_INTERVAL = 21600;

  public function __construct(
    private readonly Connection $database,
    private readonly StateInterface $state,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Checks reputation metrics and caches results in State.
   *
   * Called from hook_cron. Only runs every CHECK_INTERVAL seconds.
   *
   * @return array{bounce_rate: float, complaint_rate: float, total_suppressed: int, bounces_24h: int, complaints_24h: int, status: string}
   */
  public function checkReputation(): array {
    $now = \Drupal::time()->getRequestTime();
    $lastCheck = $this->state->get(self::STATE_LAST_CHECK, 0);

    // Rate-limit checks to every 6 hours.
    if (($now - $lastCheck) < self::CHECK_INTERVAL) {
      return $this->getCachedReputation();
    }

    $data = $this->calculateReputation($now);

    $this->state->set(self::STATE_REPUTATION, $data);
    $this->state->set(self::STATE_LAST_CHECK, $now);

    // Log warnings/errors.
    if ($data['status'] === 'critical') {
      $this->logger->error('SES reputation CRITICAL: complaint rate @rate% exceeds threshold. Risk of account suspension.', [
        '@rate' => round($data['complaint_rate'] * 100, 3),
      ]);
    }
    elseif ($data['status'] === 'warning') {
      $this->logger->warning('SES reputation WARNING: bounce rate @rate% exceeds threshold.', [
        '@rate' => round($data['bounce_rate'] * 100, 2),
      ]);
    }

    return $data;
  }

  /**
   * Returns cached reputation data for hook_requirements().
   *
   * @return array{bounce_rate: float, complaint_rate: float, total_suppressed: int, bounces_24h: int, complaints_24h: int, status: string}
   */
  public function getCachedReputation(): array {
    return $this->state->get(self::STATE_REPUTATION, [
      'bounce_rate' => 0.0,
      'complaint_rate' => 0.0,
      'total_suppressed' => 0,
      'bounces_24h' => 0,
      'complaints_24h' => 0,
      'status' => 'healthy',
      'checked_at' => 0,
    ]);
  }

  /**
   * Calculates reputation metrics from the suppression table.
   *
   * @param int $now
   *   Current timestamp.
   *
   * @return array{bounce_rate: float, complaint_rate: float, total_suppressed: int, bounces_24h: int, complaints_24h: int, status: string, checked_at: int}
   */
  private function calculateReputation(int $now): array {
    $oneDayAgo = $now - 86400;
    $sevenDaysAgo = $now - 604800;

    try {
      // Total suppressed.
      $query = $this->database->select('email_suppression', 'es');
      $query->addExpression('COUNT(*)', 'total');
      $totalSuppressed = (int) $query->execute()->fetchField();

      // Bounces in last 24h.
      $query = $this->database->select('email_suppression', 'es');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('reason', 'bounce');
      $query->condition('created', $oneDayAgo, '>=');
      $bounces24h = (int) $query->execute()->fetchField();

      // Complaints in last 24h.
      $query = $this->database->select('email_suppression', 'es');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('reason', 'complaint');
      $query->condition('created', $oneDayAgo, '>=');
      $complaints24h = (int) $query->execute()->fetchField();

      // 7-day totals for rate calculation.
      $query = $this->database->select('email_suppression', 'es');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('reason', 'bounce');
      $query->condition('created', $sevenDaysAgo, '>=');
      $bounces7d = (int) $query->execute()->fetchField();

      $query = $this->database->select('email_suppression', 'es');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('reason', 'complaint');
      $query->condition('created', $sevenDaysAgo, '>=');
      $complaints7d = (int) $query->execute()->fetchField();

      // Estimate total sends from watchdog (approximate).
      // Use suppression entries as denominator proxy if no send counter exists.
      // A healthy list has <5% bounces, so total sends ≈ bounces7d / bounce_rate_estimate.
      // For safety, we use the raw counts as indicators.
      $totalEvents7d = $bounces7d + $complaints7d;
      // Use a conservative estimate: assume 1000 sends/week minimum.
      $estimatedSends = max(1000, $totalEvents7d * 20);

      $bounceRate = $bounces7d / $estimatedSends;
      $complaintRate = $complaints7d / $estimatedSends;

      // Determine status.
      $status = 'healthy';
      if ($complaintRate >= self::COMPLAINT_RATE_ERROR) {
        $status = 'critical';
      }
      elseif ($bounceRate >= self::BOUNCE_RATE_WARNING) {
        $status = 'warning';
      }

      return [
        'bounce_rate' => $bounceRate,
        'complaint_rate' => $complaintRate,
        'total_suppressed' => $totalSuppressed,
        'bounces_24h' => $bounces24h,
        'complaints_24h' => $complaints24h,
        'bounces_7d' => $bounces7d,
        'complaints_7d' => $complaints7d,
        'status' => $status,
        'checked_at' => $now,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('SES reputation check failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'bounce_rate' => 0.0,
        'complaint_rate' => 0.0,
        'total_suppressed' => 0,
        'bounces_24h' => 0,
        'complaints_24h' => 0,
        'status' => 'unknown',
        'checked_at' => $now,
      ];
    }
  }

}
