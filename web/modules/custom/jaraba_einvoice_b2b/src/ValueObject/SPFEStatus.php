<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Status of a submission in the SPFE.
 *
 * Returned by SPFEClientInterface::querySubmission().
 *
 * Spec: Doc 181, Section 3.6.
 */
final class SPFEStatus {

  public function __construct(
    public readonly string $submissionId,
    public readonly string $status,
    public readonly ?string $lastUpdated,
    public readonly ?string $errorCode,
    public readonly ?string $errorMessage,
    public readonly ?array $rawResponse,
  ) {}

  /**
   * Creates a status from an API response.
   */
  public static function fromResponse(string $submissionId, string $status, ?string $lastUpdated = NULL): self {
    return new self(
      submissionId: $submissionId,
      status: $status,
      lastUpdated: $lastUpdated ?? date('c'),
      errorCode: NULL,
      errorMessage: NULL,
      rawResponse: NULL,
    );
  }

  /**
   * Whether the submission has been accepted.
   */
  public function isAccepted(): bool {
    return $this->status === 'accepted';
  }

  /**
   * Whether the submission is still pending.
   */
  public function isPending(): bool {
    return in_array($this->status, ['pending', 'processing'], TRUE);
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'submission_id' => $this->submissionId,
      'status' => $this->status,
      'last_updated' => $this->lastUpdated,
      'error_code' => $this->errorCode,
      'error_message' => $this->errorMessage,
    ];
  }

}
