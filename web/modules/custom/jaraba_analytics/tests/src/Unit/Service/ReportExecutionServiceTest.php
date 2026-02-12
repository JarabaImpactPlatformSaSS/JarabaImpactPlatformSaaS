<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_analytics\Service\ReportExecutionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the ReportExecutionService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\ReportExecutionService
 */
class ReportExecutionServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mocked mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManager;

  /**
   * The mocked entity storage for custom_report.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\ReportExecutionService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);

    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('custom_report')
      ->willReturn($this->storage);

    $this->service = new ReportExecutionService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
      $this->mailManager,
    );
  }

  /**
   * Helper to create a mock CustomReport entity with configurable fields.
   *
   * @param array $options
   *   Overrides: label, report_type, tenant_id, date_range, metrics,
   *   filters, recipients.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked custom report entity.
   */
  protected function createMockReport(array $options = []) {
    $report = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get', 'label', 'set', 'save', 'getMetrics', 'getFilters', 'getRecipients'])
      ->getMock();

    $report->method('label')
      ->willReturn($options['label'] ?? 'Test Report');

    $report->method('getMetrics')
      ->willReturn($options['metrics'] ?? []);
    $report->method('getFilters')
      ->willReturn($options['filters'] ?? []);
    $report->method('getRecipients')
      ->willReturn($options['recipients'] ?? []);

    // Mock the get() method for field access used in executeReport.
    $reportType = $options['report_type'] ?? 'metrics_summary';
    $tenantTargetId = $options['tenant_id'] ?? NULL;
    $dateRange = $options['date_range'] ?? 'last_30_days';

    $report->method('get')
      ->willReturnCallback(function ($field) use ($reportType, $tenantTargetId, $dateRange) {
        return match ($field) {
          'report_type' => (object) ['value' => $reportType],
          'tenant_id' => (object) ['target_id' => $tenantTargetId],
          'date_range' => (object) ['value' => $dateRange],
          'last_executed' => (object) ['value' => NULL],
          default => (object) ['value' => NULL],
        };
      });

    $report->method('set')->willReturnSelf();
    $report->method('save')->willReturn(1);

    return $report;
  }

  /**
   * Helper to create a mock database Select query.
   *
   * @param array $fetchAssocResult
   *   The result for fetchAssoc().
   *
   * @return \Drupal\Core\Database\Query\Select|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked select query.
   */
  protected function createMockSelectQuery(array $fetchAssocResult = []) {
    $query = $this->createMock(Select::class);
    $query->method('fields')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('addField')->willReturnSelf();
    $query->method('groupBy')->willReturnSelf();
    $query->method('orderBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn($fetchAssocResult);
    $statement->method('fetchAll')->willReturn([]);

    $query->method('execute')->willReturn($statement);

    return $query;
  }

  /**
   * Tests executeReport loads the correct entity by report ID.
   *
   * The service should call storage->load() with the provided report ID
   * and use the entity's configured fields to build the query.
   *
   * @covers ::executeReport
   */
  public function testExecuteReportLoadsCorrectEntity(): void {
    $report = $this->createMockReport([
      'label' => 'Monthly Metrics',
      'report_type' => 'metrics_summary',
      'date_range' => 'last_30_days',
      'metrics' => ['total_events', 'sessions'],
    ]);

    $this->storage->expects($this->once())
      ->method('load')
      ->with(42)
      ->willReturn($report);

    $query = $this->createMockSelectQuery([
      'total_events' => '150',
      'sessions' => '30',
      'unique_users' => '20',
    ]);
    $this->database->method('select')->willReturn($query);

    $result = $this->service->executeReport(42);

    $this->assertArrayHasKey('report_id', $result);
    $this->assertSame(42, $result['report_id']);
    $this->assertSame('Monthly Metrics', $result['report_name']);
    $this->assertSame('metrics_summary', $result['report_type']);
    $this->assertArrayHasKey('results', $result);
    $this->assertArrayHasKey('executed_at', $result);
  }

  /**
   * Tests executeReport returns error array for non-existent report.
   *
   * When storage->load() returns NULL, executeReport should return an
   * array with an 'error' key without querying the database.
   *
   * @covers ::executeReport
   */
  public function testExecuteReportReturnsEmptyForInvalidReport(): void {
    $this->storage->method('load')
      ->with(9999)
      ->willReturn(NULL);

    // The database should not be queried.
    $this->database->expects($this->never())->method('select');

    $result = $this->service->executeReport(9999);

    $this->assertArrayHasKey('error', $result);
    $this->assertSame('Informe no encontrado.', $result['error']);
  }

  /**
   * Tests getDateRangeBounds for 'last_7_days' returns correct timestamps.
   *
   * The 'start' should be midnight 7 days ago and 'end' should be
   * approximately the current time.
   *
   * @covers ::getDateRangeBounds
   */
  public function testGetDateRangeBoundsForLast7Days(): void {
    $beforeCall = time();
    $bounds = $this->service->getDateRangeBounds('last_7_days');
    $afterCall = time();

    $this->assertArrayHasKey('start', $bounds);
    $this->assertArrayHasKey('end', $bounds);

    // Start should be midnight 7 days ago.
    $expectedStart = strtotime('-7 days midnight');
    $this->assertSame($expectedStart, $bounds['start']);

    // End should be approximately now (within the test execution window).
    $this->assertGreaterThanOrEqual($beforeCall, $bounds['end']);
    $this->assertLessThanOrEqual($afterCall, $bounds['end']);
  }

  /**
   * Tests getDateRangeBounds for 'last_30_days' returns correct timestamps.
   *
   * @covers ::getDateRangeBounds
   */
  public function testGetDateRangeBoundsForLast30Days(): void {
    $beforeCall = time();
    $bounds = $this->service->getDateRangeBounds('last_30_days');
    $afterCall = time();

    $expectedStart = strtotime('-30 days midnight');
    $this->assertSame($expectedStart, $bounds['start']);

    $this->assertGreaterThanOrEqual($beforeCall, $bounds['end']);
    $this->assertLessThanOrEqual($afterCall, $bounds['end']);
  }

  /**
   * Tests getDateRangeBounds for 'today' returns midnight to end of day.
   *
   * Start should be midnight today, and end should be one second before
   * midnight tomorrow (23:59:59 today).
   *
   * @covers ::getDateRangeBounds
   */
  public function testGetDateRangeBoundsForToday(): void {
    $bounds = $this->service->getDateRangeBounds('today');

    $expectedStart = strtotime('today midnight');
    $expectedEnd = strtotime('tomorrow midnight') - 1;

    $this->assertSame($expectedStart, $bounds['start']);
    $this->assertSame($expectedEnd, $bounds['end']);

    // Verify start is actually midnight.
    $this->assertSame('00:00:00', date('H:i:s', $bounds['start']));

    // Verify end is the last second of today.
    $this->assertSame('23:59:59', date('H:i:s', $bounds['end']));
  }

  /**
   * Tests sendReportEmail calls the mail manager for each valid recipient.
   *
   * The service should load the report, iterate over recipients, and call
   * mailManager->mail() for each valid email address.
   *
   * @covers ::sendReportEmail
   */
  public function testSendReportEmailCallsMailManager(): void {
    $report = $this->createMockReport([
      'label' => 'Weekly Summary',
      'recipients' => ['admin@example.com', 'manager@example.com'],
    ]);

    $this->storage->method('load')
      ->with(10)
      ->willReturn($report);

    // mailManager->mail should be called twice (one per valid recipient).
    $this->mailManager->expects($this->exactly(2))
      ->method('mail')
      ->willReturnCallback(function ($module, $key, $to, $langcode, $params) {
        $this->assertSame('jaraba_analytics', $module);
        $this->assertSame('custom_report', $key);
        $this->assertContains($to, ['admin@example.com', 'manager@example.com']);
        $this->assertSame('es', $langcode);
        $this->assertArrayHasKey('subject', $params);
        $this->assertArrayHasKey('body', $params);
        $this->assertStringContainsString('Informe: Weekly Summary', $params['subject']);

        return ['result' => TRUE];
      });

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('enviado'),
        $this->callback(function ($context) {
          return $context['@count'] === 2;
        })
      );

    $results = ['total_events' => 500, 'sessions' => 100];
    $sent = $this->service->sendReportEmail(10, $results);

    $this->assertTrue($sent);
  }

  /**
   * Tests sendReportEmail returns FALSE when report has no recipients.
   *
   * @covers ::sendReportEmail
   */
  public function testSendReportEmailReturnsFalseForNoRecipients(): void {
    $report = $this->createMockReport([
      'label' => 'No Recipients Report',
      'recipients' => [],
    ]);

    $this->storage->method('load')
      ->with(11)
      ->willReturn($report);

    // Mail should never be called.
    $this->mailManager->expects($this->never())->method('mail');

    $sent = $this->service->sendReportEmail(11, ['data' => 'test']);

    $this->assertFalse($sent);
  }

  /**
   * Tests sendReportEmail returns FALSE when report entity is not found.
   *
   * @covers ::sendReportEmail
   */
  public function testSendReportEmailReturnsFalseForMissingReport(): void {
    $this->storage->method('load')
      ->with(404)
      ->willReturn(NULL);

    $this->mailManager->expects($this->never())->method('mail');

    $sent = $this->service->sendReportEmail(404, []);

    $this->assertFalse($sent);
  }

  /**
   * Tests getDateRangeBounds default case falls back to last_30_days.
   *
   * An unknown date range string should use the default which is
   * equivalent to last_30_days.
   *
   * @covers ::getDateRangeBounds
   */
  public function testGetDateRangeBoundsDefaultFallsBackToLast30Days(): void {
    $bounds = $this->service->getDateRangeBounds('unknown_range');
    $expected = $this->service->getDateRangeBounds('last_30_days');

    $this->assertSame($expected['start'], $bounds['start']);
    // End timestamps should be approximately equal (within 1 second).
    $this->assertEqualsWithDelta($expected['end'], $bounds['end'], 1.0);
  }

  /**
   * Tests getDateRangeBounds for 'yesterday' returns correct range.
   *
   * @covers ::getDateRangeBounds
   */
  public function testGetDateRangeBoundsForYesterday(): void {
    $bounds = $this->service->getDateRangeBounds('yesterday');

    $expectedStart = strtotime('yesterday midnight');
    $expectedEnd = strtotime('today midnight') - 1;

    $this->assertSame($expectedStart, $bounds['start']);
    $this->assertSame($expectedEnd, $bounds['end']);

    // Verify start is midnight yesterday.
    $this->assertSame('00:00:00', date('H:i:s', $bounds['start']));

    // Verify end is 23:59:59 yesterday.
    $this->assertSame('23:59:59', date('H:i:s', $bounds['end']));
  }

  /**
   * Tests executeReport logs info message on successful execution.
   *
   * @covers ::executeReport
   */
  public function testExecuteReportLogsOnSuccess(): void {
    $report = $this->createMockReport([
      'label' => 'Logged Report',
      'report_type' => 'metrics_summary',
      'metrics' => [],
    ]);

    $this->storage->method('load')
      ->with(7)
      ->willReturn($report);

    $query = $this->createMockSelectQuery([
      'total_events' => '50',
      'sessions' => '10',
      'unique_users' => '5',
    ]);
    $this->database->method('select')->willReturn($query);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('ejecutado correctamente'),
        $this->callback(function ($context) {
          return $context['@id'] === 7 && $context['@name'] === 'Logged Report';
        })
      );

    $this->service->executeReport(7);
  }

  /**
   * Tests sendReportEmail skips invalid email addresses.
   *
   * @covers ::sendReportEmail
   */
  public function testSendReportEmailSkipsInvalidEmails(): void {
    $report = $this->createMockReport([
      'label' => 'Mixed Recipients',
      'recipients' => ['valid@example.com', 'not-an-email', 'also@valid.org'],
    ]);

    $this->storage->method('load')
      ->with(15)
      ->willReturn($report);

    // Only 2 valid emails, so mail should be called twice.
    $this->mailManager->expects($this->exactly(2))
      ->method('mail')
      ->willReturn(['result' => TRUE]);

    $sent = $this->service->sendReportEmail(15, ['data' => 'test']);

    $this->assertTrue($sent);
  }

}
