<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for RateLimiterService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\RateLimiterService
 * @group ecosistema_jaraba_core
 */
class RateLimiterServiceTest extends UnitTestCase
{

    /**
     * The service being tested.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\RateLimiterService
     */
    protected RateLimiterService $service;

    /**
     * Mock cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * Mock logger.
     *
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(CacheBackendInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $configFactory = $this->createMock(ConfigFactoryInterface::class);

        // ConfigFactory->get() must return a Config-like object that responds to get()
        $config = $this->createMock(\Drupal\Core\Config\ImmutableConfig::class);
        $config->method('get')->willReturn(NULL);
        $configFactory->method('get')->willReturn($config);

        $this->service = new RateLimiterService($this->cache, $this->logger, $configFactory);
    }

    /**
     * @covers ::check
     */
    public function testCheckAllowsFirstRequest(): void
    {
        // First request should always be allowed
        $identifier = 'user_123';

        $this->cache->method('get')->willReturn(FALSE);

        $result = $this->service->check($identifier);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(100, $result['remaining']); // No requests yet in window
        $this->assertArrayHasKey('reset_at', $result);
    }

    /**
     * @covers ::check
     */
    public function testCheckBlocksAfterLimit(): void
    {
        $identifier = 'user_456';

        // Simulate 100 previous requests (at limit) with fixed-window counter data
        $now = time();
        $cached = (object) ['data' => ['count' => 100, 'window_start' => $now]];
        $this->cache->method('get')->willReturn($cached);

        $result = $this->service->check($identifier);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertGreaterThan(0, $result['retry_after']);
    }

    /**
     * @covers ::getHeaders
     */
    public function testGetHeadersReturnsCorrectFormat(): void
    {
        $result = [
            'allowed' => TRUE,
            'remaining' => 50,
            'limit' => 100,
            'reset_at' => time() + 60,
            'retry_after' => 0,
        ];

        $headers = $this->service->getHeaders($result);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertEquals('100', $headers['X-RateLimit-Limit']);
        $this->assertEquals('50', $headers['X-RateLimit-Remaining']);
    }

    /**
     * @covers ::getHeaders
     */
    public function testGetHeadersIncludesRetryAfterWhenBlocked(): void
    {
        $result = [
            'allowed' => FALSE,
            'remaining' => 0,
            'limit' => 100,
            'reset_at' => time() + 30,
            'retry_after' => 30,
        ];

        $headers = $this->service->getHeaders($result);

        $this->assertArrayHasKey('Retry-After', $headers);
        $this->assertEquals('30', $headers['Retry-After']);
    }

    /**
     * @covers ::consume
     */
    public function testConsumeWithHighCostReducesRemaining(): void
    {
        $identifier = 'user_789';

        // No previous requests
        $this->cache->method('get')->willReturn(FALSE);

        // Consume with cost of 5
        $result = $this->service->consume($identifier, 'api', 5);

        $this->assertTrue($result['allowed']);
        // Should have 100 - 5 = 95 remaining, but consume already did 1, so 96 - 4 = 95
        $this->assertLessThanOrEqual(96, $result['remaining']);
    }

    /**
     * Tests sliding window expiration.
     */
    public function testFixedWindowResetsAfterExpiry(): void
    {
        $identifier = 'user_window';

        // Simulate old window that has expired (more than 60 seconds ago)
        $now = time();
        $cached = (object) ['data' => ['count' => 95, 'window_start' => $now - 90]];
        $this->cache->method('get')->willReturn($cached);

        $result = $this->service->check($identifier);

        // Window expired, so starts fresh. All requests available.
        $this->assertTrue($result['allowed']);
        $this->assertEquals(100, $result['remaining']);
    }

    /**
     * Tests different endpoint limits.
     */
    public function testDifferentEndpointLimits(): void
    {
        $identifier = 'user_endpoints';

        $this->cache->method('get')->willReturn(FALSE);

        // API endpoint: 100 req/min (remaining = 100 before increment)
        $apiResult = $this->service->check($identifier, 'api');
        $this->assertEquals(100, $apiResult['remaining']);

        // AI endpoint: 20 req/min (remaining = 20 before increment)
        $aiResult = $this->service->check($identifier, 'ai');
        $this->assertEquals(20, $aiResult['remaining']);
    }

}
