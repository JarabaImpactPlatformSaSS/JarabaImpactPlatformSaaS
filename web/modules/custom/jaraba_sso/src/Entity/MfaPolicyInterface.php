<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for MFA Policy entities.
 *
 * Defines the contract for per-tenant MFA enforcement policies
 * including allowed methods, grace periods, and session controls.
 */
interface MfaPolicyInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int;

  /**
   * Gets the enforcement level (disabled, admins_only, required).
   */
  public function getEnforcement(): string;

  /**
   * Gets the allowed MFA methods as an array.
   */
  public function getAllowedMethods(): array;

  /**
   * Gets the grace period in days.
   */
  public function getGracePeriodDays(): int;

  /**
   * Gets the session duration in hours.
   */
  public function getSessionDurationHours(): int;

  /**
   * Gets the maximum concurrent sessions.
   */
  public function getMaxConcurrentSessions(): int;

  /**
   * Whether the policy is active.
   */
  public function isActive(): bool;

}
