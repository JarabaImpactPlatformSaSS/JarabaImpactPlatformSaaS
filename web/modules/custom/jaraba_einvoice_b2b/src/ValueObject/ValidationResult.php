<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Result of e-invoice XML validation.
 *
 * Aggregates results from 4 validation layers:
 *   1. XSD schema validation
 *   2. EN 16931 Schematron rules
 *   3. Spanish CIUS rules
 *   4. Business rules (NIF, IBAN, totals)
 *
 * Spec: Doc 181, Section 3.5.
 */
final class ValidationResult {

  public function __construct(
    public readonly bool $valid,
    public readonly array $errors,
    public readonly array $warnings,
    public readonly string $layer,
  ) {}

  /**
   * Creates a valid result.
   */
  public static function valid(string $layer = 'complete'): self {
    return new self(
      valid: TRUE,
      errors: [],
      warnings: [],
      layer: $layer,
    );
  }

  /**
   * Creates an invalid result with errors.
   */
  public static function invalid(array $errors, string $layer = 'complete', array $warnings = []): self {
    return new self(
      valid: FALSE,
      errors: $errors,
      warnings: $warnings,
      layer: $layer,
    );
  }

  /**
   * Merges another result into this one.
   *
   * @param \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult $other
   *   Another validation result.
   *
   * @return self
   *   Merged result.
   */
  public function merge(ValidationResult $other): self {
    return new self(
      valid: $this->valid && $other->valid,
      errors: array_merge($this->errors, $other->errors),
      warnings: array_merge($this->warnings, $other->warnings),
      layer: 'complete',
    );
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'valid' => $this->valid,
      'errors' => $this->errors,
      'warnings' => $this->warnings,
      'layer' => $this->layer,
      'error_count' => count($this->errors),
      'warning_count' => count($this->warnings),
    ];
  }

}
