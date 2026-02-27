<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\jaraba_support\Service\SupportHealthScoreService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SupportHealthScoreService.
 */
#[CoversClass(SupportHealthScoreService::class)]
#[Group('jaraba_support')]
class SupportHealthScoreServiceTest extends UnitTestCase {

  protected SupportHealthScoreService $service;
  protected Connection|MockObject $database;
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SupportHealthScoreService(
      $this->database,
      $this->logger,
      NULL,
    );
  }

  /**
   * Tests that all queries use 'support_ticket_field_data' (translatable table).
   */
  #[Test]
  public function testQueriesUseFieldDataTable(): void {
    $tablesUsed = [];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('0');

    $countQuery = $this->createMock(SelectInterface::class);
    $countQuery->method('execute')->willReturn($statement);

    $selectQuery = $this->createMock(SelectInterface::class);
    $selectQuery->method('addExpression')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('countQuery')->willReturn($countQuery);
    $selectQuery->method('execute')->willReturn($statement);
    $selectQuery->method('join')->willReturnSelf();

    $this->database->method('select')
      ->willReturnCallback(function (string $table) use (&$tablesUsed, $selectQuery) {
        $tablesUsed[] = $table;
        return $selectQuery;
      });

    $this->service->calculateSupportScore(1);

    // All queries must target _field_data or auxiliary tables (ticket_event_log).
    $allowedTables = ['support_ticket_field_data', 'ticket_event_log'];
    foreach ($tablesUsed as $i => $table) {
      $this->assertContains(
        $table,
        $allowedTables,
        "Query #{$i} uses unexpected table '{$table}'.",
      );
    }

    $this->assertNotEmpty($tablesUsed, 'Expected at least one database query.');
  }

  /**
   * Tests score returns 100 on database exception (benefit of the doubt).
   */
  #[Test]
  public function testReturns100OnException(): void {
    $this->database->method('select')
      ->willThrowException(new \Exception('DB unavailable'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Failed to calculate support score'),
        $this->anything(),
      );

    $score = $this->service->calculateSupportScore(1);
    $this->assertSame(100, $score);
  }

  /**
   * Tests score is clamped between 0 and 100.
   */
  #[Test]
  public function testScoreIsClamped(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('0');

    $countQuery = $this->createMock(SelectInterface::class);
    $countQuery->method('execute')->willReturn($statement);

    $selectQuery = $this->createMock(SelectInterface::class);
    $selectQuery->method('addExpression')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('countQuery')->willReturn($countQuery);
    $selectQuery->method('execute')->willReturn($statement);
    $selectQuery->method('join')->willReturnSelf();

    $this->database->method('select')->willReturn($selectQuery);

    $score = $this->service->calculateSupportScore(1);
    $this->assertGreaterThanOrEqual(0, $score);
    $this->assertLessThanOrEqual(100, $score);
  }

}
