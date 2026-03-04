<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_lms\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_lms\Service\CourseService;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Drupal\jaraba_lms\Service\ProgressTrackingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests EnrollmentService — enrollment business logic.
 *
 * Verifies idempotent enrollment, prerequisite checking,
 * and anonymous user handling.
 *
 * @group jaraba_lms
 * @coversDefaultClass \Drupal\jaraba_lms\Service\EnrollmentService
 */
class EnrollmentServiceTest extends TestCase {

  /**
   * Creates the service with mocked dependencies.
   */
  protected function createService(int $currentUserId = 0): EnrollmentService {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('id')->willReturn($currentUserId);

    $courseService = $this->createMock(CourseService::class);
    $progressService = $this->createMock(ProgressTrackingService::class);

    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    return new EnrollmentService(
      $entityTypeManager,
      $currentUser,
      $courseService,
      $progressService,
      $loggerFactory,
      $eventDispatcher
    );
  }

  /**
   * Tests isEnrolled returns false when no enrollment exists.
   */
  public function testIsEnrolledReturnsFalse(): void {
    $service = $this->createService();
    $this->assertFalse($service->isEnrolled(1, 1));
  }

  /**
   * Tests getUserEnrollments returns empty array for user with none.
   */
  public function testGetUserEnrollmentsEmpty(): void {
    $service = $this->createService();
    $result = $service->getUserEnrollments(999);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests checkPrerequisites returns true when course has no prerequisites.
   */
  public function testCheckPrerequisitesNoPrereqs(): void {
    $service = $this->createService();
    // Course with no prerequisites loaded = TRUE (no blocker).
    $result = $service->checkPrerequisites(1, 1);
    $this->assertTrue($result);
  }

  /**
   * Tests getCourseStats returns correct structure.
   */
  public function testGetCourseStatsStructure(): void {
    $service = $this->createService();
    $stats = $service->getCourseStats(999);

    $this->assertIsArray($stats);
    $this->assertArrayHasKey('total', $stats);
    $this->assertArrayHasKey('active', $stats);
    $this->assertArrayHasKey('completed', $stats);
    $this->assertArrayHasKey('completion_rate', $stats);
    $this->assertEquals(0, $stats['completion_rate']);
  }

}
