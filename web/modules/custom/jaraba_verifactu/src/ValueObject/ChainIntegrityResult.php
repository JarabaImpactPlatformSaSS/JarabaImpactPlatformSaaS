<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ValueObject;

/**
 * Resultado inmutable de la verificacion de integridad de cadena hash.
 *
 * Encapsula el resultado de la verificacion SHA-256 de la cadena
 * de registros VeriFactu para un tenant. Indica si la cadena es
 * integra o si se ha detectado una rotura, con detalle del punto
 * exacto de la discrepancia.
 *
 * Spec: Doc 179, Seccion 3.1. Plan: FASE 2, entregable F2-1.
 */
final class ChainIntegrityResult {

  /**
   * Constructs a ChainIntegrityResult.
   *
   * @param bool $isValid
   *   Whether the entire hash chain is valid.
   * @param int $totalRecords
   *   Total number of records verified.
   * @param int $validRecords
   *   Number of records with valid hash.
   * @param int|null $breakAtRecordId
   *   Entity ID of the first record where chain break was detected.
   * @param string $expectedHash
   *   Expected hash at the break point (empty if valid).
   * @param string $actualHash
   *   Actual hash found at the break point (empty if valid).
   * @param float $verificationTimeMs
   *   Time taken for verification in milliseconds.
   * @param string $errorMessage
   *   Error message if verification failed for non-integrity reasons.
   */
  public function __construct(
    public readonly bool $isValid,
    public readonly int $totalRecords,
    public readonly int $validRecords,
    public readonly ?int $breakAtRecordId = NULL,
    public readonly string $expectedHash = '',
    public readonly string $actualHash = '',
    public readonly float $verificationTimeMs = 0.0,
    public readonly string $errorMessage = '',
  ) {}

  /**
   * Creates a valid result.
   */
  public static function valid(int $totalRecords, float $verificationTimeMs): self {
    return new self(
      isValid: TRUE,
      totalRecords: $totalRecords,
      validRecords: $totalRecords,
      verificationTimeMs: $verificationTimeMs,
    );
  }

  /**
   * Creates a result indicating a chain break.
   */
  public static function broken(
    int $totalRecords,
    int $validRecords,
    int $breakAtRecordId,
    string $expectedHash,
    string $actualHash,
    float $verificationTimeMs,
  ): self {
    return new self(
      isValid: FALSE,
      totalRecords: $totalRecords,
      validRecords: $validRecords,
      breakAtRecordId: $breakAtRecordId,
      expectedHash: $expectedHash,
      actualHash: $actualHash,
      verificationTimeMs: $verificationTimeMs,
    );
  }

  /**
   * Creates an error result when verification could not complete.
   */
  public static function error(string $message): self {
    return new self(
      isValid: FALSE,
      totalRecords: 0,
      validRecords: 0,
      errorMessage: $message,
    );
  }

  /**
   * Returns a summary array for JSON serialization.
   */
  public function toArray(): array {
    return [
      'is_valid' => $this->isValid,
      'total_records' => $this->totalRecords,
      'valid_records' => $this->validRecords,
      'break_at_record_id' => $this->breakAtRecordId,
      'expected_hash' => $this->expectedHash,
      'actual_hash' => $this->actualHash,
      'verification_time_ms' => round($this->verificationTimeMs, 2),
      'error_message' => $this->errorMessage,
    ];
  }

}
