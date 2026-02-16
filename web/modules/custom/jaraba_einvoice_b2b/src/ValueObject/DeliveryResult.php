<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ValueObject;

/**
 * Result of a document delivery attempt.
 *
 * Immutable value object returned by EInvoiceDeliveryService::deliver().
 * Records the outcome of sending an e-invoice through any channel.
 *
 * Spec: Doc 181, Section 3.3.
 */
final class DeliveryResult {

  public function __construct(
    public readonly bool $success,
    public readonly string $channel,
    public readonly string $status,
    public readonly ?string $messageId,
    public readonly ?string $errorMessage,
    public readonly ?int $httpStatus,
    public readonly ?int $durationMs,
    public readonly ?array $metadata,
  ) {}

  /**
   * Creates a successful result.
   */
  public static function success(string $channel, ?string $messageId = NULL, ?array $metadata = NULL): self {
    return new self(
      success: TRUE,
      channel: $channel,
      status: 'delivered',
      messageId: $messageId,
      errorMessage: NULL,
      httpStatus: 200,
      durationMs: NULL,
      metadata: $metadata,
    );
  }

  /**
   * Creates a failed result.
   */
  public static function failure(string $channel, string $errorMessage, ?int $httpStatus = NULL): self {
    return new self(
      success: FALSE,
      channel: $channel,
      status: 'failed',
      messageId: NULL,
      errorMessage: $errorMessage,
      httpStatus: $httpStatus,
      durationMs: NULL,
      metadata: NULL,
    );
  }

  /**
   * Exports to associative array.
   */
  public function toArray(): array {
    return [
      'success' => $this->success,
      'channel' => $this->channel,
      'status' => $this->status,
      'message_id' => $this->messageId,
      'error_message' => $this->errorMessage,
      'http_status' => $this->httpStatus,
      'duration_ms' => $this->durationMs,
      'metadata' => $this->metadata,
    ];
  }

}
