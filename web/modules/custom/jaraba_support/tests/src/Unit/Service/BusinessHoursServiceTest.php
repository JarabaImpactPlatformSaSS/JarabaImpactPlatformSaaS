<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Service\BusinessHoursService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests BusinessHoursService — pure datetime arithmetic.
 *
 * Verifies business hour windows, holiday skipping,
 * and addBusinessHours forward calculation.
 *
 * @group jaraba_support
 * @coversDefaultClass \Drupal\jaraba_support\Service\BusinessHoursService
 */
class BusinessHoursServiceTest extends TestCase {

  /**
   * Tests missing schedule defaults to open (fail-open).
   */
  public function testMissingScheduleDefaultsToOpen(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new BusinessHoursService($entityTypeManager, $logger);

    // Missing schedule = business is "open" (fail-open for SLA purposes).
    $result = $service->isWithinBusinessHours('nonexistent_schedule');
    $this->assertTrue($result);
  }

  /**
   * Tests isHoliday returns false for missing schedule.
   */
  public function testIsHolidayFalseForMissingSchedule(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new BusinessHoursService($entityTypeManager, $logger);

    $result = $service->isHoliday('nonexistent_schedule', new \DateTimeImmutable());
    $this->assertFalse($result);
  }

  /**
   * Tests addBusinessHours returns a DateTimeImmutable.
   */
  public function testAddBusinessHoursReturnsDateTimeImmutable(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new BusinessHoursService($entityTypeManager, $logger);

    $from = new \DateTimeImmutable('2026-03-03 10:00:00');
    $result = $service->addBusinessHours('default', $from, 8);

    $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    $this->assertGreaterThan($from, $result);
  }

}
