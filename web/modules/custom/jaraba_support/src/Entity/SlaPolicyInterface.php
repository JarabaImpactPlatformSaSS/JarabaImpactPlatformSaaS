<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for SLA Policy config entities.
 */
interface SlaPolicyInterface extends ConfigEntityInterface {

  /**
   * Gets the plan tier (starter, professional, enterprise, institutional).
   */
  public function getPlanTier(): string;

  /**
   * Gets the priority level.
   */
  public function getPriority(): string;

  /**
   * Gets the first response hours.
   */
  public function getFirstResponseHours(): int;

  /**
   * Gets the resolution hours.
   */
  public function getResolutionHours(): int;

  /**
   * Whether to only count business hours.
   */
  public function isBusinessHoursOnly(): bool;

  /**
   * Whether SLA pauses when ticket is pending customer.
   */
  public function isPauseOnPending(): bool;

}
