<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface para la entidad SecureConversation.
 */
interface SecureConversationInterface extends ContentEntityInterface, EntityChangedInterface {

  public const STATUS_ACTIVE = 'active';
  public const STATUS_ARCHIVED = 'archived';
  public const STATUS_CLOSED = 'closed';
  public const STATUS_DELETED = 'deleted';

  public const TYPE_DIRECT = 'direct';
  public const TYPE_GROUP = 'group';
  public const TYPE_SUPPORT = 'support';

  public const CONTEXT_GENERAL = 'general';
  public const CONTEXT_CASE = 'case';
  public const CONTEXT_BOOKING = 'booking';
  public const CONTEXT_EMPLOYMENT = 'employment';
  public const CONTEXT_MENTORING = 'mentoring';
  public const CONTEXT_COMMERCE = 'commerce';

  /**
   * Gets the conversation title.
   */
  public function getTitle(): string;

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int;

  /**
   * Gets the conversation type.
   */
  public function getConversationType(): string;

  /**
   * Gets the status.
   */
  public function getStatus(): string;

  /**
   * Gets the user who initiated the conversation.
   */
  public function getInitiatedBy(): ?int;

  /**
   * Returns TRUE if the conversation is confidential (excluded from AI).
   */
  public function isConfidential(): bool;

  /**
   * Returns TRUE if the conversation is active.
   */
  public function isActive(): bool;

  /**
   * Gets the last message timestamp.
   */
  public function getLastMessageAt(): ?int;

  /**
   * Gets the message count.
   */
  public function getMessageCount(): int;

  /**
   * Gets the participant count.
   */
  public function getParticipantCount(): int;

}
