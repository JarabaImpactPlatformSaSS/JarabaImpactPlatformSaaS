<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for caching AI responses to reduce costs.
 *
 * Uses Drupal Cache API which can be backed by Redis if configured.
 * Implements smart caching with TTL, hit rate tracking, and cost savings.
 *
 * CACHE STRATEGY:
 * - TTL: 1 hour (configurable)
 * - Key: hash of message + mode + context hash
 * - Cache hit rate objetivo: 20%+
 * - Estimated savings: €2-5/day with typical usage
 */
class CopilotCacheService
{

    /**
     * Cache backend.
     */
    protected CacheBackendInterface $cache;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Cache tags invalidator.
     */
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

    /**
     * Default TTL in seconds (1 hour).
     */
    protected const DEFAULT_TTL = 3600;

    /**
     * Constructs CopilotCacheService.
     */
    public function __construct(
        CacheBackendInterface $cache,
        LoggerInterface $logger,
        CacheTagsInvalidatorInterface $cacheTagsInvalidator
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    }

    /**
     * Generates cache key for a message.
     *
     * @param string $message
     *   User message.
     * @param string $mode
     *   Copilot mode.
     * @param array $context
     *   Context array (will hash relevant parts).
     *
     * @return string
     *   Cache key.
     */
    public function generateCacheKey(string $message, string $mode, array $context): string
    {
        // Normalize message (lowercase, trim, remove extra spaces)
        $normalizedMessage = strtolower(trim(preg_replace('/\s+/', ' ', $message)));

        // Extract stable context elements for cache key
        $stableContext = [
            'vertical' => $context['vertical'] ?? '',
            'phase' => $context['phase'] ?? '',
            'tenant_plan' => $context['tenant_plan'] ?? '',
        ];

        // Generate hash
        $payload = json_encode([
            'message' => $normalizedMessage,
            'mode' => $mode,
            'context' => $stableContext,
        ]);

        return 'copilot_response:' . md5($payload);
    }

    /**
     * Gets cached response if available.
     *
     * FIX-036: Two-layer cache: exact match (Drupal cache) then semantic
     * match (SemanticCacheService via Qdrant). Semantic layer catches
     * queries like "AOVE premium" vs "aceite de oliva virgen extra".
     *
     * @param string $message
     *   User message.
     * @param string $mode
     *   Copilot mode.
     * @param array $context
     *   Context array.
     *
     * @return array|null
     *   Cached response or NULL if not found.
     */
    public function get(string $message, string $mode, array $context): ?array
    {
        // Layer 1: Exact match via Drupal cache.
        $key = $this->generateCacheKey($message, $mode, $context);
        $cached = $this->cache->get($key);

        if ($cached && isset($cached->data)) {
            $this->trackCacheHit();
            $this->logger->debug('Copilot cache HIT (exact): @key', ['@key' => $key]);

            $response = $cached->data;
            $response['from_cache'] = TRUE;
            $response['cache_key'] = $key;
            $response['cache_layer'] = 'exact';

            return $response;
        }

        // Layer 2: Semantic match via SemanticCacheService (FIX-036).
        if (\Drupal::hasService('jaraba_copilot_v2.semantic_cache')) {
            try {
                $semanticCache = \Drupal::service('jaraba_copilot_v2.semantic_cache');
                $tenantId = $context['tenant_id'] ?? $context['vertical'] ?? '';
                $semanticResult = $semanticCache->get($message, $mode, (string) $tenantId);

                if ($semanticResult !== NULL) {
                    $this->trackCacheHit();
                    $this->logger->debug('Copilot cache HIT (semantic): @msg', ['@msg' => mb_substr($message, 0, 80)]);

                    $semanticResult['from_cache'] = TRUE;
                    $semanticResult['cache_layer'] = 'semantic';

                    return $semanticResult;
                }
            } catch (\Exception $e) {
                // Semantic cache failure is non-critical.
                $this->logger->debug('Semantic cache lookup failed: @msg', ['@msg' => $e->getMessage()]);
            }
        }

        $this->trackCacheMiss();
        return NULL;
    }

