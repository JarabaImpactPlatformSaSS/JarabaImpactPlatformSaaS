<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Psr\Log\LoggerInterface;

/**
 * Semantic Cache Service (FIX-036).
 *
 * Cache layer based on embeddings: generates embedding of query, searches
 * in Qdrant collection `semantic_cache` with threshold 0.92, returns
 * cached response if match found.
 *
 * Queries that are semantically equivalent ("aceite de oliva virgen extra"
 * vs "AOVE premium") hit the same cache entry.
 */
class SemanticCacheService
{

    /**
     * Similarity threshold for cache hit.
     */
    protected const SIMILARITY_THRESHOLD = 0.92;

    /**
     * Cache collection name in Qdrant.
     */
    protected const COLLECTION = 'semantic_cache';

    /**
     * Default TTL in seconds (1 hour).
     */
    protected const DEFAULT_TTL = 3600;

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected ?object $qdrantClient = NULL,
        protected ?object $ragService = NULL,
    ) {
    }

    /**
     * Gets a cached response by semantic similarity.
     *
     * @param string $query
     *   The user query.
     * @param string $mode
     *   The copilot mode.
     * @param string|null $tenantId
     *   The tenant ID.
     *
     * @return array|null
     *   Cached response array or NULL if no match.
     */
    public function get(string $query, string $mode, ?string $tenantId = NULL): ?array
    {
        if (!$this->qdrantClient || !$this->ragService) {
            return NULL;
        }

        try {
            // Generate embedding for query.
            $embedding = $this->generateEmbedding($query);
            if (empty($embedding)) {
                return NULL;
            }

            // Search Qdrant with similarity threshold.
            $filters = [
                'must' => [
                    ['key' => 'mode', 'match' => ['value' => $mode]],
                ],
            ];

            if ($tenantId) {
                $filters['must'][] = ['key' => 'tenant_id', 'match' => ['value' => $tenantId]];
            }

            $results = $this->searchQdrant($embedding, $filters);

            if (!empty($results)) {
                $topResult = $results[0];
                if (($topResult['score'] ?? 0) >= self::SIMILARITY_THRESHOLD) {
                    // Check TTL.
                    $cachedAt = $topResult['payload']['cached_at'] ?? 0;
                    $ttl = $topResult['payload']['ttl'] ?? self::DEFAULT_TTL;

                    if ((time() - $cachedAt) < $ttl) {
                        $this->logger->debug('Semantic cache HIT: score=@score, query="@query"', [
                            '@score' => round($topResult['score'], 4),
                            '@query' => mb_substr($query, 0, 50),
                        ]);

                        return [
                            'text' => $topResult['payload']['response'] ?? '',
                            'cached' => TRUE,
                            'similarity_score' => $topResult['score'],
                            'original_query' => $topResult['payload']['original_query'] ?? '',
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->warning('Semantic cache get failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return NULL;
    }

    /**
     * Stores a response in the semantic cache.
     *
     * @param string $query
     *   The user query.
     * @param string $response
     *   The AI response text.
     * @param string $mode
     *   The copilot mode.
     * @param string|null $tenantId
     *   The tenant ID.
     * @param int $ttl
     *   Time-to-live in seconds.
     */
    public function set(string $query, string $response, string $mode, ?string $tenantId = NULL, int $ttl = self::DEFAULT_TTL): void
    {
        if (!$this->qdrantClient || !$this->ragService) {
            return;
        }

        try {
            $embedding = $this->generateEmbedding($query);
            if (empty($embedding)) {
                return;
            }

            $pointId = $this->generatePointId($query, $mode, $tenantId);

            $payload = [
                'original_query' => $query,
                'response' => $response,
                'mode' => $mode,
                'tenant_id' => $tenantId ?? '',
                'cached_at' => time(),
                'ttl' => $ttl,
            ];

            $this->upsertQdrant($pointId, $embedding, $payload);

            $this->logger->debug('Semantic cache SET: query="@query", mode=@mode', [
                '@query' => mb_substr($query, 0, 50),
                '@mode' => $mode,
            ]);

        } catch (\Exception $e) {
            $this->logger->warning('Semantic cache set failed: @msg', ['@msg' => $e->getMessage()]);
        }
    }

    /**
     * Invalidates cache entries for a tenant.
     *
     * @param string $tenantId
     *   The tenant ID.
     */
    public function invalidate(string $tenantId): void
    {
        if (!$this->qdrantClient) {
            return;
        }

        try {
            if (method_exists($this->qdrantClient, 'deleteByFilter')) {
                $this->qdrantClient->deleteByFilter(self::COLLECTION, [
                    'must' => [
                        ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
                    ],
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Semantic cache invalidation failed: @msg', ['@msg' => $e->getMessage()]);
        }
    }

    /**
     * Generates an embedding for text.
     */
    protected function generateEmbedding(string $text): array
    {
        if (method_exists($this->ragService, 'generateEmbedding')) {
            return $this->ragService->generateEmbedding($text);
        }
        return [];
    }

    /**
     * Searches Qdrant for similar vectors.
     */
    protected function searchQdrant(array $embedding, array $filters): array
    {
        if (method_exists($this->qdrantClient, 'vectorSearch')) {
            return $this->qdrantClient->vectorSearch($embedding, $filters, 1, self::SIMILARITY_THRESHOLD, self::COLLECTION);
        }
        return [];
    }

    /**
     * Upserts a point in Qdrant.
     */
    protected function upsertQdrant(string $pointId, array $embedding, array $payload): void
    {
        if (method_exists($this->qdrantClient, 'upsert')) {
            $this->qdrantClient->upsert(self::COLLECTION, [
                [
                    'id' => $pointId,
                    'vector' => $embedding,
                    'payload' => $payload,
                ],
            ]);
        }
    }

    /**
     * Generates a deterministic point ID.
     */
    protected function generatePointId(string $query, string $mode, ?string $tenantId): string
    {
        return md5("{$query}:{$mode}:{$tenantId}");
    }

}
