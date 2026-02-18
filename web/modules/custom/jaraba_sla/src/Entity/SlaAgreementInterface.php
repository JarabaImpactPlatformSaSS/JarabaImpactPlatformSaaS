<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for SLA Agreement entities.
 */
interface SlaAgreementInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the SLA tier.
   *
   * @return string
   *   The SLA tier (standard, premium, critical).
   */
  public function getSlaTier(): string;

  /**
   * Gets the uptime target percentage.
   *
   * @return float
   *   The uptime target (e.g. 99.900).
   */
  public function getUptimeTarget(): float;

  /**
   * Gets the credit policy as decoded array.
   *
   * @return array
   *   The credit policy thresholds.
   */
  public function getCreditPolicy(): array;

  /**
   * Gets whether the agreement is active.
   *
   * @return bool
   *   TRUE if the agreement is active.
   */
  public function isActive(): bool;

  /**
   * Gets the tenant ID.
   *
   * @return int|null
   *   The tenant entity ID.
   */
  public function getTenantId(): ?int;

}
