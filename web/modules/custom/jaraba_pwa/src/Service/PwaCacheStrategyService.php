<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Service;

use Psr\Log\LoggerInterface;

/**
 * Cache strategy service for the PWA service worker.
 *
 * Defines caching strategies for different route patterns,
 * following the Workbox-style approach to service worker caching.
 *
 * Strategies:
 * - cache-first: Serve from cache, fall back to network (static assets).
 * - network-first: Try network, fall back to cache (API responses).
 * - stale-while-revalidate: Serve from cache, update in background (pages).
 * - network-only: Always fetch from network (real-time data).
 * - cache-only: Only serve from cache (pre-cached resources).
 */
class PwaCacheStrategyService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Gets all configured cache strategies.
   *
   * @return array
   *   Array of strategy definitions with keys:
   *   - pattern: Route pattern (regex).
   *   - strategy: Cache strategy name.
   *   - maxEntries: Maximum cache entries (for LRU eviction).
   *   - maxAge: Maximum cache age in seconds.
   *   - description: Human-readable description.
   */
  public function getStrategies(): array {
    return [
      [
        'pattern' => '/\\.(?:css|js|woff2?|ttf|eot|svg)$/',
        'strategy' => 'cache-first',
        'maxEntries' => 200,
        'maxAge' => 2592000,
        'description' => 'Static assets (CSS, JS, fonts): cache-first with 30-day TTL.',
      ],
      [
        'pattern' => '/\\.(?:png|jpg|jpeg|gif|webp|avif|ico)$/',
        'strategy' => 'cache-first',
        'maxEntries' => 100,
        'maxAge' => 2592000,
        'description' => 'Images: cache-first with 30-day TTL.',
      ],
      [
        'pattern' => '/^\\/api\\/v1\\//',
        'strategy' => 'network-first',
        'maxEntries' => 50,
        'maxAge' => 300,
        'description' => 'API endpoints: network-first with 5-minute cache fallback.',
      ],
      [
        'pattern' => '/^\\/api\\/v1\\/pwa\\/manifest$/',
        'strategy' => 'stale-while-revalidate',
        'maxEntries' => 1,
        'maxAge' => 86400,
        'description' => 'PWA manifest: stale-while-revalidate with 24-hour TTL.',
      ],
      [
        'pattern' => '/^\\/dashboard/',
        'strategy' => 'stale-while-revalidate',
        'maxEntries' => 10,
        'maxAge' => 600,
        'description' => 'Dashboard pages: stale-while-revalidate with 10-minute TTL.',
      ],
      [
        'pattern' => '/^\\/user\\//',
        'strategy' => 'network-first',
        'maxEntries' => 5,
        'maxAge' => 60,
        'description' => 'User pages: network-first with 1-minute cache fallback.',
      ],
      [
        'pattern' => '/^\\/admin\\//',
        'strategy' => 'network-only',
        'maxEntries' => 0,
        'maxAge' => 0,
        'description' => 'Admin pages: always fetch from network.',
      ],
      [
        'pattern' => '/^\\/$/',
        'strategy' => 'stale-while-revalidate',
        'maxEntries' => 1,
        'maxAge' => 3600,
        'description' => 'Home page: stale-while-revalidate with 1-hour TTL.',
      ],
    ];
  }

  /**
   * Returns the cache strategy for a given route pattern.
   *
   * @param string $routePattern
   *   The URL path to match against configured strategies.
   *
   * @return string
   *   The cache strategy name. Falls back to 'network-first'
   *   if no specific pattern matches.
   */
  public function getStrategyForRoute(string $routePattern): string {
    try {
      $strategies = $this->getStrategies();

      foreach ($strategies as $config) {
        if (preg_match($config['pattern'], $routePattern)) {
          return $config['strategy'];
        }
      }

      // Default strategy for unmatched routes.
      return 'network-first';
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to resolve cache strategy for @route: @error', [
        '@route' => $routePattern,
        '@error' => $e->getMessage(),
      ]);
      return 'network-first';
    }
  }

}
