<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Exception;

/**
 * Exception thrown when e-invoice validation fails.
 *
 * Contains the validation errors from all 4 layers.
 */
class EInvoiceValidationException extends \RuntimeException {

  /**
   * @param array $errors
   *   List of validation error strings.
   * @param string $layer
   *   The validation layer that failed: xsd, schematron, cius, business.
   */
  public function __construct(
    protected array $errors = [],
    protected string $layer = 'unknown',
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    if (empty($message)) {
      $message = sprintf('E-Invoice validation failed at layer "%s": %s', $layer, implode('; ', $errors));
    }
    parent::__construct($message, $code, $previous);
  }

  public function getErrors(): array {
    return $this->errors;
  }

  public function getLayer(): string {
    return $this->layer;
  }

}
