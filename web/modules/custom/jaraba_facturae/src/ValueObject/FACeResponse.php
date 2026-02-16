<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ValueObject;

/**
 * Resultado inmutable de una operacion FACe.
 *
 * Spec: Doc 180, Seccion 3.3.
 */
final class FACeResponse {

  public function __construct(
    public readonly bool $success,
    public readonly string $code,
    public readonly string $description,
    public readonly string $registryNumber,
    public readonly string $csv,
    public readonly array $rawResponse,
  ) {}

  /**
   * Creates a successful response.
   */
  public static function success(string $code, string $description, string $registryNumber = '', string $csv = '', array $raw = []): self {
    return new self(TRUE, $code, $description, $registryNumber, $csv, $raw);
  }

  /**
   * Creates an error response.
   */
  public static function error(string $code, string $description, array $raw = []): self {
    return new self(FALSE, $code, $description, '', '', $raw);
  }

  /**
   * Returns the result as an array.
   */
  public function toArray(): array {
    return [
      'success' => $this->success,
      'code' => $this->code,
      'description' => $this->description,
      'registry_number' => $this->registryNumber,
      'csv' => $this->csv,
    ];
  }

}
