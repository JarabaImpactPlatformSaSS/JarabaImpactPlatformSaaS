<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\WebSocket;

use Drupal\jaraba_messaging\Service\MessagingServiceInterface;
use Drupal\jaraba_messaging\Service\PresenceServiceInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

/**
 * Procesador de frames WebSocket de mensajería.
 *
 * PROPÓSITO:
 * Recibe mensajes JSON crudos desde el servidor WebSocket,
 * los parsea, valida el campo 'type' y despacha al handler
 * correspondiente (message.send, message.read, typing.start,
 * typing.stop, presence.heartbeat).
 *
 * FORMATO DE FRAME ENTRANTE:
 * {
 *   "type": "message.send",
 *   "data": { ... }
 * }
 *
 * FORMATO DE FRAME SALIENTE:
 * {
 *   "type": "message.new",
 *   "data": { ... },
 *   "timestamp": 1234567890
 * }
 */
class MessageHandler {

  public function __construct(
    protected MessagingServiceInterface $messagingService,
    protected PresenceServiceInterface $presenceService,
    protected ConnectionManager $connectionManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Handles an incoming WebSocket message frame.
   *
   * Parses the raw JSON, dispatches by 'type' field to the
   * appropriate handler method, and sends responses/broadcasts.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The source WebSocket connection.
   * @param string $rawMessage
   *   The raw JSON message string.
   * @param int $userId
   *   The authenticated user ID for this connection.
   * @param int $tenantId
   *   The tenant ID context for this connection.
   */
  public function handle(ConnectionInterface $conn, string $rawMessage, int $userId, int $tenantId): void {
    $frame = json_decode($rawMessage, TRUE);

    if (!is_array($frame) || !isset($frame['type'])) {
      $this->sendError($conn, 'INVALID_FRAME', 'Missing or invalid frame format.');
      return;
    }

    $type = $frame['type'];
    $data = $frame['data'] ?? [];

    try {
      match ($type) {
        'message.send' => $this->handleMessageSend($conn, $data, $userId, $tenantId),
        'message.read' => $this->handleMessageRead($conn, $data, $userId),
        'typing.start' => $this->handleTypingStart($conn, $data, $userId, $tenantId),
        'typing.stop' => $this->handleTypingStop($conn, $data, $userId, $tenantId),
        'presence.heartbeat' => $this->handlePresenceHeartbeat($conn, $userId, $tenantId),
        default => $this->sendError($conn, 'UNKNOWN_TYPE', "Unknown frame type: {$type}"),
      };
    }
    catch (\Throwable $e) {
      $this->logger->error('WebSocket message handler error for type @type: @error', [
        '@type' => $type,
        '@error' => $e->getMessage(),
      ]);
      $this->sendError($conn, 'INTERNAL_ERROR', 'An internal error occurred processing your request.');
    }
  }

  /**
   * Handles message.send frames.
   *
   * Delegates to MessagingService, then broadcasts the new message
   * to all conversation participants.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The sender's connection.
   * @param array $data
   *   Frame data: conversation_uuid, body, message_type, reply_to_id.
   * @param int $userId
   *   The sender's user ID.
   * @param int $tenantId
   *   The tenant ID context.
   */
  protected function handleMessageSend(ConnectionInterface $conn, array $data, int $userId, int $tenantId): void {
    if (empty($data['conversation_uuid']) || empty($data['body'])) {
      $this->sendError($conn, 'VALIDATION', 'conversation_uuid and body are required.');
      return;
    }

    $messageData = $this->messagingService->sendMessage(
      $data['conversation_uuid'],
      $data['body'],
      $data['message_type'] ?? 'text',
      isset($data['reply_to_id']) ? (int) $data['reply_to_id'] : NULL,
      $data['attachment_ids'] ?? [],
    );

    // Clear typing indicator for sender.
    if (isset($data['conversation_id'])) {
      $this->presenceService->clearTyping($userId, (int) $data['conversation_id']);
    }

    // Build the outbound frame.
    $outboundFrame = json_encode([
      'type' => 'message.new',
      'data' => $messageData,
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR);

    // Get participant IDs for broadcast.
    $participantIds = $this->getConversationParticipantIds($data['conversation_uuid']);

    // Broadcast to all participants (including sender for confirmation).
    $this->connectionManager->broadcast($participantIds, $outboundFrame);

    $this->logger->debug('Message sent by user @uid in conversation @conv via WebSocket.', [
      '@uid' => $userId,
      '@conv' => $data['conversation_uuid'],
    ]);
  }

  /**
   * Handles message.read frames.
   *
   * Marks conversation as read and notifies other participants.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The reader's connection.
   * @param array $data
   *   Frame data: conversation_uuid.
   * @param int $userId
   *   The reader's user ID.
   */
  protected function handleMessageRead(ConnectionInterface $conn, array $data, int $userId): void {
    if (empty($data['conversation_uuid'])) {
      $this->sendError($conn, 'VALIDATION', 'conversation_uuid is required.');
      return;
    }

    $count = $this->messagingService->markConversationRead($data['conversation_uuid']);

    // Notify participants about the read receipt.
    $outboundFrame = json_encode([
      'type' => 'message.read_receipt',
      'data' => [
        'conversation_uuid' => $data['conversation_uuid'],
        'user_id' => $userId,
        'read_count' => $count,
      ],
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR);

    $participantIds = $this->getConversationParticipantIds($data['conversation_uuid']);
    $this->connectionManager->broadcast($participantIds, $outboundFrame, $userId);
  }

  /**
   * Handles typing.start frames.
   *
   * Sets the typing indicator and broadcasts to conversation participants.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The typer's connection.
   * @param array $data
   *   Frame data: conversation_id.
   * @param int $userId
   *   The user ID who started typing.
   * @param int $tenantId
   *   The tenant ID context.
   */
  protected function handleTypingStart(ConnectionInterface $conn, array $data, int $userId, int $tenantId): void {
    if (empty($data['conversation_id'])) {
      $this->sendError($conn, 'VALIDATION', 'conversation_id is required.');
      return;
    }

    $conversationId = (int) $data['conversation_id'];
    $this->presenceService->setTyping($userId, $conversationId);

    $outboundFrame = json_encode([
      'type' => 'typing.indicator',
      'data' => [
        'conversation_id' => $conversationId,
        'user_id' => $userId,
        'is_typing' => TRUE,
      ],
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR);

    $participantIds = $data['participant_ids'] ?? [];
    if (!empty($participantIds)) {
      $this->connectionManager->broadcast($participantIds, $outboundFrame, $userId);
    }
  }

  /**
   * Handles typing.stop frames.
   *
   * Clears the typing indicator and broadcasts to conversation participants.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The typer's connection.
   * @param array $data
   *   Frame data: conversation_id.
   * @param int $userId
   *   The user ID who stopped typing.
   * @param int $tenantId
   *   The tenant ID context.
   */
  protected function handleTypingStop(ConnectionInterface $conn, array $data, int $userId, int $tenantId): void {
    if (empty($data['conversation_id'])) {
      $this->sendError($conn, 'VALIDATION', 'conversation_id is required.');
      return;
    }

    $conversationId = (int) $data['conversation_id'];
    $this->presenceService->clearTyping($userId, $conversationId);

    $outboundFrame = json_encode([
      'type' => 'typing.indicator',
      'data' => [
        'conversation_id' => $conversationId,
        'user_id' => $userId,
        'is_typing' => FALSE,
      ],
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR);

    $participantIds = $data['participant_ids'] ?? [];
    if (!empty($participantIds)) {
      $this->connectionManager->broadcast($participantIds, $outboundFrame, $userId);
    }
  }

  /**
   * Handles presence.heartbeat frames.
   *
   * Refreshes the user's online status.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The connection sending the heartbeat.
   * @param int $userId
   *   The user ID.
   * @param int $tenantId
   *   The tenant ID context.
   */
  protected function handlePresenceHeartbeat(ConnectionInterface $conn, int $userId, int $tenantId): void {
    $this->presenceService->setOnline($userId, $tenantId);

    // Acknowledge the heartbeat.
    $conn->send(json_encode([
      'type' => 'presence.heartbeat_ack',
      'data' => ['status' => 'ok'],
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Sends an error frame to a specific connection.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The target connection.
   * @param string $code
   *   The error code.
   * @param string $message
   *   The error message.
   */
  protected function sendError(ConnectionInterface $conn, string $code, string $message): void {
    $conn->send(json_encode([
      'type' => 'error',
      'data' => [
        'code' => $code,
        'message' => $message,
      ],
      'timestamp' => time(),
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Gets participant user IDs for a conversation by UUID.
   *
   * Uses the ConversationService via MessagingService to resolve
   * participants. Returns empty array if the conversation is not found.
   *
   * @param string $conversationUuid
   *   The conversation UUID.
   *
   * @return int[]
   *   Array of participant user IDs.
   */
  protected function getConversationParticipantIds(string $conversationUuid): array {
    try {
      // Access conversation service through the container.
      if (!\Drupal::hasService('jaraba_messaging.conversation')) {
        return [];
      }

      /** @var \Drupal\jaraba_messaging\Service\ConversationServiceInterface $conversationService */
      $conversationService = \Drupal::service('jaraba_messaging.conversation');
      $conversation = $conversationService->getByUuid($conversationUuid);

      if ($conversation === NULL) {
        return [];
      }

      $participants = $conversationService->getParticipants((int) $conversation->id());
      $ids = [];
      foreach ($participants as $participant) {
        $ids[] = $participant->getUserId();
      }

      return $ids;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Failed to resolve participants for conversation @uuid: @error', [
        '@uuid' => $conversationUuid,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
