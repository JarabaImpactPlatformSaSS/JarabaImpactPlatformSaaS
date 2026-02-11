<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Rate limiter service for API protection.
 *
 * Implementa rate limiting usando sliding window algorithm para
 * proteger APIs críticas contra abuso.
 *
 * Los límites son configurables desde:
 * /admin/config/system/rate-limits
 *
 * MEJORES PRÁCTICAS:
 * - Sliding window counter (más preciso que fixed window)
 * - Diferentes límites por tipo de usuario/endpoint
 * - Headers X-RateLimit-* en respuestas
 * - Graceful degradation
 */
class RateLimiterService
{

    /**
     * Cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected CacheBackendInterface $cache;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Default rate limits per endpoint type (fallback si no hay config).
     */
    const DEFAULT_LIMITS = [
        'api' => ['requests' => 100, 'window' => 60],
        'api_authenticated' => ['requests' => 200, 'window' => 60],
        'ai' => ['requests' => 20, 'window' => 60],
        'search' => ['requests' => 30, 'window' => 60],
        'insights' => ['requests' => 50, 'window' => 60],
    ];

    /**
     * Constructor.
     */
    public function __construct(CacheBackendInterface $cache, LoggerInterface $logger, ConfigFactoryInterface $configFactory)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->configFactory = $configFactory;
    }

    /**
     * Obtiene los límites para un tipo de endpoint desde config con fallback.
     */
    protected function getLimitsForType(string $endpointType): array
    {
        $config = $this->configFactory->get('ecosistema_jaraba_core.rate_limits');
        $requests = $config->get("limits.{$endpointType}.requests");
        $window = $config->get("limits.{$endpointType}.window");

        if ($requests && $window) {
            return ['requests' => (int) $requests, 'window' => (int) $window];
        }

        return self::DEFAULT_LIMITS[$endpointType] ?? self::DEFAULT_LIMITS['api'];
    }

    /**
     * Checks if a request should be allowed.
     *
     * @param string $identifier
     *   Unique identifier (user ID, IP, API key).
     * @param string $endpointType
     *   Type of endpoint for limit selection.
     *
     * @return array
     *   Result with 'allowed', 'remaining', 'reset_at' keys.
     */
    public function check(string $identifier, string $endpointType = 'api'): array
    {
        $limits = $this->getLimitsForType($endpointType);
        $maxRequests = $limits['requests'];
        $windowSeconds = $limits['window'];

        // BE-13: Fixed-window counter approach (O(1) vs O(n) array_filter).
        // Uses two cache entries: counter + window start, with TTL auto-expiry.
        $cacheKey = "rate_limit:{$endpointType}:{$identifier}";
        $now = time();

        $cached = $this->cache->get($cacheKey);
        $windowData = $cached ? $cached->data : NULL;

        // Check if window is still valid.
        if ($windowData && isset($windowData['window_start']) && ($now - $windowData['window_start']) < $windowSeconds) {
            $currentCount = $windowData['count'];
            $windowStart = $windowData['window_start'];
        } else {
            // Start new window.
            $currentCount = 0;
            $windowStart = $now;
        }

        $remaining = max(0, $maxRequests - $currentCount);
        $allowed = $currentCount < $maxRequests;

        if ($allowed) {
            $this->cache->set($cacheKey, [
                'count' => $currentCount + 1,
                'window_start' => $windowStart,
            ], $windowStart + $windowSeconds);
        } else {
            $this->logger->warning('Rate limit exceeded for @id on @type', [
                '@id' => $identifier,
                '@type' => $endpointType,
            ]);
        }

        $resetAt = $windowStart + $windowSeconds;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'limit' => $maxRequests,
            'reset_at' => $resetAt,
            'retry_after' => $allowed ? 0 : $resetAt - $now,
        ];
    }

    /**
     * Consumes a request from the rate limit.
     *
     * @param string $identifier
     *   Unique identifier.
     * @param string $endpointType
     *   Endpoint type.
     * @param int $cost
     *   Cost of this request (default 1).
     *
     * @return array
     *   Result with rate limit status.
     */
    public function consume(string $identifier, string $endpointType = 'api', int $cost = 1): array
    {
        $result = $this->check($identifier, $endpointType);

        // For high-cost operations, subtract additional
        if ($cost > 1 && $result['allowed']) {
            $limits = $this->getLimitsForType($endpointType);
            $cacheKey = "rate_limit:{$endpointType}:{$identifier}";
            $now = time();

            $cached = $this->cache->get($cacheKey);
            $requests = $cached ? $cached->data : [];

            // Add additional cost
            for ($i = 1; $i < $cost; $i++) {
                $requests[] = $now;
            }

            $this->cache->set($cacheKey, $requests, $now + $limits['window']);
            $result['remaining'] = max(0, $result['remaining'] - ($cost - 1));
        }

        return $result;
    }

    /**
     * Gets rate limit headers for HTTP response.
     *
     * @param array $result
     *   Result from check() or consume().
     *
     * @return array
     *   Headers to add to response.
     */
    public function getHeaders(array $result): array
    {
        $headers = [
            'X-RateLimit-Limit' => (string) $result['limit'],
            'X-RateLimit-Remaining' => (string) $result['remaining'],
            'X-RateLimit-Reset' => (string) $result['reset_at'],
        ];

        if (!$result['allowed']) {
            $headers['Retry-After'] = (string) $result['retry_after'];
        }

        return $headers;
    }

    /**
     * Resets rate limit for an identifier.
     *
     * @param string $identifier
     *   Unique identifier.
     * @param string $endpointType
     *   Endpoint type.
     */
    public function reset(string $identifier, string $endpointType = 'api'): void
    {
        $cacheKey = "rate_limit:{$endpointType}:{$identifier}";
        $this->cache->delete($cacheKey);
    }

}
