<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Exception;

/**
 * Exception thrown when SPFE connection fails.
 *
 * The SPFE API is not yet published by AEAT. This exception will be
 * used when the live client encounters connectivity issues.
 */
class SPFEConnectionException extends \RuntimeException {

  public function __construct(
    string $message = 'SPFE connection failed.',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

}
