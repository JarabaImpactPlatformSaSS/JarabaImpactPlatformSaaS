<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Exception;

/**
 * Exception thrown when e-invoice delivery fails.
 */
class EInvoiceDeliveryException extends \RuntimeException {

  /**
   * @param string $channel
   *   The delivery channel that failed: spfe, email, peppol, platform.
   */
  public function __construct(
    protected string $channel = 'unknown',
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    if (empty($message)) {
      $message = sprintf('E-Invoice delivery failed via channel "%s".', $channel);
    }
    parent::__construct($message, $code, $previous);
  }

  public function getChannel(): string {
    return $this->channel;
  }

}
