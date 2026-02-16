<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ValueObject;

/**
 * Resultado inmutable del envio de un batch de remision a la AEAT.
 *
 * Combina la respuesta AEAT con metadatos del envio (tiempos, reintentos).
 *
 * Spec: Doc 179, Seccion 4. Plan: FASE 3, entregable F3-2.
 */
final class RemisionResult {

  /**
   * Constructs a RemisionResult.
   *
   * @param bool $isSuccess
   *   Whether the submission was successful.
   * @param int $batchId
   *   The VeriFactuRemisionBatch entity ID.
   * @param \Drupal\jaraba_verifactu\ValueObject\AeatResponse|null $aeatResponse
   *   The parsed AEAT response.
   * @param int $retryCount
   *   Number of retry attempts made.
   * @param float $durationMs
   *   Total time for the submission in milliseconds.
   * @param string $errorMessage
   *   Error message if submission failed before reaching AEAT.
   */
  public function __construct(
    public readonly bool $isSuccess,
    public readonly int $batchId,
    public readonly ?AeatResponse $aeatResponse = NULL,
    public readonly int $retryCount = 0,
    public readonly float $durationMs = 0.0,
    public readonly string $errorMessage = '',
  ) {}

  /**
   * Creates a successful result.
   */
  public static function success(int $batchId, AeatResponse $aeatResponse, float $durationMs): self {
    return new self(
      isSuccess: TRUE,
      batchId: $batchId,
      aeatResponse: $aeatResponse,
      durationMs: $durationMs,
    );
  }

  /**
   * Creates a failed result.
   */
  public static function failure(int $batchId, string $errorMessage, int $retryCount = 0): self {
    return new self(
      isSuccess: FALSE,
      batchId: $batchId,
      retryCount: $retryCount,
      errorMessage: $errorMessage,
    );
  }

  /**
   * Returns a summary array for JSON serialization.
   */
  public function toArray(): array {
    return [
      'is_success' => $this->isSuccess,
      'batch_id' => $this->batchId,
      'aeat_response' => $this->aeatResponse?->toArray(),
      'retry_count' => $this->retryCount,
      'duration_ms' => round($this->durationMs, 2),
      'error_message' => $this->errorMessage,
    ];
  }

}
