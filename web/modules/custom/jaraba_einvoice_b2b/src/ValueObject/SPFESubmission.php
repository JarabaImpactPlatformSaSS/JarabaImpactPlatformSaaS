<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Result of submitting an invoice to the SPFE.
 *
 * Immutable value object returned by SPFEClientInterface::submitInvoice().
 *
 * Spec: Doc 181, Section 3.6.
 */
final class SPFESubmission {

  public function __construct(
    public readonly bool $success,
    public readonly ?string $submissionId,
    public readonly string $status,
    public readonly ?string $errorCode,
    public readonly ?string $errorMessage,
    public readonly ?string $timestamp,
    public readonly ?array $rawResponse,
  ) {}

  /**
   * Creates a successful submission.
   */
  public static function accepted(string $submissionId, ?string $timestamp = NULL): self {
    return new self(
      success: TRUE,
      submissionId: $submissionId,
      status: 'accepted',
      errorCode: NULL,
      errorMessage: NULL,
      timestamp: $timestamp ?? date('c'),
      rawResponse: NULL,
    );
  }

  /**
   * Creates a rejected submission.
   */
  public static function rejected(string $errorCode, string $errorMessage): self {
    return new self(
      success: FALSE,
      submissionId: NULL,
      status: 'rejected',
      errorCode: $errorCode,
      errorMessage: $errorMessage,
      timestamp: date('c'),
      rawResponse: NULL,
    );
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'success' => $this->success,
      'submission_id' => $this->submissionId,
      'status' => $this->status,
      'error_code' => $this->errorCode,
      'error_message' => $this->errorMessage,
      'timestamp' => $this->timestamp,
    ];
  }

}
