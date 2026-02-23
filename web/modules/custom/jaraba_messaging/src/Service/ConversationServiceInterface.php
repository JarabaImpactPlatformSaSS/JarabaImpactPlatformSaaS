<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\jaraba_messaging\Entity\SecureConversationInterface;

/**
 * Interface para el servicio de gestión de conversaciones seguras.
 */
interface ConversationServiceInterface {

  /**
   * Creates a new secure conversation.
   *
   * @param int $tenantId
   *   The tenant ID for multi-tenancy isolation.
   * @param int $initiatedBy
   *   The user ID of the conversation initiator.
   * @param array $participantIds
   *   Array of user IDs to add as participants.
   * @param string $title
   *   The conversation title.
   * @param string $conversationType
   *   The conversation type (direct, group, support).
   * @param string $contextType
   *   The context type (general, case, booking, employment, mentoring, commerce).
   * @param string|null $contextId
   *   Optional context entity ID for linking to a specific entity.
   *
   * @return \Drupal\jaraba_messaging\Entity\SecureConversationInterface
   *   The created conversation entity.
   */
  public function create(int $tenantId, int $initiatedBy, array $participantIds, string $title, string $conversationType = 'direct', string $contextType = 'general', ?string $contextId = NULL): SecureConversationInterface;

  /**
   * Gets a conversation by its entity ID.
   *
   * @param int $id
   *   The conversation entity ID.
   *
   * @return \Drupal\jaraba_messaging\Entity\SecureConversationInterface|null
   *   The conversation entity, or NULL if not found.
   */
  public function getById(int $id): ?SecureConversationInterface;

  /**
   * Gets a conversation by its UUID.
   *
   * @param string $uuid
   *   The conversation UUID.
   *
   * @return \Drupal\jaraba_messaging\Entity\SecureConversationInterface|null
   *   The conversation entity, or NULL if not found.
   */
  public function getByUuid(string $uuid): ?SecureConversationInterface;

  /**
   * Lists conversations for a given user within a tenant.
   *
   * @param int $userId
   *   The user ID to list conversations for.
   * @param int $tenantId
   *   The tenant ID for multi-tenancy isolation.
   * @param string $status
   *   Filter by conversation status (active, archived, closed).
   * @param int $limit
   *   Maximum number of conversations to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of SecureConversationInterface entities.
   */
  public function listForUser(int $userId, int $tenantId, string $status = 'active', int $limit = 50, int $offset = 0): array;

  /**
   * Closes a conversation, preventing further messages.
   *
   * @param int $conversationId
   *   The conversation entity ID to close.
   */
  public function close(int $conversationId): void;

  /**
   * Archives a conversation for a specific user.
   *
   * @param int $conversationId
   *   The conversation entity ID to archive.
   * @param int $userId
   *   The user ID performing the archive action.
   */
  public function archive(int $conversationId, int $userId): void;

  /**
   * Gets all participants of a conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   *
   * @return array
   *   Array of participant data including user IDs and roles.
   */
  public function getParticipants(int $conversationId): array;

  /**
   * Adds a participant to a conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $userId
   *   The user ID to add as participant.
   * @param string $role
   *   The participant role (e.g., 'participant', 'admin', 'observer').
   */
  public function addParticipant(int $conversationId, int $userId, string $role = 'participant'): void;

  /**
   * Removes a participant from a conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $userId
   *   The user ID to remove.
   * @param int $removedBy
   *   The user ID performing the removal.
   */
  public function removeParticipant(int $conversationId, int $userId, int $removedBy): void;

  /**
   * Checks whether a user is a participant in a conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $userId
   *   The user ID to check.
   *
   * @return bool
   *   TRUE if the user is a participant, FALSE otherwise.
   */
  public function isParticipant(int $conversationId, int $userId): bool;

  /**
   * Automatically closes conversations inactive for the given number of days.
   *
   * @param int $days
   *   Number of days of inactivity after which to close conversations.
   *
   * @return int
   *   The number of conversations that were closed.
   */
  public function autoCloseInactive(int $days): int;

  /**
   * Finds an existing direct conversation between two users within a tenant.
   *
   * @param int $tenantId
   *   The tenant ID for multi-tenancy isolation.
   * @param int $userA
   *   The first user ID.
   * @param int $userB
   *   The second user ID.
   *
   * @return \Drupal\jaraba_messaging\Entity\SecureConversationInterface|null
   *   The existing direct conversation, or NULL if none exists.
   */
  public function findExistingDirect(int $tenantId, int $userA, int $userB): ?SecureConversationInterface;

}
