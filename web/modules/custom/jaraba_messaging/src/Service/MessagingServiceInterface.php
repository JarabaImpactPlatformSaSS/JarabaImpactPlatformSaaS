<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

/**
 * Interface para el servicio orquestador de mensajería segura.
 *
 * Actúa como fachada de alto nivel sobre ConversationService y MessageService,
 * resolviendo el usuario y tenant actuales desde el contexto de sesión.
 */
interface MessagingServiceInterface {

  /**
   * Sends a message in an existing conversation.
   *
   * Resolves the current user and tenant from session context,
   * validates participation, and delegates to MessageService.
   *
   * @param string $conversationUuid
   *   The UUID of the target conversation.
   * @param string $body
   *   The plaintext message body.
   * @param string $messageType
   *   The message type (text, image, file, system).
   * @param int|null $replyToId
   *   Optional ID of the message being replied to.
   * @param array $attachmentIds
   *   Array of file entity IDs to attach to the message.
   *
   * @return array
   *   The serialized message data suitable for API responses.
   *
   * @throws \Drupal\jaraba_messaging\Exception\AccessDeniedException
   * @throws \Drupal\jaraba_messaging\Exception\RateLimitException
   */
  public function sendMessage(string $conversationUuid, string $body, string $messageType = 'text', ?int $replyToId = NULL, array $attachmentIds = []): array;

  /**
   * Creates a new conversation with the given participants.
   *
   * Resolves the current user and tenant from session context and
   * delegates to ConversationService.
   *
   * @param array $participantIds
   *   Array of user IDs to include as participants.
   * @param string $title
   *   The conversation title.
   * @param string $conversationType
   *   The conversation type (direct, group, support).
   * @param string $contextType
   *   The context type (general, case, booking, employment, mentoring, commerce).
   * @param string|null $contextId
   *   Optional context entity ID for linking to a specific entity.
   *
   * @return array
   *   The serialized conversation data suitable for API responses.
   */
  public function createConversation(array $participantIds, string $title, string $conversationType = 'direct', string $contextType = 'general', ?string $contextId = NULL): array;

  /**
   * Gets the current user's conversations.
   *
   * Resolves the current user and tenant from session context.
   *
   * @param string $status
   *   Filter by conversation status (active, archived, closed).
   * @param int $limit
   *   Maximum number of conversations to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of serialized conversation data suitable for API responses.
   */
  public function getConversations(string $status = 'active', int $limit = 50, int $offset = 0): array;

  /**
   * Gets messages from a conversation.
   *
   * Resolves the current user and tenant from session context,
   * validates participation, and delegates to MessageService.
   *
   * @param string $conversationUuid
   *   The UUID of the target conversation.
   * @param int $limit
   *   Maximum number of messages to return.
   * @param int|null $beforeId
   *   Optional message ID cursor; returns messages older than this ID.
   *
   * @return array
   *   Array of serialized message data suitable for API responses.
   *
   * @throws \Drupal\jaraba_messaging\Exception\AccessDeniedException
   */
  public function getMessages(string $conversationUuid, int $limit = 25, ?int $beforeId = NULL): array;

  /**
   * Marks all messages in a conversation as read for the current user.
   *
   * Resolves the current user from session context and delegates
   * to MessageService.
   *
   * @param string $conversationUuid
   *   The UUID of the target conversation.
   *
   * @return int
   *   The number of messages marked as read.
   *
   * @throws \Drupal\jaraba_messaging\Exception\AccessDeniedException
   */
  public function markConversationRead(string $conversationUuid): int;

}
