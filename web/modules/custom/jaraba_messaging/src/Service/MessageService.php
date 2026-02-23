<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_messaging\Exception\EditWindowExpiredException;
use Drupal\jaraba_messaging\Exception\RateLimitException;
use Drupal\jaraba_messaging\Model\EncryptedPayload;
use Drupal\jaraba_messaging\Model\SecureMessageDTO;
use Psr\Log\LoggerInterface;

/**
 * Servicio CRUD para mensajes cifrados (custom SQL).
 *
 * PROPÓSITO:
 * Gestiona mensajes en la tabla custom secure_message. Cifra al escribir,
 * descifra al leer. Implementa rate limiting, soft-delete, edit window,
 * reactions, y read receipts.
 */
class MessageService implements MessageServiceInterface {

  public function __construct(
    protected Connection $database,
    protected MessageEncryptionServiceInterface $encryptionService,
    protected MessageAuditServiceInterface $auditService,
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function send(int $conversationId, int $senderId, int $tenantId, string $body, string $messageType = 'text', ?int $replyToId = NULL, array $attachmentIds = []): SecureMessageDTO {
    // Rate limit check.
    $this->checkRateLimit($senderId, $conversationId, $tenantId);

    // Encrypt the message body.
    $payload = $this->encryptionService->encrypt($body, $tenantId);

    $createdAt = time();

    $id = $this->database->insert('secure_message')
      ->fields([
        'conversation_id' => $conversationId,
        'sender_id' => $senderId,
        'tenant_id' => $tenantId,
        'message_type' => $messageType,
        'body_encrypted' => $payload->ciphertext,
        'encryption_iv' => $payload->iv,
        'encryption_tag' => $payload->tag,
        'encryption_key_id' => $payload->keyId,
        'reply_to_id' => $replyToId,
        'attachment_ids' => !empty($attachmentIds) ? json_encode($attachmentIds) : NULL,
        'reactions' => NULL,
        'metadata' => NULL,
        'is_edited' => 0,
        'edited_at' => NULL,
        'is_deleted' => 0,
        'deleted_at' => NULL,
        'created_at' => $createdAt,
      ])
      ->execute();

    // Audit log.
    $this->auditService->log(
      $conversationId,
      $tenantId,
      'message.sent',
      (int) $id,
      ['type' => $messageType],
    );

    return new SecureMessageDTO(
      id: (int) $id,
      conversationId: $conversationId,
      senderId: $senderId,
      tenantId: $tenantId,
      messageType: $messageType,
      body: $body,
      replyToId: $replyToId,
      attachmentIds: $attachmentIds,
      reactions: [],
      metadata: [],
      isEdited: FALSE,
      editedAt: NULL,
      isDeleted: FALSE,
      deletedAt: NULL,
      createdAt: $createdAt,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(int $conversationId, int $tenantId, int $limit = 25, ?int $beforeId = NULL): array {
    $query = $this->database->select('secure_message', 'm')
      ->fields('m')
      ->condition('m.conversation_id', $conversationId)
      ->condition('m.tenant_id', $tenantId)
      ->orderBy('m.id', 'DESC')
      ->range(0, $limit);

    if ($beforeId !== NULL) {
      $query->condition('m.id', $beforeId, '<');
    }

    $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($rows as $row) {
      $body = '';
      if (!$row['is_deleted']) {
        $payload = new EncryptedPayload(
          ciphertext: $row['body_encrypted'],
          iv: $row['encryption_iv'],
          tag: $row['encryption_tag'],
          key_id: $row['encryption_key_id'],
        );
        try {
          $body = $this->encryptionService->decrypt($payload, $tenantId);
        }
        catch (\Throwable $e) {
          $this->logger->error('Failed to decrypt message @id: @error', [
            '@id' => $row['id'],
            '@error' => $e->getMessage(),
          ]);
          $body = '[Decryption error]';
        }
      }

      $messages[] = SecureMessageDTO::fromRow($row, $body);
    }

    return array_reverse($messages);
  }

  /**
   * {@inheritdoc}
   */
  public function edit(int $messageId, int $tenantId, string $newBody): SecureMessageDTO {
    $row = $this->database->select('secure_message', 'm')
      ->fields('m')
      ->condition('m.id', $messageId)
      ->condition('m.tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();

    if (!$row) {
      throw new \RuntimeException('Message not found.');
    }

    // Check edit window.
    $config = $this->configFactory->get('jaraba_messaging.settings');
    $windowMinutes = $config->get('edit_window_minutes') ?? 15;
    $elapsed = time() - (int) $row['created_at'];
    if ($elapsed > ($windowMinutes * 60)) {
      throw new EditWindowExpiredException($windowMinutes);
    }

    // Re-encrypt with new body.
    $payload = $this->encryptionService->encrypt($newBody, $tenantId);

    $this->database->update('secure_message')
      ->fields([
        'body_encrypted' => $payload->ciphertext,
        'encryption_iv' => $payload->iv,
        'encryption_tag' => $payload->tag,
        'is_edited' => 1,
        'edited_at' => time(),
      ])
      ->condition('id', $messageId)
      ->execute();

    $this->auditService->log(
      (int) $row['conversation_id'],
      $tenantId,
      'message.edited',
      $messageId,
    );

    $updatedRow = $this->database->select('secure_message', 'm')
      ->fields('m')
      ->condition('m.id', $messageId)
      ->execute()
      ->fetchAssoc();

    return SecureMessageDTO::fromRow($updatedRow, $newBody);
  }

  /**
   * {@inheritdoc}
   */
  public function softDelete(int $messageId, int $tenantId): void {
    $row = $this->database->select('secure_message', 'm')
      ->fields('m', ['conversation_id'])
      ->condition('m.id', $messageId)
      ->condition('m.tenant_id', $tenantId)
      ->execute()
      ->fetchAssoc();

    if (!$row) {
      return;
    }

    $this->database->update('secure_message')
      ->fields([
        'is_deleted' => 1,
        'deleted_at' => time(),
      ])
      ->condition('id', $messageId)
      ->execute();

    $this->auditService->log(
      (int) $row['conversation_id'],
      $tenantId,
      'message.deleted',
      $messageId,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function markRead(int $conversationId, int $userId, ?int $upToMessageId = NULL): int {
    // Get the latest message ID if not specified.
    if ($upToMessageId === NULL) {
      $upToMessageId = (int) $this->database->select('secure_message', 'm')
        ->fields('m', ['id'])
        ->condition('m.conversation_id', $conversationId)
        ->orderBy('m.id', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
    }

    if (!$upToMessageId) {
      return 0;
    }

    // Get unread messages (not sent by user, not already read).
    $unread = $this->database->select('secure_message', 'm')
      ->fields('m', ['id'])
      ->condition('m.conversation_id', $conversationId)
      ->condition('m.id', $upToMessageId, '<=')
      ->condition('m.sender_id', $userId, '<>')
      ->condition('m.is_deleted', 0)
      ->execute()
      ->fetchCol();

    if (empty($unread)) {
      return 0;
    }

    $count = 0;
    $now = time();
    foreach ($unread as $msgId) {
      try {
        $this->database->merge('message_read_receipt')
          ->keys([
            'message_id' => (int) $msgId,
            'user_id' => $userId,
          ])
          ->fields([
            'conversation_id' => $conversationId,
            'read_at' => $now,
          ])
          ->execute();
        $count++;
      }
      catch (\Exception $e) {
        // Ignore duplicate key errors.
      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function addReaction(int $messageId, int $userId, string $emoji): void {
    $row = $this->database->select('secure_message', 'm')
      ->fields('m', ['reactions', 'conversation_id', 'tenant_id'])
      ->condition('m.id', $messageId)
      ->execute()
      ->fetchAssoc();

    if (!$row) {
      return;
    }

    $reactions = $row['reactions'] ? json_decode($row['reactions'], TRUE) : [];
    if (!isset($reactions[$emoji])) {
      $reactions[$emoji] = [];
    }
    if (!in_array($userId, $reactions[$emoji])) {
      $reactions[$emoji][] = $userId;
    }

    $this->database->update('secure_message')
      ->fields(['reactions' => json_encode($reactions)])
      ->condition('id', $messageId)
      ->execute();

    $this->auditService->log(
      (int) $row['conversation_id'],
      (int) $row['tenant_id'],
      'message.reaction_added',
      $messageId,
      ['emoji' => $emoji, 'user_id' => $userId],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function removeReaction(int $messageId, int $userId, string $emoji): void {
    $row = $this->database->select('secure_message', 'm')
      ->fields('m', ['reactions', 'conversation_id', 'tenant_id'])
      ->condition('m.id', $messageId)
      ->execute()
      ->fetchAssoc();

    if (!$row || !$row['reactions']) {
      return;
    }

    $reactions = json_decode($row['reactions'], TRUE);
    if (isset($reactions[$emoji])) {
      $reactions[$emoji] = array_values(array_diff($reactions[$emoji], [$userId]));
      if (empty($reactions[$emoji])) {
        unset($reactions[$emoji]);
      }
    }

    $this->database->update('secure_message')
      ->fields(['reactions' => !empty($reactions) ? json_encode($reactions) : NULL])
      ->condition('id', $messageId)
      ->execute();
  }

  /**
   * Checks rate limits for message sending.
   *
   * @throws \Drupal\jaraba_messaging\Exception\RateLimitException
   */
  protected function checkRateLimit(int $senderId, int $conversationId, int $tenantId): void {
    if ($this->currentUser->hasPermission('bypass messaging rate limit')) {
      return;
    }

    $config = $this->configFactory->get('jaraba_messaging.settings');
    $userLimit = $config->get('rate_limiting.messages_per_minute_per_user') ?? 30;
    $convLimit = $config->get('rate_limiting.messages_per_minute_per_conversation') ?? 100;
    $windowStart = time() - 60;

    // Check per-user rate.
    $userCount = (int) $this->database->select('secure_message', 'm')
      ->condition('m.sender_id', $senderId)
      ->condition('m.conversation_id', $conversationId)
      ->condition('m.created_at', $windowStart, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($userCount >= $userLimit) {
      throw new RateLimitException($userLimit, 60, 'user');
    }

    // Check per-conversation rate.
    $convCount = (int) $this->database->select('secure_message', 'm')
      ->condition('m.conversation_id', $conversationId)
      ->condition('m.created_at', $windowStart, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($convCount >= $convLimit) {
      throw new RateLimitException($convLimit, 60, 'conversation');
    }
  }

}