    /**
     * Stores response in cache.
     *
     * @param string $message
     *   User message.
     * @param string $mode
     *   Copilot mode.
     * @param array $context
     *   Context array.
     * @param array $response
     *   Response to cache.
     * @param int|null $ttl
     *   Time to live in seconds (optional).
     */
    public function set(string $message, string $mode, array $context, array $response, ?int $ttl = NULL): void
    {
        // Don't cache error responses
        if (isset($response['error']) && $response['error']) {
            return;
        }

        $key = $this->generateCacheKey($message, $mode, $context);
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $expires = time() + $ttl;

        // Remove 'from_cache' flag before storing
        unset($response['from_cache']);
        unset($response['cache_key']);

        // AI-10: Tags granulares para invalidación selectiva por modo/tenant.
        $tags = ['copilot_responses'];
        $tags[] = 'copilot_mode:' . $mode;
        $tenantId = $context['tenant_id'] ?? $context['vertical'] ?? NULL;
        if ($tenantId) {
            $tags[] = 'copilot_tenant:' . $tenantId;
        }

        $this->cache->set($key, $response, $expires, $tags);

        // FIX-036: Also store in semantic cache for fuzzy matching.
        if (\Drupal::hasService('jaraba_copilot_v2.semantic_cache')) {
            try {
                $semanticCache = \Drupal::service('jaraba_copilot_v2.semantic_cache');
                $tenantId = $context['tenant_id'] ?? $context['vertical'] ?? '';
                $responseText = $response['text'] ?? json_encode($response);
                $semanticCache->set($message, $responseText, $mode, (string) $tenantId, $ttl);
            } catch (\Exception $e) {
                // Non-critical — exact cache already stored.
            }
        }

        $this->logger->debug('Copilot cache SET: @key (TTL: @ttl)', [
            '@key' => $key,
            '@ttl' => $ttl,
        ]);
    }

    /**
     * Invalidates all cached responses.
     */
    public function invalidateAll(): void
    {
        $this->cache->invalidateAll();
        $this->logger->info('Copilot cache invalidated');
    }

    /**
     * Invalidates cached responses for a specific copilot mode.
     *
     * @param string $mode
     *   The copilot mode (e.g., 'empleo', 'emprender').
     */
    public function invalidateByMode(string $mode): void
    {
        $this->cacheTagsInvalidator->invalidateTags(['copilot_mode:' . $mode]);
        $this->logger->info('Copilot cache invalidated for mode: @mode', ['@mode' => $mode]);
    }

    /**
     * Invalidates cached responses for a specific tenant.
     *
     * @param string|int $tenantId
     *   The tenant ID.
     */
    public function invalidateByTenant(string|int $tenantId): void
    {
        $this->cacheTagsInvalidator->invalidateTags(['copilot_tenant:' . $tenantId]);
        $this->logger->info('Copilot cache invalidated for tenant: @tenant', ['@tenant' => $tenantId]);
    }

    /**
     * Gets cache statistics.
     *
     * @return array
     *   Stats with hits, misses, hit_rate.
     */
    public function getStats(): array
    {
        $state = \Drupal::state();
        $hits = $state->get('copilot_cache_hits', 0);
        $misses = $state->get('copilot_cache_misses', 0);
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'total' => $total,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
            'estimated_savings' => $this->estimateSavings($hits),
        ];
    }

    /**
     * Tracks cache hit.
     */
    protected function trackCacheHit(): void
    {
        $state = \Drupal::state();
        $state->set('copilot_cache_hits', $state->get('copilot_cache_hits', 0) + 1);
        $state->set('ai_cost_cache_hits', $state->get('ai_cost_cache_hits', 0) + 1);
    }

    /**
     * Tracks cache miss.
     */
    protected function trackCacheMiss(): void
    {
        $state = \Drupal::state();
        $state->set('copilot_cache_misses', $state->get('copilot_cache_misses', 0) + 1);
    }

    /**
     * Estimates cost savings from cache hits.
     *
     * @param int $hits
     *   Number of cache hits.
     *
     * @return float
     *   Estimated savings in EUR.
     */
    protected function estimateSavings(int $hits): float
    {
        // Average cost per AI call (estimated)
        $avgCostPerCall = 0.002; // €0.002 per call
        return round($hits * $avgCostPerCall, 4);
    }

    /**
     * Resets cache stats (for testing/daily reset).
     */
    public function resetStats(): void
    {
        $state = \Drupal::state();
        $state->delete('copilot_cache_hits');
        $state->delete('copilot_cache_misses');
    }

}
