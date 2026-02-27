<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\jaraba_support\Service\SupportAnalyticsService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SupportAnalyticsService.
 *
 * Tests the private formatHours helper, overview stats default behavior
 * on database failure, and the structure of the returned stats array.
 */
#[CoversClass(SupportAnalyticsService::class)]
#[Group('jaraba_support')]
class SupportAnalyticsServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected SupportAnalyticsService $service;

  /**
   * Mock database connection.
   */
  protected Connection|MockObject $database;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SupportAnalyticsService(
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests formatHours returns minutes for sub-hour values.
   *
   * Uses reflection to access the private formatHours method.
   * 0.5 hours should return '30min'.
   */
  #[Test]
  public function testFormatHoursReturnsMinutesForSubHour(): void {
    $reflection = new \ReflectionMethod($this->service, 'formatHours');

    $this->assertSame('30min', $reflection->invoke($this->service, 0.5));
  }

  /**
   * Tests formatHours returns '--' for zero.
   *
   * 0 hours should return the default '--' placeholder.
   */
  #[Test]
  public function testFormatHoursReturnsPlaceholderForZero(): void {
    $reflection = new \ReflectionMethod($this->service, 'formatHours');

    $this->assertSame('--', $reflection->invoke($this->service, 0.0));
  }

  /**
   * Tests formatHours returns hours for values >= 1.
   *
   * 2.5 hours should return '2.5h'.
   */
  #[Test]
  public function testFormatHoursReturnsHoursForLargeValues(): void {
    $reflection = new \ReflectionMethod($this->service, 'formatHours');

    $this->assertSame('2.5h', $reflection->invoke($this->service, 2.5));
  }

  /**
   * Tests formatHours returns '--' for negative values.
   *
   * Negative hours (edge case) should return the default '--' placeholder.
   */
  #[Test]
  public function testFormatHoursReturnsPlaceholderForNegative(): void {
    $reflection = new \ReflectionMethod($this->service, 'formatHours');

    $this->assertSame('--', $reflection->invoke($this->service, -1.0));
  }

  /**
   * Tests formatHours rounds correctly at boundary.
   *
   * Exactly 1.0 hours should return '1h' (rounded).
   */
  #[Test]
  public function testFormatHoursAtBoundary(): void {
    $reflection = new \ReflectionMethod($this->service, 'formatHours');

    $this->assertSame('1h', $reflection->invoke($this->service, 1.0));
  }

  /**
   * Tests getOverviewStats returns default array when database throws.
   *
   * When the database connection throws an exception during the
   * initial select query, the service should catch it and return
   * an array with all zero values.
   */
  #[Test]
  public function testGetOverviewStatsReturnsDefaultsOnException(): void {
    $this->database->method('select')
      ->willThrowException(new \Exception('Database unavailable'));

    // Logger should record the error.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Analytics getOverviewStats failed'),
        $this->anything(),
      );

    $result = $this->service->getOverviewStats();

    $this->assertSame(0, $result['total_tickets']);
    $this->assertSame(0, $result['open_tickets']);
    $this->assertSame(0, $result['total_queue']);
    $this->assertSame(0, $result['pending_tickets']);
    $this->assertSame(0, $result['resolved_tickets']);
    $this->assertSame(0, $result['tickets_resolved_today']);
    $this->assertSame(0.0, $result['sla_compliance']);
    $this->assertSame(0.0, $result['csat_score']);
    $this->assertSame('--', $result['avg_response_time']);
    $this->assertSame('--', $result['avg_resolution_time']);
    $this->assertSame(0, $result['my_open']);
  }

  /**
   * Tests getOverviewStats structure with valid database responses.
   *
   * Mocks the full database select chain to return simple values.
   * Verifies all expected keys are present in the returned array.
   */
  #[Test]
  public function testGetOverviewStatsStructure(): void {
    // Create a mock select query that chains properly.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('10');

    $selectQuery = $this->createMock(SelectInterface::class);
    $selectQuery->method('addExpression')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->willReturn($selectQuery);

    $result = $this->service->getOverviewStats();

    // Verify structure: all expected keys present.
    $expectedKeys = [
      'total_tickets',
      'open_tickets',
      'total_queue',
      'pending_tickets',
      'resolved_tickets',
      'tickets_resolved_today',
      'sla_compliance',
      'csat_score',
      'avg_response_time',
      'avg_resolution_time',
      'my_open',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $result, "Missing expected key: {$key}");
    }

    // Verify numeric types for count fields.
    $this->assertIsInt($result['total_tickets']);
    $this->assertIsInt($result['open_tickets']);
    $this->assertIsInt($result['pending_tickets']);
    $this->assertIsInt($result['resolved_tickets']);
    $this->assertIsInt($result['tickets_resolved_today']);
    $this->assertIsInt($result['total_queue']);
    $this->assertIsInt($result['my_open']);

    // Verify float types for rate fields.
    $this->assertIsFloat($result['sla_compliance']);
    $this->assertIsFloat($result['csat_score']);

    // Verify string types for formatted time fields.
    $this->assertIsString($result['avg_response_time']);
    $this->assertIsString($result['avg_resolution_time']);
  }

  /**
   * Tests getOverviewStats calculates SLA compliance correctly.
   *
   * With 10 total tickets and 2 SLA-breached tickets, SLA compliance
   * should be ((10 - 2) / 10) * 100 = 80.0%.
   */
  #[Test]
  public function testGetOverviewStatsCalculatesSlaCompliance(): void {
    // Track which conditions are requested to return different counts.
    $callIndex = 0;
    $returnValues = [
      '10', // total tickets
      '3',  // open tickets
      '1',  // pending tickets
      '5',  // resolved tickets
      '2',  // sla breached
      '1',  // today resolved
      '4.2', // avg csat
      '1800', // avg response seconds
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')
      ->willReturnCallback(function () use (&$callIndex, $returnValues) {
        $value = $returnValues[$callIndex] ?? '0';
        $callIndex++;
        return $value;
      });

    $selectQuery = $this->createMock(SelectInterface::class);
    $selectQuery->method('addExpression')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($selectQuery);

    $result = $this->service->getOverviewStats();

    // SLA compliance: ((10 - 2) / 10) * 100 = 80.0
    $this->assertSame(80.0, $result['sla_compliance']);
    $this->assertSame(10, $result['total_tickets']);
  }

}
