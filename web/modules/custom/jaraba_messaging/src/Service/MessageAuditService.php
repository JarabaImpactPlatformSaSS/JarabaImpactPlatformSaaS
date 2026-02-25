<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_messaging\Model\IntegrityReport;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Audit trail inmutable con hash chain SHA-256.
 *
 * PROPÓSITO:
 * Registra todas las acciones sobre conversaciones y mensajes en una
 * cadena de hashes SHA-256 append-only. Cada registro contiene el hash
 * del registro anterior, formando una cadena verificable.
 *
 * PATRÓN:
 * - Idéntico a document_audit_log del Buzón de Confianza (doc 88).
 * - hash_chain = SHA-256(previous_hash + json_encode(entry))
 * - Primer registro: previous_hash = str_repeat('0', 64)
 * - NUNCA UPDATE ni DELETE (ENTITY-APPEND-001).
 *
 * CATÁLOGO DE ACCIONES:
 * conversation.created, conversation.closed, conversation.archived,
 * conversation.reopened, participant.added, participant.removed,
 * participant.left, participant.blocked, message.sent, message.edited,
 * message.deleted, message.read, message.reaction_added,
 * message.reaction_removed, attachment.uploaded, attachment.downloaded,
 * export.requested, settings.changed
 */
class MessageAuditService implements MessageAuditServiceInterface {

  private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

  public function __construct(
    protected Connection $database,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function log(int $conversationId, int $tenantId, string $action, ?int $targetId = NULL, array $details = []): void {
    $previousHash = $this->getLastHash($conversationId);
    $actorId = (int) $this->currentUser->id();
    $ipAddress = $this->getClientIp();
    $createdAt = time();

    $entry = [
      'conversation_id' => $conversationId,
      'tenant_id' => $tenantId,
      'action' => $action,
      'actor_id' => $actorId,
      'target_id' => $targetId,
      'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
      'ip_address' => $ipAddress,
      'created_at' => $createdAt,
    ];

    $currentHash = $this->computeHash($previousHash, $entry);

    // Store hashes as raw binary (32 bytes) for storage efficiency.
    // Internal logic uses hex strings; conversion at DB boundary only.
    $this->database->insert('message_audit_log')
      ->fields([
        'conversation_id' => $conversationId,
        'tenant_id' => $tenantId,
        'action' => $action,
        'actor_id' => $actorId,
        'target_id' => $targetId,
        'details' => $entry['details'],
        'ip_address' => $ipAddress,
        'previous_hash' => hex2bin($previousHash),
        'current_hash' => hex2bin($currentHash),
        'created_at' => $createdAt,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function verifyIntegrity(int $conversationId): IntegrityReport {
    $entries = $this->database->select('message_audit_log', 'a')
      ->fields('a')
      ->condition('conversation_id', $conversationId)
      ->orderBy('id', 'ASC')
      ->execute()
      ->fetchAll();

    if (empty($entries)) {
      return IntegrityReport::success(0);
    }

    $expectedPreviousHash = self::GENESIS_HASH;
    $index = 1;

    foreach ($entries as $entry) {
      // Convert binary hashes from DB to hex for chain verification.
      $entryPrevHash = bin2hex($entry->previous_hash);
      $entryCurrentHash = bin2hex($entry->current_hash);

      // Verify chain: previous_hash must match expected.
      if ($entryPrevHash !== $expectedPreviousHash) {
        return IntegrityReport::failure(
          count($entries),
          $index,
          "Previous hash mismatch at entry #{$index} (id={$entry->id}).",
        );
      }

      // Recompute hash and verify.
      $entryData = [
        'conversation_id' => (int) $entry->conversation_id,
        'tenant_id' => (int) $entry->tenant_id,
        'action' => $entry->action,
        'actor_id' => (int) $entry->actor_id,
        'target_id' => $entry->target_id ? (int) $entry->target_id : NULL,
        'details' => $entry->details,
        'ip_address' => $entry->ip_address,
        'created_at' => (int) $entry->created_at,
      ];

      $computedHash = $this->computeHash($entryPrevHash, $entryData);
      if ($computedHash !== $entryCurrentHash) {
        return IntegrityReport::failure(
          count($entries),
          $index,
          "Hash mismatch at entry #{$index} (id={$entry->id}). Data may have been tampered.",
        );
      }

      $expectedPreviousHash = $entryCurrentHash;
      $index++;
    }

    return IntegrityReport::success(count($entries));
  }

  /**
   * {@inheritdoc}
   */
  public function getLog(int $conversationId, int $limit = 50, int $offset = 0): array {
    $entries = $this->database->select('message_audit_log', 'a')
      ->fields('a')
      ->condition('conversation_id', $conversationId)
      ->orderBy('id', 'DESC')
      ->range($offset, $limit)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    // Convert binary hashes to hex for API consumers.
    foreach ($entries as &$entry) {
      if (isset($entry['previous_hash'])) {
        $entry['previous_hash'] = bin2hex($entry['previous_hash']);
      }
      if (isset($entry['current_hash'])) {
        $entry['current_hash'] = bin2hex($entry['current_hash']);
      }
    }

    return $entries;
  }

  /**
   * Gets the last hash in the chain for a conversation.
   */
  protected function getLastHash(int $conversationId): string {
    $lastHash = $this->database->select('message_audit_log', 'a')
      ->fields('a', ['current_hash'])
      ->condition('conversation_id', $conversationId)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    return $lastHash ? bin2hex($lastHash) : self::GENESIS_HASH;
  }

  /**
   * Computes a SHA-256 hash for the chain.
   *
   * @param string $previousHash
   *   The previous hash in the chain.
   * @param array $entry
   *   The entry data to hash.
   *
   * @return string
   *   64-character hex SHA-256 hash.
   */
  protected function computeHash(string $previousHash, array $entry): string {
    $payload = $previousHash . json_encode($entry, JSON_UNESCAPED_UNICODE);
    return hash('sha256', $payload);
  }

  /**
   * Gets the client IP address.
   */
  protected function getClientIp(): string {
    $request = $this->requestStack->getCurrentRequest();
    return $request ? $request->getClientIp() ?? '0.0.0.0' : '0.0.0.0';
  }

}
