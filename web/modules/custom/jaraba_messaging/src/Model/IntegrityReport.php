<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Model;

/**
 * Value Object for audit/integrity verification results.
 *
 * Immutable report produced by the integrity-check service after
 * verifying the HMAC chain of secure_message entries. Contains
 * the verification outcome, total entries checked, the position
 * where the chain broke (if any), and human-readable details.
 *
 * Usage:
 *   $report = IntegrityReport::success(150);
 *   $report = IntegrityReport::failure(150, 42, 'HMAC mismatch at row 42.');
 */
final readonly class IntegrityReport {

  /**
   * Constructs an IntegrityReport.
   *
   * @param bool $valid
   *   Whether the integrity check passed.
   * @param int $total_entries
   *   Total number of entries verified.
   * @param int|null $broken_at
   *   Index/ID of the first entry where integrity failed, or NULL.
   * @param string $details
   *   Human-readable description of the verification result.
   */
  public function __construct(
    public bool $valid,
    public int $total_entries,
    public ?int $broken_at,
    public string $details,
  ) {}

  /**
   * Creates a successful integrity report.
   *
   * @param int $total
   *   Total number of entries verified.
   *
   * @return self
   */
  public static function success(int $total): self {
    return new self(
      valid: TRUE,
      total_entries: $total,
      broken_at: NULL,
      details: sprintf('All %d entries passed integrity verification.', $total),
    );
  }

  /**
   * Creates a failed integrity report.
   *
   * @param int $total
   *   Total number of entries verified.
   * @param int $brokenAt
   *   Index/ID of the first entry where integrity failed.
   * @param string $details
   *   Human-readable description of the failure.
   *
   * @return self
   */
  public static function failure(int $total, int $brokenAt, string $details): self {
    return new self(
      valid: FALSE,
      total_entries: $total,
      broken_at: $brokenAt,
      details: $details,
    );
  }

}
