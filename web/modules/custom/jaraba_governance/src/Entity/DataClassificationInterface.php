<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for DataClassification entities.
 *
 * Represents the data classification level assigned to an entity type
 * or a specific field within an entity type.
 */
interface DataClassificationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the classified entity type ID.
   */
  public function getEntityTypeClassified(): string;

  /**
   * Gets the classified field name, or NULL for entire entity.
   */
  public function getFieldName(): ?string;

  /**
   * Gets the classification level (C1_PUBLIC, C2_INTERNAL, etc.).
   */
  public function getClassificationLevel(): string;

  /**
   * Whether this data is PII.
   */
  public function isPii(): bool;

  /**
   * Whether this data is sensitive (GDPR Art. 9).
   */
  public function isSensitive(): bool;

  /**
   * Gets the retention period in days.
   */
  public function getRetentionDays(): ?int;

  /**
   * Whether encryption is required.
   */
  public function isEncryptionRequired(): bool;

  /**
   * Whether masking is required for staging.
   */
  public function isMaskingRequired(): bool;

  /**
   * Whether cross-border transfer is allowed.
   */
  public function isCrossBorderAllowed(): bool;

  /**
   * Gets the legal basis.
   */
  public function getLegalBasis(): ?string;

  /**
   * Gets the creation timestamp.
   */
  public function getCreatedTime(): int;

}
