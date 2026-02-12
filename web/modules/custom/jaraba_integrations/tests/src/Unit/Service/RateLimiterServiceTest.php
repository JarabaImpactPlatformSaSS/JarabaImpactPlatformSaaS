<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\jaraba_integrations\Service\RateLimiterService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para RateLimiterService.
 *
 * @covers \Drupal\jaraba_integrations\Service\RateLimiterService
 * @group jaraba_integrations
 */
class RateLimiterServiceTest extends UnitTestCase {

  protected CacheBackendInterface $cache;
  protected LoggerInterface $logger;
  protected RateLimiterService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new RateLimiterService(
      $this->cache,
      $this->logger,
    );
  }

  /**
   * Tests isAllowed returns TRUE when no previous requests.
   */
  public function testIsAllowedWithNoHistory(): void {
    $this->cache->method('get')->willReturn(FALSE);
    $this->cache->expects($this->once())->method('set');

    $this->assertTrue($this->service->isAllowed('test', 1, 100, 3600));
  }

  /**
   * Tests isAllowed returns FALSE when limit reached.
   */
  public function testIsAllowedReturnsFalseAtLimit(): void {
    $now = time();
    $timestamps = array_fill(0, 100, $now);

    $cached = new \stdClass();
    $cached->data = $timestamps;

    $this->cache->method('get')->willReturn($cached);

    $this->assertFalse($this->service->isAllowed('test', 1, 100, 3600));
  }

  /**
   * Tests isAllowed cleans expired timestamps.
   */
  public function testIsAllowedCleansExpired(): void {
    $now = time();
    $oldTimestamps = array_fill(0, 50, $now - 7200);

    $cached = new \stdClass();
    $cached->data = $oldTimestamps;

    $this->cache->method('get')->willReturn($cached);
    $this->cache->expects($this->once())->method('set');

    $this->assertTrue($this->service->isAllowed('test', 1, 100, 3600));
  }

  /**
   * Tests getStatus returns correct remaining count.
   */
  public function testGetStatusReturnsCorrectData(): void {
    $now = time();
    $timestamps = array_fill(0, 30, $now);

    $cached = new \stdClass();
    $cached->data = $timestamps;

    $this->cache->method('get')->willReturn($cached);

    $status = $this->service->getStatus('test', 1, 100, 3600);

    $this->assertEquals(70, $status['remaining']);
    $this->assertEquals(100, $status['limit']);
    $this->assertEquals(30, $status['used']);
  }

  /**
   * Tests reset deletes cache entry.
   */
  public function testResetDeletesCacheEntry(): void {
    $this->cache->expects($this->once())
      ->method('delete')
      ->with('rate_limit:test:1');

    $this->service->reset('test', 1);
  }

}
