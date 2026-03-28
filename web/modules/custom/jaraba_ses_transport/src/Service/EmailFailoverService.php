<?php

declare(strict_types=1);

namespace Drupal\jaraba_ses_transport\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * EMAIL-FAILOVER-001: Circuit breaker for SES email transport.
 *
 * If SES SMTP fails N consecutive times, automatically switches to IONOS
 * fallback transport for a cooldown period. Prevents email outage when
 * SES is unavailable (sandbox, credentials expired, AWS incident).
 *
 * Pattern: Circuit breaker (same as PROVIDER-FALLBACK-001 for AI).
 * - CLOSED (normal): Use SES. Count failures.
 * - OPEN (tripped): Use IONOS fallback. Skip SES for cooldown period.
 * - HALF-OPEN: After cooldown, try SES again. If success → CLOSED. If fail → OPEN.
 */
class EmailFailoverService {

  /**
   * Failures before tripping the circuit breaker.
   */
  private const FAILURE_THRESHOLD = 3;

  /**
   * Cooldown period before retrying SES (15 minutes).
   */
  private const COOLDOWN_SECONDS = 900;

  /**
   * State key for failure count.
   */
  private const STATE_FAILURES = 'jaraba_ses.failover_failures';

  /**
   * State key for circuit breaker trip timestamp.
   */
  private const STATE_TRIPPED_AT = 'jaraba_ses.failover_tripped_at';

  /**
   * Primary transport ID.
   */
  public const TRANSPORT_SES = 'smtp_ses';

  /**
   * Fallback transport ID.
   */
  public const TRANSPORT_IONOS = 'smtp_ionos';

  public function __construct(
    private readonly StateInterface $state,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns the transport ID to use based on circuit breaker state.
   *
   * @return string
   *   Transport machine name: 'smtp_ses' or 'smtp_ionos'.
   */
  public function getActiveTransport(): string {
    $trippedAt = $this->state->get(self::STATE_TRIPPED_AT, 0);

    if ($trippedAt === 0) {
      // Circuit CLOSED — use SES.
      return self::TRANSPORT_SES;
    }

    $now = \Drupal::time()->getRequestTime();
    $elapsed = $now - $trippedAt;

    if ($elapsed >= self::COOLDOWN_SECONDS) {
      // HALF-OPEN — cooldown expired, try SES again.
      return self::TRANSPORT_SES;
    }

    // Circuit OPEN — use fallback.
    return self::TRANSPORT_IONOS;
  }

  /**
   * Records a successful send. Resets circuit breaker.
   */
  public function recordSuccess(): void {
    $trippedAt = $this->state->get(self::STATE_TRIPPED_AT, 0);
    if ($trippedAt > 0) {
      // Was in HALF-OPEN, now recovered.
      $this->logger->notice('SES transport recovered. Circuit breaker reset.');
    }
    $this->state->set(self::STATE_FAILURES, 0);
    $this->state->set(self::STATE_TRIPPED_AT, 0);
  }

  /**
   * Records a send failure. Trips circuit breaker if threshold reached.
   */
  public function recordFailure(): void {
    $failures = (int) $this->state->get(self::STATE_FAILURES, 0);
    $failures++;
    $this->state->set(self::STATE_FAILURES, $failures);

    if ($failures >= self::FAILURE_THRESHOLD) {
      $now = \Drupal::time()->getRequestTime();
      $this->state->set(self::STATE_TRIPPED_AT, $now);
      $this->logger->error(
        'SES transport circuit breaker TRIPPED after @count consecutive failures. Falling back to IONOS for @cooldown minutes.',
        [
          '@count' => $failures,
          '@cooldown' => self::COOLDOWN_SECONDS / 60,
        ]
      );
    }
    else {
      $this->logger->warning('SES transport failure @count/@threshold.', [
        '@count' => $failures,
        '@threshold' => self::FAILURE_THRESHOLD,
      ]);
    }
  }

  /**
   * Returns the current circuit breaker state for monitoring.
   *
   * @return array{state: string, failures: int, tripped_at: int, active_transport: string}
   */
  public function getState(): array {
    $trippedAt = (int) $this->state->get(self::STATE_TRIPPED_AT, 0);
    $failures = (int) $this->state->get(self::STATE_FAILURES, 0);

    if ($trippedAt === 0) {
      $circuitState = 'closed';
    }
    else {
      $now = \Drupal::time()->getRequestTime();
      $circuitState = ($now - $trippedAt >= self::COOLDOWN_SECONDS) ? 'half-open' : 'open';
    }

    return [
      'state' => $circuitState,
      'failures' => $failures,
      'tripped_at' => $trippedAt,
      'active_transport' => $this->getActiveTransport(),
    ];
  }

}
