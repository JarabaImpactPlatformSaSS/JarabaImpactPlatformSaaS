<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Support Ticket entities.
 */
interface SupportTicketInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the ticket number (JRB-YYYYMM-NNNN).
   */
  public function getTicketNumber(): string;

  /**
   * Gets the ticket status.
   */
  public function getStatus(): string;

  /**
   * Gets the ticket priority.
   */
  public function getPriority(): string;

  /**
   * Gets the AI classification data.
   *
   * @return array
   *   Decoded JSON: {category, confidence, sentiment, urgency}.
   */
  public function getAiClassification(): array;

  /**
   * Gets the ticket tags.
   *
   * @return array
   *   Array of tag strings.
   */
  public function getTags(): array;

  /**
   * Checks if the ticket is in a resolved or closed state.
   */
  public function isResolved(): bool;

  /**
   * Checks if the SLA has been breached.
   */
  public function isSlaBreached(): bool;

}
