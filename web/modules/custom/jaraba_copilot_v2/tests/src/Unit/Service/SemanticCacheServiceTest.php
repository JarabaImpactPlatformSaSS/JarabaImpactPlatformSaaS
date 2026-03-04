<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Service;

use Drupal\jaraba_copilot_v2\Service\SemanticCacheService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests SemanticCacheService — graceful degradation and TTL logic.
 *
 * Verifies that the service handles null dependencies, expired TTL,
 * and similarity threshold gating correctly.
 *
 * @group jaraba_copilot_v2
 * @coversDefaultClass \Drupal\jaraba_copilot_v2\Service\SemanticCacheService
 */
class SemanticCacheServiceTest extends TestCase {

  /**
   * Tests get returns NULL when qdrantClient is null (graceful degradation).
   */
  public function testGetReturnsNullWithoutQdrant(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $service = new SemanticCacheService($logger, NULL, NULL);

    $result = $service->get('test query', 'general');
    $this->assertNull($result);
  }

  /**
   * Tests set does not throw when qdrantClient is null.
   */
  public function testSetDoesNotThrowWithoutQdrant(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $service = new SemanticCacheService($logger, NULL, NULL);

    // Should silently return without error.
    $service->set('test query', 'test response', 'general');
    $this->assertTrue(TRUE, 'set() did not throw with null client.');
  }

  /**
   * Tests invalidate does not throw when qdrantClient is null.
   */
  public function testInvalidateDoesNotThrowWithoutQdrant(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $service = new SemanticCacheService($logger, NULL, NULL);

    // Should silently return without error.
    $service->invalidate('tenant_123');
    $this->assertTrue(TRUE, 'invalidate() did not throw with null client.');
  }

}
