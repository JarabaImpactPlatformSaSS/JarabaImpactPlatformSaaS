<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_analytics\Service\ActivationTrackingService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for ActivationTrackingService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\ActivationTrackingService
 */
class ActivationTrackingServiceTest extends UnitTestCase {

  protected ActivationTrackingService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->service = new ActivationTrackingService(
      $this->entityTypeManager,
      $this->configFactory,
    );
  }

  /**
   * @covers ::isUserActivated
   */
  public function testIsUserActivatedReturnsFalseWhenNoCriteria(): void {
    $user = $this->createMock(AccountInterface::class);

    // No activation criteria config found.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('activation_criteria')
      ->willReturn($storage);

    $result = $this->service->isUserActivated($user, 'empleabilidad');
    $this->assertFalse($result);
  }

  /**
   * @covers ::getActivationProgress
   */
  public function testGetActivationProgressEmptyWhenNoCriteria(): void {
    $user = $this->createMock(AccountInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('activation_criteria')
      ->willReturn($storage);

    $progress = $this->service->getActivationProgress($user, 'demo');
    $this->assertEmpty($progress);
  }

  /**
   * @covers ::calculateActivationRate
   */
  public function testCalculateActivationRateReturnsZeroForNoEvents(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $rate = $this->service->calculateActivationRate('demo', '0');
    $this->assertEquals(0.0, $rate);
  }

}
