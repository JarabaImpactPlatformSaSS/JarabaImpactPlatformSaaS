<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Exception;

/**
 * Exception thrown when the VeriFactu hash chain integrity is compromised.
 *
 * This is a critical exception indicating that one or more records in the
 * append-only hash chain have been tampered with or corrupted. The chain
 * MUST be investigated and recovered before new records can be created.
 *
 * This exception triggers a VeriFactuEventLog entry with type CHAIN_BREAK.
 *
 * @see \Drupal\jaraba_verifactu\Service\VeriFactuHashService
 */
class VeriFactuChainBreakException extends \RuntimeException {

  /**
   * Constructs a VeriFactuChainBreakException.
   *
   * @param int $tenantId
   *   The tenant ID where the chain break was detected.
   * @param int $recordId
   *   The record ID where the mismatch was found.
   * @param string $expectedHash
   *   The expected hash based on the previous record.
   * @param string $actualHash
   *   The actual hash found in the record.
   * @param \Throwable|null $previous
   *   The previous exception, if any.
   */
  public function __construct(
    public readonly int $tenantId,
    public readonly int $recordId,
    public readonly string $expectedHash,
    public readonly string $actualHash,
    ?\Throwable $previous = NULL,
  ) {
    $message = sprintf(
      'VeriFactu hash chain break detected for tenant %d at record %d. Expected: %s, Actual: %s',
      $tenantId,
      $recordId,
      substr($expectedHash, 0, 16) . '...',
      substr($actualHash, 0, 16) . '...',
    );
    parent::__construct($message, 0, $previous);
  }

}
