<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface para la entidad ConversationParticipant.
 */
interface ConversationParticipantInterface extends ContentEntityInterface, EntityChangedInterface {

  public const STATUS_ACTIVE = 'active';
  public const STATUS_LEFT = 'left';
  public const STATUS_REMOVED = 'removed';
  public const STATUS_BLOCKED = 'blocked';

  public const ROLE_OWNER = 'owner';
  public const ROLE_PARTICIPANT = 'participant';
  public const ROLE_OBSERVER = 'observer';

  public const NOTIFICATION_ALL = 'all';
  public const NOTIFICATION_MENTIONS = 'mentions';
  public const NOTIFICATION_NONE = 'none';

  /**
   * Gets the conversation ID.
   */
  public function getConversationId(): int;

  /**
   * Gets the user ID.
   */
  public function getUserId(): int;

  /**
   * Gets the participant role.
   */
  public function getRole(): string;

  /**
   * Returns TRUE if the participant can send messages.
   */
  public function canSend(): bool;

  /**
   * Returns TRUE if the participant can attach files.
   */
  public function canAttach(): bool;

  /**
   * Gets the unread message count.
   */
  public function getUnreadCount(): int;

  /**
   * Returns TRUE if the participant is active.
   */
  public function isActive(): bool;

  /**
   * Gets the status.
   */
  public function getStatus(): string;

}
