<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_funding\Service\OpportunityTrackerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for OpportunityTrackerService.
 *
 * @coversDefaultClass \Drupal\jaraba_funding\Service\OpportunityTrackerService
 * @group jaraba_funding
 */
class OpportunityTrackerServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_funding\Service\OpportunityTrackerService
   */
  protected OpportunityTrackerService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock tenant context.
   *
   * @var object|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = new \stdClass();
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new OpportunityTrackerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Creates a mock entity storage with query support.
   *
   * @param array $ids
   *   The entity IDs to return from execute().
   * @param int $total
   *   The total count to return from count query.
   * @param array $entities
   *   The entities to return from loadMultiple().
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked storage.
   */
  protected function createMockStorage(array $ids, int $total, array $entities): EntityStorageInterface {
    $storage = $this->createMock(EntityStorageInterface::class);

    // Main query mock.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    // Count query mock.
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn($total);

    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($query, $countQuery);

    $storage->method('loadMultiple')
      ->with($ids)
      ->willReturn($entities);

    return $storage;
  }

  /**
   * Creates a simple mock entity with field values.
   *
   * @param array $fields
   *   Associative array of field_name => value.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   A mock entity.
   */
  protected function createMockEntity(array $fields): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();

    $entity->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        $value = $fields[$field_name] ?? NULL;
        return (object) ['value' => $value];
      });

    return $entity;
  }

  /**
   * @covers ::getActiveOpportunities
   */
  public function testGetActiveOpportunitiesReturnsResults(): void {
    $entity1 = $this->createMockEntity(['status' => 'open', 'deadline' => '2026-06-01']);
    $entity2 = $this->createMockEntity(['status' => 'upcoming', 'deadline' => '2026-07-15']);

    $storage = $this->createMockStorage(
      [1, 2],
      2,
      [1 => $entity1, 2 => $entity2],
    );

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $result = $this->service->getActiveOpportunities();

    $this->assertArrayHasKey('opportunities', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertCount(2, $result['opportunities']);
    $this->assertEquals(2, $result['total']);
  }

  /**
   * @covers ::getActiveOpportunities
   */
  public function testGetActiveOpportunitiesReturnsEmptyOnNoResults(): void {
    $storage = $this->createMockStorage([], 0, []);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $result = $this->service->getActiveOpportunities();

    $this->assertArrayHasKey('opportunities', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertEmpty($result['opportunities']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::getActiveOpportunities
   */
  public function testGetActiveOpportunitiesHandlesException(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Database connection failed'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al obtener convocatorias activas'),
        $this->anything(),
      );

    $result = $this->service->getActiveOpportunities();

    $this->assertEmpty($result['opportunities']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::getOpportunitiesFiltered
   */
  public function testGetOpportunitiesFilteredByStatus(): void {
    $entity = $this->createMockEntity(['status' => 'open', 'deadline' => '2026-08-01']);

    $storage = $this->createMockStorage([5], 1, [5 => $entity]);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $result = $this->service->getOpportunitiesFiltered(['status' => 'open']);

    $this->assertArrayHasKey('opportunities', $result);
    $this->assertCount(1, $result['opportunities']);
    $this->assertEquals(1, $result['total']);
  }

  /**
   * @covers ::getOpportunitiesFiltered
   */
  public function testGetOpportunitiesFilteredSkipsNullValues(): void {
    $storage = $this->createMockStorage([], 0, []);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    // Filters with NULL and empty string should be skipped.
    $result = $this->service->getOpportunitiesFiltered([
      'status' => NULL,
      'program' => '',
      'funding_body' => 'EU Commission',
    ]);

    $this->assertArrayHasKey('opportunities', $result);
    $this->assertArrayHasKey('total', $result);
  }

  /**
   * @covers ::getOpportunitiesFiltered
   */
  public function testGetOpportunitiesFilteredHandlesException(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Query failure'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al filtrar convocatorias'),
        $this->anything(),
      );

    $result = $this->service->getOpportunitiesFiltered(['status' => 'open']);

    $this->assertEmpty($result['opportunities']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::checkDeadlines
   */
  public function testCheckDeadlinesReturnsAlertsForUpcomingDeadlines(): void {
    // Create an opportunity with a deadline 5 days from now.
    $deadline = (new \DateTime('+5 days'))->format('Y-m-d');
    $entity = $this->createMockEntity([
      'deadline' => $deadline,
      'alert_days_before' => 15,
      'status' => 'open',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([10])
      ->willReturn([10 => $entity]);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $alerts = $this->service->checkDeadlines();

    $this->assertNotEmpty($alerts);
    $this->assertCount(1, $alerts);
    $this->assertArrayHasKey('opportunity', $alerts[0]);
    $this->assertArrayHasKey('days_until_deadline', $alerts[0]);
    $this->assertArrayHasKey('alert_days', $alerts[0]);
    $this->assertLessThanOrEqual(15, $alerts[0]['days_until_deadline']);
    $this->assertGreaterThanOrEqual(0, $alerts[0]['days_until_deadline']);
  }

  /**
   * @covers ::checkDeadlines
   */
  public function testCheckDeadlinesReturnsEmptyWhenNoOpenOpportunities(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $alerts = $this->service->checkDeadlines();

    $this->assertEmpty($alerts);
  }

  /**
   * @covers ::checkDeadlines
   */
  public function testCheckDeadlinesExcludesDistantDeadlines(): void {
    // Create an opportunity with a deadline 60 days from now (beyond default
    // alert_days_before of 15).
    $deadline = (new \DateTime('+60 days'))->format('Y-m-d');
    $entity = $this->createMockEntity([
      'deadline' => $deadline,
      'alert_days_before' => 15,
      'status' => 'open',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([20]);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([20])
      ->willReturn([20 => $entity]);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $alerts = $this->service->checkDeadlines();

    $this->assertEmpty($alerts);
  }

  /**
   * @covers ::checkDeadlines
   */
  public function testCheckDeadlinesHandlesException(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Storage error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al verificar plazos'),
        $this->anything(),
      );

    $alerts = $this->service->checkDeadlines();

    $this->assertEmpty($alerts);
  }

  /**
   * @covers ::getActiveOpportunities
   */
  public function testGetActiveOpportunitiesRespectsLimitAndOffset(): void {
    $entity = $this->createMockEntity(['status' => 'open', 'deadline' => '2026-09-01']);

    $storage = $this->createMock(EntityStorageInterface::class);

    // Main query: verify range is called with proper arguments.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->expects($this->once())
      ->method('range')
      ->with(10, 5)
      ->willReturnSelf();
    $query->method('execute')->willReturn([100]);

    // Count query.
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn(50);

    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($query, $countQuery);

    $storage->method('loadMultiple')
      ->with([100])
      ->willReturn([100 => $entity]);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_opportunity')
      ->willReturn($storage);

    $result = $this->service->getActiveOpportunities(5, 10);

    $this->assertCount(1, $result['opportunities']);
    $this->assertEquals(50, $result['total']);
  }

}
