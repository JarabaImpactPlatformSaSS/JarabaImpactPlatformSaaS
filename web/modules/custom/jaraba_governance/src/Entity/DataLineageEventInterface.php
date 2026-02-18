<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for DataLineageEvent entities.
 *
 * Append-only audit trail: records every data lifecycle event
 * (created, updated, read, exported, deleted, anonymized, transferred).
 */
interface DataLineageEventInterface extends ContentEntityInterface {

  /**
   * Gets the target entity type.
   */
  public function getTargetEntityType(): string;

  /**
   * Gets the target entity ID.
   */
  public function getTargetEntityId(): int;

  /**
   * Gets the event type.
   */
  public function getEventType(): string;

  /**
   * Gets the actor user ID.
   */
  public function getActorId(): ?int;

  /**
   * Gets the actor type (user, system, agent, api_client).
   */
  public function getActorType(): string;

  /**
   * Gets the source system identifier.
   */
  public function getSourceSystem(): ?string;

  /**
   * Gets the destination system identifier.
   */
  public function getDestinationSystem(): ?string;

  /**
   * Gets the event metadata as decoded array.
   */
  public function getMetadataArray(): array;

  /**
   * Gets the creation timestamp.
   */
  public function getCreatedTime(): int;

}
