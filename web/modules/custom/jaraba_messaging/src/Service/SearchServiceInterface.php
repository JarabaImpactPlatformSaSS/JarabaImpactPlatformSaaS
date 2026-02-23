<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

/**
 * Interface for the messaging search service.
 */
interface SearchServiceInterface {

  /**
   * Searches messages within a tenant's conversations.
   *
   * @param string $query
   *   The search query string.
   * @param int $tenantId
   *   The tenant ID to scope the search.
   * @param array $conversationIds
   *   Optional list of conversation IDs to limit search to.
   * @param int $limit
   *   Maximum results to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of search result items with keys:
   *   message_id, conversation_id, sender_id, body_preview, created_at.
   */
  public function search(string $query, int $tenantId, array $conversationIds = [], int $limit = 20, int $offset = 0): array;

}
