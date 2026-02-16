<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Exception;

/**
 * Exception thrown when AEAT SOAP communication fails.
 *
 * Covers network errors, SOAP faults, invalid responses, and
 * authentication failures with the AEAT VeriFactu service.
 *
 * The remision service handles retries with exponential backoff.
 * This exception is logged as a VeriFactuEventLog AEAT_RESPONSE event.
 *
 * @see \Drupal\jaraba_verifactu\Service\VeriFactuRemisionService
 */
class VeriFactuAeatCommunicationException extends \RuntimeException {

  /**
   * Constructs a VeriFactuAeatCommunicationException.
   *
   * @param string $message
   *   The error message.
   * @param string $soapAction
   *   The SOAP action that was being executed.
   * @param string $responseCode
   *   The AEAT response code, if available.
   * @param string $responseXml
   *   The raw AEAT response XML, if available.
   * @param int $httpStatusCode
   *   The HTTP status code of the response.
   * @param \Throwable|null $previous
   *   The previous exception, if any.
   */
  public function __construct(
    string $message,
    public readonly string $soapAction = '',
    public readonly string $responseCode = '',
    public readonly string $responseXml = '',
    public readonly int $httpStatusCode = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, 0, $previous);
  }

}
