<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_cases\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_legal_cases\Service\CaseManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CaseManagerService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_cases\Service\CaseManagerService
 * @group jaraba_legal_cases
 */
class CaseManagerServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_cases\Service\CaseManagerService
   */
  protected CaseManagerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new CaseManagerService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
    );
  }

  /**
   * Tests getActiveCases returns expected structure with cases and total.
   *
   * @covers ::getActiveCases
   */
  public function testListCases(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturnOnConsecutiveCalls(
      [1, 2, 3],
      3,
    );

    $caseEntity1 = new \stdClass();
    $caseEntity2 = new \stdClass();
    $caseEntity3 = new \stdClass();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2, 3])
      ->willReturn([$caseEntity1, $caseEntity2, $caseEntity3]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('client_case')
      ->willReturn($storage);

    $result = $this->service->getActiveCases(20, 0);

    $this->assertArrayHasKey('cases', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertCount(3, $result['cases']);
    $this->assertSame(3, $result['total']);
  }

  /**
   * Tests getDashboardStats returns all KPI keys with correct counts.
   *
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStats(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // Four consecutive count queries: active, on_hold, completed, this_month.
    $query->method('execute')->willReturnOnConsecutiveCalls(5, 2, 10, 3);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('client_case')
      ->willReturn($storage);

    $stats = $this->service->getDashboardStats();

    $this->assertSame(5, $stats['total_active']);
    $this->assertSame(2, $stats['total_on_hold']);
    $this->assertSame(10, $stats['total_completed']);
    $this->assertSame(3, $stats['total_this_month']);
  }

  /**
   * Tests getCaseByUuid returns a case entity when found.
   *
   * @covers ::getCaseByUuid
   */
  public function testSerializeCase(): void {
    $caseEntity = new \stdClass();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['uuid' => 'test-uuid-1234'])
      ->willReturn([$caseEntity]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('client_case')
      ->willReturn($storage);

    $result = $this->service->getCaseByUuid('test-uuid-1234');

    $this->assertNotNull($result);
    $this->assertSame($caseEntity, $result);
  }

  /**
   * Tests getActiveCases returns empty result on exception.
   *
   * @covers ::getActiveCases
   */
  public function testListCasesReturnsEmptyOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('DB connection failed'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('client_case')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getActiveCases();

    $this->assertSame([], $result['cases']);
    $this->assertSame(0, $result['total']);
  }

  /**
   * Tests getDashboardStats returns zeroes on exception.
   *
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStatsReturnsZeroesOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('client_case')
      ->willReturn($storage);

    $stats = $this->service->getDashboardStats();

    $this->assertSame(0, $stats['total_active']);
    $this->assertSame(0, $stats['total_on_hold']);
    $this->assertSame(0, $stats['total_completed']);
    $this->assertSame(0, $stats['total_this_month']);
  }

}
