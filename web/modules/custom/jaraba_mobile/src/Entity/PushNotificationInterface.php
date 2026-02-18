<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the PushNotification entity.
 *
 * PushNotification is an APPEND-ONLY entity: once created, records
 * cannot be updated or deleted. This ensures an immutable audit trail.
 */
interface PushNotificationInterface extends ContentEntityInterface {

  /**
   * Gets the notification title.
   *
   * @return string
   *   The notification title.
   */
  public function getTitle(): string;

  /**
   * Gets the notification body.
   *
   * @return string
   *   The notification body text.
   */
  public function getBody(): string;

  /**
   * Gets the notification channel.
   *
   * @return string
   *   The channel (general, jobs, orders, alerts, marketing).
   */
  public function getChannel(): string;

  /**
   * Gets the delivery status.
   *
   * @return string
   *   The status (queued, sent, delivered, opened, failed).
   */
  public function getStatus(): string;

  /**
   * Gets the recipient user ID.
   *
   * @return int
   *   The recipient user ID.
   */
  public function getRecipientId(): int;

}
