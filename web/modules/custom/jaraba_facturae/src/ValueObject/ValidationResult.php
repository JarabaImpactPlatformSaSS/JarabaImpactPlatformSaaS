<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ValueObject;

/**
 * Resultado inmutable de una validacion Facturae.
 *
 * Usado por FacturaeValidationService para devolver resultados
 * de validacion XSD, NIF, IBAN, DIR3 e importes.
 *
 * Spec: Doc 180, Seccion 3.6.
 * Plan: FASE 6, entregable F6-6.
 */
final class ValidationResult {

  /**
   * @param bool $valid
   *   Whether the validation passed.
   * @param array $errors
   *   List of error messages (empty if valid).
   */
  public function __construct(
    public readonly bool $valid,
    public readonly array $errors = [],
  ) {}

  /**
   * Creates a successful validation result.
   */
  public static function success(): self {
    return new self(TRUE, []);
  }

  /**
   * Creates a failed validation result.
   *
   * @param array $errors
   *   List of error messages.
   */
  public static function failure(array $errors): self {
    return new self(FALSE, $errors);
  }

  /**
   * Returns the result as an array.
   */
  public function toArray(): array {
    return [
      'valid' => $this->valid,
      'errors' => $this->errors,
    ];
  }

}
