<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Psr\Log\LoggerInterface;

/**
 * Ticket deflection service.
 *
 * Searches the knowledge base and FAQ content to suggest existing
 * answers before a ticket is submitted. Reduces ticket volume by
 * deflecting common questions to self-service content.
 */
final class TicketDeflectionService {

  /**
   * Qdrant collection name for KB content.
   */
  private const COLLECTION_NAME = 'knowledge_base';

  /**
   * Minimum relevance score to include in results.
   */
  private const MIN_SCORE = 0.65;

  public function __construct(
    protected ?QdrantDirectClient $qdrantClient,
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext,
  ) {}

  /**
   * Searches for deflection content matching a user's query.
   *
   * @param string $query
   *   The user's question or issue description.
   * @param int|null $tenantId
   *   Optional tenant ID for scoped results.
   *
   * @return array
   *   Array of deflection suggestions with title, url, excerpt, score.
   */
  public function searchDeflection(string $query, ?int $tenantId = NULL): array {
    if ($this->qdrantClient === NULL) {
      $this->logger->info('Qdrant unavailable — deflection search skipped.');
      return [];
    }

    $query = trim($query);
    if (mb_strlen($query) < 5) {
      return [];
    }

    try {
      $tenantId = $tenantId ?? ($this->tenantContext?->getCurrentTenant()?->id());

      // Build Qdrant search filter.
      $filter = [
        'must' => [
          [
            'key' => 'content_type',
            'match' => ['any' => ['article', 'faq', 'documentation', 'kb_entry']],
          ],
          [
            'key' => 'status',
            'match' => ['value' => 'published'],
          ],
        ],
      ];

      // Tenant-scoped: include both tenant-specific and global content.
      if ($tenantId) {
        $filter['should'] = [
          ['key' => 'tenant_id', 'match' => ['value' => (int) $tenantId]],
          ['key' => 'scope', 'match' => ['value' => 'global']],
        ];
      }

      $results = $this->qdrantClient->search(
        self::COLLECTION_NAME,
        $query,
        [
          'limit' => 5,
          'score_threshold' => self::MIN_SCORE,
          'filter' => $filter,
        ],
      );

      if (empty($results)) {
        return [];
      }

      $suggestions = [];
      foreach ($results as $result) {
        $payload = $result['payload'] ?? [];
        $score = (float) ($result['score'] ?? 0);

        if ($score < self::MIN_SCORE) {
          continue;
        }

        $suggestions[] = [
          'id' => $payload['entity_id'] ?? $result['id'] ?? '',
          'title' => $payload['title'] ?? 'Untitled',
          'url' => $payload['url'] ?? '#',
          'excerpt' => mb_substr($payload['excerpt'] ?? $payload['content'] ?? '', 0, 200),
          'score' => round($score, 3),
        ];
      }

      $this->logger->info('Deflection search for "@query": @count results found.', [
        '@query' => mb_substr($query, 0, 60),
        '@count' => count($suggestions),
      ]);

      return $suggestions;
    }
    catch (\Exception $e) {
      $this->logger->warning('Deflection search failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
