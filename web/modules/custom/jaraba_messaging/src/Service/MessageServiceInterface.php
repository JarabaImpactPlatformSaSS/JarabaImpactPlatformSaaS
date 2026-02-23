<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\jaraba_messaging\Model\SecureMessageDTO;

/**
 * Interface para el servicio de gestión de mensajes seguros.
 */
interface MessageServiceInterface {

  /**
   * Sends a new message in a conversation.
   *
   * Handles encryption, storage, and audit logging of the message.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $senderId
   *   The user ID of the message sender.
   * @param int $tenantId
   *   The tenant ID for multi-tenancy isolation and encryption.
   * @param string $body
   *   The plaintext message body to encrypt and store.
   * @param string $messageType
   *   The message type (text, image, file, system).
   * @param int|null $replyToId
   *   Optional ID of the message being replied to.
   * @param array $attachmentIds
   *   Array of file entity IDs to attach to the message.
   *
   * @return \Drupal\jaraba_messaging\Model\SecureMessageDTO
   *   The created message as a DTO with decrypted body.
   *
   * @throws \Drupal\jaraba_messaging\Exception\AccessDeniedException
   * @throws \Drupal\jaraba_messaging\Exception\RateLimitException
   * @throws \Drupal\jaraba_messaging\Exception\EncryptionException
   */
  public function send(int $conversationId, int $senderId, int $tenantId, string $body, string $messageType = 'text', ?int $replyToId = NULL, array $attachmentIds = []): SecureMessageDTO;

  /**
   * Gets messages for a conversation with cursor-based pagination.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $tenantId
   *   The tenant ID for decryption.
   * @param int $limit
   *   Maximum number of messages to return.
   * @param int|null $beforeId
   *   Optional message ID cursor; returns messages older than this ID.
   *
   * @return \Drupal\jaraba_messaging\Model\SecureMessageDTO[]
   *   Array of decrypted message DTOs ordered by creation time descending.
   *
   * @throws \Drupal\jaraba_messaging\Exception\DecryptionException
   */
  public function getMessages(int $conversationId, int $tenantId, int $limit = 25, ?int $beforeId = NULL): array;

  /**
   * Edits an existing message within the allowed edit window.
   *
   * @param int $messageId
   *   The message ID to edit.
   * @param int $tenantId
   *   The tenant ID for re-encryption.
   * @param string $newBody
   *   The new plaintext message body.
   *
   * @return \Drupal\jaraba_messaging\Model\SecureMessageDTO
   *   The updated message as a DTO with the new decrypted body.
   *
   * @throws \Drupal\jaraba_messaging\Exception\EditWindowExpiredException
   * @throws \Drupal\jaraba_messaging\Exception\EncryptionException
   */
  public function edit(int $messageId, int $tenantId, string $newBody): SecureMessageDTO;

  /**
   * Soft-deletes a message, preserving the audit trail.
   *
   * The message body is wiped but the record and metadata are retained
   * for audit integrity.
   *
   * @param int $messageId
   *   The message ID to soft-delete.
   * @param int $tenantId
   *   The tenant ID for audit logging.
   */
  public function softDelete(int $messageId, int $tenantId): void;

  /**
   * Marks messages as read up to a given message ID.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $userId
   *   The user ID marking messages as read.
   * @param int|null $upToMessageId
   *   Optional message ID up to which to mark as read. If NULL, marks
   *   all messages in the conversation as read.
   *
   * @return int
   *   The number of messages marked as read.
   */
  public function markRead(int $conversationId, int $userId, ?int $upToMessageId = NULL): int;

  /**
   * Adds an emoji reaction to a message.
   *
   * @param int $messageId
   *   The message ID to react to.
   * @param int $userId
   *   The user ID adding the reaction.
   * @param string $emoji
   *   The emoji character or shortcode.
   */
  public function addReaction(int $messageId, int $userId, string $emoji): void;

  /**
   * Removes an emoji reaction from a message.
   *
   * @param int $messageId
   *   The message ID to remove the reaction from.
   * @param int $userId
   *   The user ID removing the reaction.
   * @param string $emoji
   *   The emoji character or shortcode to remove.
   */
  public function removeReaction(int $messageId, int $userId, string $emoji): void;

}
