<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for the MobileDevice entity.
 */
interface MobileDeviceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the device token.
   *
   * @return string
   *   The device push token.
   */
  public function getDeviceToken(): string;

  /**
   * Gets the platform (ios or android).
   *
   * @return string
   *   The device platform.
   */
  public function getPlatform(): string;

  /**
   * Checks if push notifications are enabled for this device.
   *
   * @return bool
   *   TRUE if push is enabled.
   */
  public function isPushEnabled(): bool;

  /**
   * Checks if the device is active.
   *
   * @return bool
   *   TRUE if device is active.
   */
  public function isActive(): bool;

}
