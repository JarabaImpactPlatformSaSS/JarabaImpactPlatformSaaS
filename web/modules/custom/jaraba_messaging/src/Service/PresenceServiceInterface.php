<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

/**
 * Interface para el servicio de presencia en tiempo real.
 *
 * Gestiona el estado online/offline de usuarios y los indicadores
 * de escritura (typing) por conversación. Implementación base
 * en memoria; puede sustituirse por Redis en producción.
 */
interface PresenceServiceInterface {

  /**
   * Marks a user as online for a given tenant.
   *
   * @param int $userId
   *   The user ID to mark as online.
   * @param int $tenantId
   *   The tenant ID context.
   */
  public function setOnline(int $userId, int $tenantId): void;

  /**
   * Marks a user as offline.
   *
   * @param int $userId
   *   The user ID to mark as offline.
   */
  public function setOffline(int $userId): void;

  /**
   * Checks whether a user is currently online.
   *
   * @param int $userId
   *   The user ID to check.
   *
   * @return bool
   *   TRUE if the user is online and within TTL, FALSE otherwise.
   */
  public function isOnline(int $userId): bool;

  /**
   * Gets all online user IDs for a given tenant.
   *
   * @param int $tenantId
   *   The tenant ID to query.
   *
   * @return int[]
   *   Array of user IDs currently online for this tenant.
   */
  public function getOnlineUsers(int $tenantId): array;

  /**
   * Sets a user as typing in a specific conversation.
   *
   * @param int $userId
   *   The user ID who is typing.
   * @param int $conversationId
   *   The conversation ID where the user is typing.
   */
  public function setTyping(int $userId, int $conversationId): void;

  /**
   * Clears the typing indicator for a user in a conversation.
   *
   * @param int $userId
   *   The user ID to clear typing for.
   * @param int $conversationId
   *   The conversation ID to clear typing in.
   */
  public function clearTyping(int $userId, int $conversationId): void;

  /**
   * Gets all users currently typing in a conversation.
   *
   * Automatically expires typing indicators older than 5 seconds.
   *
   * @param int $conversationId
   *   The conversation ID to query.
   *
   * @return int[]
   *   Array of user IDs currently typing in this conversation.
   */
  public function getTypingUsers(int $conversationId): array;

}
