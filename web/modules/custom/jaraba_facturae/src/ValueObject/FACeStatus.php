<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ValueObject;

/**
 * Estado inmutable de una factura en FACe.
 *
 * Spec: Doc 180, Seccion 3.3.4.
 */
final class FACeStatus {

  /**
   * FACe lifecycle status codes.
   */
  public const STATUS_REGISTERED = '1200';
  public const STATUS_REGISTERED_RCF = '1300';
  public const STATUS_ACCOUNTED = '2400';
  public const STATUS_OBLIGATION_RECOGNIZED = '2500';
  public const STATUS_PAID = '2600';
  public const STATUS_CANCELLATION_REQUESTED = '3100';
  public const STATUS_CANCELLATION_ACCEPTED = '3200';
  public const STATUS_CANCELLATION_REJECTED = '3300';

  public function __construct(
    public readonly string $registryNumber,
    public readonly string $tramitacionCode,
    public readonly string $tramitacionDescription,
    public readonly string $tramitacionMotivo,
    public readonly string $anulacionCode,
    public readonly string $anulacionDescription,
    public readonly string $anulacionMotivo,
  ) {}

  /**
   * Returns the Drupal entity face_status value for this FACe status.
   */
  public function toEntityStatus(): string {
    return match ($this->tramitacionCode) {
      self::STATUS_REGISTERED => 'registered',
      self::STATUS_REGISTERED_RCF => 'registered_rcf',
      self::STATUS_ACCOUNTED => 'accounted',
      self::STATUS_OBLIGATION_RECOGNIZED => 'obligation_recognized',
      self::STATUS_PAID => 'paid',
      default => 'sent',
    };
  }

  /**
   * Checks if there is a pending cancellation.
   */
  public function hasCancellation(): bool {
    return !empty($this->anulacionCode);
  }

  /**
   * Returns the result as an array.
   */
  public function toArray(): array {
    return [
      'registry_number' => $this->registryNumber,
      'tramitacion' => [
        'code' => $this->tramitacionCode,
        'description' => $this->tramitacionDescription,
        'motivo' => $this->tramitacionMotivo,
      ],
      'anulacion' => [
        'code' => $this->anulacionCode,
        'description' => $this->anulacionDescription,
        'motivo' => $this->anulacionMotivo,
      ],
    ];
  }

}
