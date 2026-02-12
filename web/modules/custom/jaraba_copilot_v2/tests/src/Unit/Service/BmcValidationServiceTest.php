<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\BmcValidationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the BmcValidationService.
 *
 * @covers \Drupal\jaraba_copilot_v2\Service\BmcValidationService
 * @group jaraba_copilot_v2
 */
class BmcValidationServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\BmcValidationService
   */
  protected BmcValidationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new BmcValidationService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Invokes the protected calculateSemaphore method via reflection.
   *
   * @param int $total
   *   Total number of hypotheses.
   * @param float $ratio
   *   Validated/total ratio.
   *
   * @return string
   *   The semaphore color (GREEN, YELLOW, RED, or GRAY).
   */
  protected function invokeCalculateSemaphore(int $total, float $ratio): string {
    $reflection = new \ReflectionMethod(BmcValidationService::class, 'calculateSemaphore');
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($this->service, $total, $ratio);
  }

  /**
   * Tests that a ratio >= 0.66 returns GREEN semaphore.
   */
  public function testGetSemaphoreColorGreen(): void {
    $result = $this->invokeCalculateSemaphore(10, 0.80);
    $this->assertSame('GREEN', $result);

    $result = $this->invokeCalculateSemaphore(5, 1.0);
    $this->assertSame('GREEN', $result);
  }

  /**
   * Tests that a ratio between 0.33 and 0.66 returns YELLOW semaphore.
   */
  public function testGetSemaphoreColorYellow(): void {
    $result = $this->invokeCalculateSemaphore(10, 0.50);
    $this->assertSame('YELLOW', $result);

    $result = $this->invokeCalculateSemaphore(10, 0.45);
    $this->assertSame('YELLOW', $result);
  }

  /**
   * Tests that a ratio below 0.33 returns RED semaphore.
   */
  public function testGetSemaphoreColorRed(): void {
    $result = $this->invokeCalculateSemaphore(10, 0.10);
    $this->assertSame('RED', $result);

    $result = $this->invokeCalculateSemaphore(10, 0.20);
    $this->assertSame('RED', $result);
  }

  /**
   * Tests that a ratio of 0 with existing hypotheses returns RED.
   */
  public function testGetSemaphoreColorZero(): void {
    $result = $this->invokeCalculateSemaphore(5, 0.0);
    $this->assertSame('RED', $result);
  }

  /**
   * Tests the edge case at the GREEN boundary (ratio exactly 0.66).
   */
  public function testGetSemaphoreColorEdgeCaseAt066(): void {
    $result = $this->invokeCalculateSemaphore(10, 0.66);
    $this->assertSame('GREEN', $result);
  }

  /**
   * Tests the edge case at the YELLOW boundary (ratio exactly 0.33).
   */
  public function testGetSemaphoreColorEdgeCaseAt033(): void {
    $result = $this->invokeCalculateSemaphore(10, 0.33);
    $this->assertSame('YELLOW', $result);
  }

  /**
   * Tests that total=0 returns GRAY regardless of ratio.
   */
  public function testGetSemaphoreColorGrayWhenNoHypotheses(): void {
    $result = $this->invokeCalculateSemaphore(0, 0.0);
    $this->assertSame('GRAY', $result);

    // Even with a high ratio, zero total means GRAY.
    $result = $this->invokeCalculateSemaphore(0, 1.0);
    $this->assertSame('GRAY', $result);
  }

  /**
   * Tests that the BMC_BLOCKS constant contains all 9 blocks.
   */
  public function testBmcBlocksContainsAllNineBlocks(): void {
    $blocks = BmcValidationService::BMC_BLOCKS;
    $this->assertCount(9, $blocks);

    $expectedKeys = ['CS', 'VP', 'CH', 'CR', 'RS', 'KR', 'KA', 'KP', 'C$'];
    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $blocks, "BMC_BLOCKS should contain key: {$key}");
    }
  }

  /**
   * Tests that the service can be instantiated.
   */
  public function testServiceExists(): void {
    $this->assertInstanceOf(BmcValidationService::class, $this->service);
  }

}
