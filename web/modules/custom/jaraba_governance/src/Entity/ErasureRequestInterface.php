<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for ErasureRequest entities.
 *
 * Models GDPR data subject requests: erasure (Art. 17),
 * rectification (Art. 16), portability (Art. 20), access (Art. 15).
 */
interface ErasureRequestInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the requester user ID.
   */
  public function getRequesterId(): int;

  /**
   * Gets the subject user ID (whose data to process).
   */
  public function getSubjectUserId(): int;

  /**
   * Gets the request type.
   */
  public function getRequestType(): string;

  /**
   * Gets the current status.
   */
  public function getStatus(): string;

  /**
   * Sets the status.
   */
  public function setStatus(string $status): self;

  /**
   * Gets the reason for the request.
   */
  public function getReason(): ?string;

  /**
   * Gets affected entities as decoded array.
   */
  public function getEntitiesAffectedArray(): array;

  /**
   * Sets the entities affected (JSON).
   */
  public function setEntitiesAffected(array $entities): self;

  /**
   * Gets the processed timestamp.
   */
  public function getProcessedAt(): ?string;

  /**
   * Gets the user ID of who processed the request.
   */
  public function getProcessedById(): ?int;

  /**
   * Gets internal notes.
   */
  public function getNotes(): ?string;

  /**
   * Gets the creation timestamp.
   */
  public function getCreatedTime(): int;

}
