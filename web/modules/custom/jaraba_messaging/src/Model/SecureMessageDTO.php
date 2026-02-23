<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Model;

/**
 * Data Transfer Object for messages stored in the secure_message custom table.
 *
 * Represents a decrypted message with all metadata. The body field
 * contains the already-decrypted plaintext; encryption/decryption
 * is handled by the EncryptionService layer before constructing this DTO.
 *
 * Usage:
 *   $dto = SecureMessageDTO::fromRow($row, $decryptedBody);
 *   $array = $dto->toArray();
 */
final readonly class SecureMessageDTO {

  /**
   * Constructs a SecureMessageDTO.
   *
   * @param int $id
   *   Primary key in secure_message table.
   * @param int $conversation_id
   *   Reference to the parent secure_conversation entity.
   * @param int $sender_id
   *   User ID of the message sender.
   * @param int $tenant_id
   *   Tenant (group) ID for multi-tenancy isolation.
   * @param string $message_type
   *   Message type: text, image, file, system, etc.
   * @param string $body
   *   Decrypted message body (plaintext).
   * @param int|null $reply_to_id
   *   ID of the message this is a reply to, or NULL.
   * @param array $attachment_ids
   *   Array of file entity IDs attached to this message.
   * @param array $reactions
   *   Array of reactions keyed by emoji => [user_ids].
   * @param array $metadata
   *   Extensible JSON metadata (read receipts, link previews, etc.).
   * @param bool $is_edited
   *   Whether the message has been edited.
   * @param int|null $edited_at
   *   Timestamp of last edit, or NULL if never edited.
   * @param bool $is_deleted
   *   Whether the message has been soft-deleted.
   * @param int|null $deleted_at
   *   Timestamp of deletion, or NULL if not deleted.
   * @param int $created_at
   *   Timestamp when the message was created.
   */
  public function __construct(
    public int $id,
    public int $conversation_id,
    public int $sender_id,
    public int $tenant_id,
    public string $message_type,
    public string $body,
    public ?int $reply_to_id,
    public array $attachment_ids,
    public array $reactions,
    public array $metadata,
    public bool $is_edited,
    public ?int $edited_at,
    public bool $is_deleted,
    public ?int $deleted_at,
    public int $created_at,
  ) {}

  /**
   * Creates a SecureMessageDTO from a database row and decrypted body.
   *
   * @param array $row
   *   Associative array from the secure_message table query.
   * @param string $decryptedBody
   *   The already-decrypted message body plaintext.
   *
   * @return self
   */
  public static function fromRow(array $row, string $decryptedBody): self {
    return new self(
      id: (int) $row['id'],
      conversation_id: (int) $row['conversation_id'],
      sender_id: (int) $row['sender_id'],
      tenant_id: (int) $row['tenant_id'],
      message_type: $row['message_type'] ?? 'text',
      body: $decryptedBody,
      reply_to_id: isset($row['reply_to_id']) ? (int) $row['reply_to_id'] : NULL,
      attachment_ids: !empty($row['attachment_ids']) ? json_decode($row['attachment_ids'], TRUE) ?? [] : [],
      reactions: !empty($row['reactions']) ? json_decode($row['reactions'], TRUE) ?? [] : [],
      metadata: !empty($row['metadata']) ? json_decode($row['metadata'], TRUE) ?? [] : [],
      is_edited: (bool) ($row['is_edited'] ?? FALSE),
      edited_at: isset($row['edited_at']) ? (int) $row['edited_at'] : NULL,
      is_deleted: (bool) ($row['is_deleted'] ?? FALSE),
      deleted_at: isset($row['deleted_at']) ? (int) $row['deleted_at'] : NULL,
      created_at: (int) $row['created_at'],
    );
  }

  /**
   * Exports the DTO to an associative array.
   *
   * JSON-serializable fields (attachment_ids, reactions, metadata)
   * are returned as arrays, not as JSON strings.
   *
   * @return array
   *   Keyed array matching the DTO properties.
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'conversation_id' => $this->conversation_id,
      'sender_id' => $this->sender_id,
      'tenant_id' => $this->tenant_id,
      'message_type' => $this->message_type,
      'body' => $this->body,
      'reply_to_id' => $this->reply_to_id,
      'attachment_ids' => $this->attachment_ids,
      'reactions' => $this->reactions,
      'metadata' => $this->metadata,
      'is_edited' => $this->is_edited,
      'edited_at' => $this->edited_at,
      'is_deleted' => $this->is_deleted,
      'deleted_at' => $this->deleted_at,
      'created_at' => $this->created_at,
    ];
  }

}
