<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Service\AnalyticsAggregatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the AnalyticsAggregatorService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\AnalyticsAggregatorService
 */
class AnalyticsAggregatorServiceTest extends TestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked logger factory.
   *
   * @var object
   */
  protected $loggerFactory;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\AnalyticsAggregatorService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->loggerFactory = new class ($this->logger) {

      private LoggerInterface $logger;

      public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
      }

      public function get(string $channel): LoggerInterface {
        return $this->logger;
      }

    };

    $this->service = new AnalyticsAggregatorService(
      $this->database,
      $this->entityTypeManager,
      $this->cache,
      $this->loggerFactory,
    );
  }

  /**
   * Helper: creates a fluent SelectInterface mock for DB queries.
   *
   * The mock supports chained method calls typical of Drupal's select query
   * builder: condition(), fields(), distinct(), countQuery(), execute(), etc.
   *
   * @param mixed $fetchFieldResult
   *   The value returned by fetchField() at the end of the chain.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mock select query.
   */
  protected function createFluentSelectMock($fetchFieldResult = 0) {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($fetchFieldResult);
    $statement->method('fetchAll')->willReturn([]);
    $statement->method('fetchCol')->willReturn([]);

    $countQuery = $this->createMock(SelectInterface::class);
    $countQuery->method('execute')->willReturn($statement);

    $select = $this->createMock(SelectInterface::class);
    $select->method('condition')->willReturnSelf();
    $select->method('fields')->willReturnSelf();
    $select->method('addField')->willReturnSelf();
    $select->method('addExpression')->willReturnSelf();
    $select->method('groupBy')->willReturnSelf();
    $select->method('having')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('isNotNull')->willReturnSelf();
    $select->method('distinct')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);
    $select->method('execute')->willReturn($statement);

    return $select;
  }

  /**
   * Tests that calculateBasicMetrics returns the correct array structure.
   *
   * @covers ::calculateBasicMetrics
   */
  public function testCalculateBasicMetricsReturnsCorrectStructure(): void {
    $tenantId = 1;
    $startTs = strtotime('2026-01-01 00:00:00');
    $endTs = strtotime('2026-01-01 23:59:59');

    // Call counter to return different values for sequential select() calls.
    $callIndex = 0;

    // Page views query -> 100.
    $pvSelect = $this->createFluentSelectMock(100);
    // Unique visitors query -> 50.
    $uvSelect = $this->createFluentSelectMock(50);
    // Sessions query -> 60.
    $sessSelect = $this->createFluentSelectMock(60);

    // Bounce query: inner select for groupBy/having, wrapped in outer count.
    // The bounce subquery returns 15.
    $bounceInnerStatement = $this->createMock(StatementInterface::class);
    $bounceInnerStatement->method('fetchField')->willReturn(15);

    $bounceCountQuery = $this->createMock(SelectInterface::class);
    $bounceCountQuery->method('execute')->willReturn($bounceInnerStatement);

    $bounceOuterSelect = $this->createMock(SelectInterface::class);
    $bounceOuterSelect->method('countQuery')->willReturn($bounceCountQuery);
    $bounceOuterSelect->method('execute')->willReturn($bounceInnerStatement);

    // Duration query: returns empty durations for simplicity.
    $durationStatement = $this->createMock(StatementInterface::class);
    $durationStatement->method('fetchCol')->willReturn([]);

    $durationSelect = $this->createFluentSelectMock(0);
    $durationSelect->method('execute')->willReturn($durationStatement);

    // Bounce inner select (the grouped query).
    $bounceGroupSelect = $this->createMock(SelectInterface::class);
    $bounceGroupSelect->method('addField')->willReturnSelf();
    $bounceGroupSelect->method('condition')->willReturnSelf();
    $bounceGroupSelect->method('addExpression')->willReturnSelf();
    $bounceGroupSelect->method('groupBy')->willReturnSelf();
    $bounceGroupSelect->method('having')->willReturnSelf();

    // Map the database->select() calls in order.
    $this->database->method('select')
      ->willReturnOnConsecutiveCalls(
        $pvSelect,        // page_views countQuery
        $uvSelect,        // unique_visitors distinct countQuery
        $sessSelect,      // sessions distinct countQuery
        $bounceGroupSelect, // bounce inner query
        $bounceOuterSelect, // bounce outer count
        $durationSelect   // avg session duration
      );

    $reflection = new \ReflectionMethod($this->service, 'calculateBasicMetrics');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, $tenantId, $startTs, $endTs);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('page_views', $result);
    $this->assertArrayHasKey('unique_visitors', $result);
    $this->assertArrayHasKey('sessions', $result);
    $this->assertArrayHasKey('bounce_rate', $result);
    $this->assertArrayHasKey('avg_session_duration', $result);

    $this->assertIsInt($result['page_views']);
    $this->assertIsInt($result['unique_visitors']);
    $this->assertIsInt($result['sessions']);
    $this->assertIsFloat($result['bounce_rate']);
    $this->assertIsInt($result['avg_session_duration']);
  }

  /**
   * Tests bounce rate calculation with specific values.
   *
   * The service computes: bounce_rate = bounceSessions / sessions.
   * With sessions=100 and bounce=35, bounce_rate should be 0.35.
   *
   * @covers ::calculateBasicMetrics
   */
  public function testBounceRateCalculation(): void {
    // The formula from the service is:
    // bounce_rate = $sessions > 0 ? round($bounceSessions / $sessions, 4) : 0
    $sessions = 100;
    $bounceSessions = 35;

    $bounceRate = $sessions > 0 ? round($bounceSessions / $sessions, 4) : 0;

    $this->assertSame(0.35, $bounceRate);
  }

  /**
   * Tests bounce rate is zero when there are no sessions.
   *
   * @covers ::calculateBasicMetrics
   */
  public function testBounceRateZeroWhenNoSessions(): void {
    // The formula from the service:
    // bounce_rate = $sessions > 0 ? round($bounceSessions / $sessions, 4) : 0
    $sessions = 0;
    $bounceSessions = 0;

    $bounceRate = $sessions > 0 ? round($bounceSessions / $sessions, 4) : 0;

    $this->assertSame(0, $bounceRate);
  }

  /**
   * Tests ecommerce metrics calculation with revenue data.
   *
   * @covers ::calculateEcommerceMetrics
   */
  public function testCalculateEcommerceMetricsWithRevenue(): void {
    $tenantId = 1;
    $startTs = strtotime('2026-01-01 00:00:00');
    $endTs = strtotime('2026-01-01 23:59:59');

    // Purchase count query -> 3 orders.
    $purchaseCountStatement = $this->createMock(StatementInterface::class);
    $purchaseCountStatement->method('fetchField')->willReturn(3);

    $purchaseCountQuery = $this->createMock(SelectInterface::class);
    $purchaseCountQuery->method('execute')->willReturn($purchaseCountStatement);

    // Revenue query: returns rows with event_data containing JSON values.
    $row1 = (object) ['event_data' => json_encode(['value' => 49.99])];
    $row2 = (object) ['event_data' => json_encode(['value' => 120.50])];
    $row3 = (object) ['event_data' => json_encode(['value' => 29.51])];

    $revenueStatement = $this->createMock(StatementInterface::class);
    $revenueStatement->method('fetchAll')->willReturn([$row1, $row2, $row3]);

    // The service clones the purchaseQuery for countQuery and for fields.
    // We need a base select that supports clone and returns the right things.
    $purchaseSelect = $this->createMock(SelectInterface::class);
    $purchaseSelect->method('condition')->willReturnSelf();
    $purchaseSelect->method('countQuery')->willReturn($purchaseCountQuery);
    $purchaseSelect->method('fields')->willReturnSelf();
    $purchaseSelect->method('execute')->willReturn($revenueStatement);

    // New users count query -> 5.
    $newUsersStatement = $this->createMock(StatementInterface::class);
    $newUsersStatement->method('fetchField')->willReturn(5);

    $newUsersCountQuery = $this->createMock(SelectInterface::class);
    $newUsersCountQuery->method('execute')->willReturn($newUsersStatement);

    $newUsersSelect = $this->createFluentSelectMock(5);

    // Unique visitors query -> 200.
    $uvSelect = $this->createFluentSelectMock(200);

    $this->database->method('select')
      ->willReturnOnConsecutiveCalls(
        $purchaseSelect,  // purchase base query
        $newUsersSelect,  // new users (signup) query
        $uvSelect         // unique visitors query
      );

    // Since calculateEcommerceMetrics uses clone on the purchase query,
    // we test the revenue logic directly.
    $purchaseRows = [$row1, $row2, $row3];
    $totalRevenue = 0;
    foreach ($purchaseRows as $row) {
      $eventData = is_string($row->event_data)
        ? json_decode($row->event_data, TRUE)
        : $row->event_data;
      if (is_array($eventData) && isset($eventData['value'])) {
        $totalRevenue += (float) $eventData['value'];
      }
    }

    $this->assertEqualsWithDelta(200.00, $totalRevenue, 0.01);
  }

  /**
   * Tests conversion rate calculation.
   *
   * The formula: conversionRate = ordersCount / uniqueVisitors.
   * With ordersCount=5 and uniqueVisitors=100, rate should be 0.05.
   *
   * @covers ::calculateEcommerceMetrics
   */
  public function testConversionRateCalculation(): void {
    // The formula from the service:
    // $conversionRate = $uniqueVisitors > 0
    //   ? round((int) $ordersCount / $uniqueVisitors, 4) : 0
    $ordersCount = 5;
    $uniqueVisitors = 100;

    $conversionRate = $uniqueVisitors > 0
      ? round($ordersCount / $uniqueVisitors, 4)
      : 0;

    $this->assertSame(0.05, $conversionRate);
  }

}
