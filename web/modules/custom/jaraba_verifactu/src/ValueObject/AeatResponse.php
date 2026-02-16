<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ValueObject;

/**
 * Resultado inmutable del parseo de una respuesta AEAT VeriFactu.
 *
 * Encapsula la respuesta SOAP de la AEAT, incluyendo el estado global
 * del envio y el desglose por registro (aceptado/rechazado).
 *
 * Spec: Doc 179, Seccion 4. Plan: FASE 3, entregable F3-1.
 */
final class AeatResponse {

  /**
   * Constructs an AeatResponse.
   *
   * @param bool $isSuccess
   *   Whether the overall submission was successful.
   * @param string $globalStatus
   *   Global status: 'Correcto', 'ParcialmenteCorrecto', 'Incorrecto'.
   * @param string $csv
   *   Codigo Seguro de Verificacion assigned by AEAT.
   * @param array $recordResults
   *   Per-record results: [['invoice' => string, 'status' => string,
   *   'code' => string, 'message' => string], ...].
   * @param int $acceptedCount
   *   Number of accepted records.
   * @param int $rejectedCount
   *   Number of rejected records.
   * @param string $rawXml
   *   The raw AEAT response XML.
   * @param string $errorMessage
   *   Global error message if the entire submission failed.
   */
  public function __construct(
    public readonly bool $isSuccess,
    public readonly string $globalStatus,
    public readonly string $csv = '',
    public readonly array $recordResults = [],
    public readonly int $acceptedCount = 0,
    public readonly int $rejectedCount = 0,
    public readonly string $rawXml = '',
    public readonly string $errorMessage = '',
  ) {}

  /**
   * Creates a successful response.
   */
  public static function success(
    string $csv,
    array $recordResults,
    int $acceptedCount,
    string $rawXml,
  ): self {
    return new self(
      isSuccess: TRUE,
      globalStatus: 'Correcto',
      csv: $csv,
      recordResults: $recordResults,
      acceptedCount: $acceptedCount,
      rejectedCount: 0,
      rawXml: $rawXml,
    );
  }

  /**
   * Creates a partial success response (some records rejected).
   */
  public static function partial(
    string $csv,
    array $recordResults,
    int $acceptedCount,
    int $rejectedCount,
    string $rawXml,
  ): self {
    return new self(
      isSuccess: FALSE,
      globalStatus: 'ParcialmenteCorrecto',
      csv: $csv,
      recordResults: $recordResults,
      acceptedCount: $acceptedCount,
      rejectedCount: $rejectedCount,
      rawXml: $rawXml,
    );
  }

  /**
   * Creates an error response.
   */
  public static function error(string $errorMessage, string $rawXml = ''): self {
    return new self(
      isSuccess: FALSE,
      globalStatus: 'Incorrecto',
      errorMessage: $errorMessage,
      rawXml: $rawXml,
    );
  }

  /**
   * Returns a summary array for JSON serialization.
   */
  public function toArray(): array {
    return [
      'is_success' => $this->isSuccess,
      'global_status' => $this->globalStatus,
      'csv' => $this->csv,
      'accepted_count' => $this->acceptedCount,
      'rejected_count' => $this->rejectedCount,
      'record_results' => $this->recordResults,
      'error_message' => $this->errorMessage,
    ];
  }

}
