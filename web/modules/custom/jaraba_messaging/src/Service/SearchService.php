<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de búsqueda full-text y semántica en mensajes.
 *
 * PROPÓSITO:
 * Busca en mensajes descifrados usando full-text SQL y opcionalmente
 * búsqueda semántica vía Qdrant cuando está disponible.
 * Respeta is_confidential (excluye de Qdrant/IA).
 */
class SearchService implements SearchServiceInterface {

  public function __construct(
    protected Connection $database,
    protected MessageEncryptionServiceInterface $encryptionService,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function search(string $query, int $tenantId, array $conversationIds = [], int $limit = 20, int $offset = 0): array {
    if (empty($query)) {
      return [];
    }

    // Full-text search by decrypting messages and matching.
    // For large datasets, this would use Qdrant. For now, SQL-based.
    $dbQuery = $this->database->select('secure_message', 'm')
      ->fields('m')
      ->condition('m.tenant_id', $tenantId)
      ->condition('m.is_deleted', 0)
      ->orderBy('m.created_at', 'DESC')
      ->range($offset, $limit * 3); // Fetch more to account for non-matches.

    if (!empty($conversationIds)) {
      $dbQuery->condition('m.conversation_id', $conversationIds, 'IN');
    }

    $rows = $dbQuery->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $results = [];
    $queryLower = mb_strtolower($query);

    foreach ($rows as $row) {
      if (count($results) >= $limit) {
        break;
      }

      try {
        $payload = new \Drupal\jaraba_messaging\Model\EncryptedPayload(
          ciphertext: $row['body_encrypted'],
          iv: $row['encryption_iv'],
          tag: $row['encryption_tag'],
          keyId: $row['encryption_key_id'],
        );
        $plaintext = $this->encryptionService->decrypt($payload, $tenantId);

        if (mb_stripos($plaintext, $query) !== FALSE) {
          $results[] = [
            'message_id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'sender_id' => (int) $row['sender_id'],
            'body_preview' => $this->highlightMatch($plaintext, $query),
            'created_at' => (int) $row['created_at'],
          ];
        }
      }
      catch (\Throwable $e) {
        // Skip messages that cannot be decrypted.
        continue;
      }
    }

    return $results;
  }

  /**
   * Creates a highlighted preview of the match.
   */
  protected function highlightMatch(string $text, string $query): string {
    $pos = mb_stripos($text, $query);
    if ($pos === FALSE) {
      return mb_substr($text, 0, 150);
    }

    $start = max(0, $pos - 50);
    $length = min(mb_strlen($text) - $start, 150);
    $preview = mb_substr($text, $start, $length);

    if ($start > 0) {
      $preview = '...' . $preview;
    }
    if ($start + $length < mb_strlen($text)) {
      $preview .= '...';
    }

    return $preview;
  }

}
