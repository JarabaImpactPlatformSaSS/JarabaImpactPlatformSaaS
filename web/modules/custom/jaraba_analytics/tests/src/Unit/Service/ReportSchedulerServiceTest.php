<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_analytics\Entity\ScheduledReport;
use Drupal\jaraba_analytics\Service\ReportSchedulerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the ReportSchedulerService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\ReportSchedulerService
 */
class ReportSchedulerServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\ReportSchedulerService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReportSchedulerService(
      $this->entityTypeManager,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Helper to create a mock ScheduledReport entity.
   *
   * @param array $options
   *   Overrides for entity values.
   *
   * @return \Drupal\jaraba_analytics\Entity\ScheduledReport|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked report entity.
   */
  protected function createMockReport(array $options = []) {
    $report = $this->createMock(ScheduledReport::class);

    $report->method('id')->willReturn($options['id'] ?? '1');
    $report->method('getName')->willReturn($options['name'] ?? 'Test Report');
    $report->method('getScheduleType')->willReturn($options['schedule_type'] ?? 'weekly');
    $report->method('getReportStatus')->willReturn($options['report_status'] ?? 'active');
    $report->method('getRecipients')->willReturn($options['recipients'] ?? ['test@example.com']);
    $report->method('getLastSent')->willReturn($options['last_sent'] ?? NULL);
    $report->method('getNextSend')->willReturn($options['next_send'] ?? time() - 3600);
    $report->method('getTenantId')->willReturn($options['tenant_id'] ?? NULL);
    $report->method('getReportConfig')->willReturn($options['report_config'] ?? ['metric' => 'page_views']);

    $createdField = new \stdClass();
    $createdField->value = $options['created'] ?? time();

    $report->method('get')
      ->willReturnCallback(function ($field) use ($createdField) {
        return match ($field) {
          'created' => $createdField,
          default => new \stdClass(),
        };
      });

    return $report;
  }

  /**
   * Tests getScheduledReports returns list of reports.
   *
   * @covers ::getScheduledReports
   */
  public function testGetScheduledReportsReturnsList(): void {
    $report1 = $this->createMockReport(['id' => '1', 'name' => 'Weekly Sales']);
    $report2 = $this->createMockReport(['id' => '2', 'name' => 'Monthly Traffic']);

    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => '1', 2 => '2']);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $report1, 2 => $report2]);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    $results = $this->service->getScheduledReports();

    $this->assertCount(2, $results);
    $this->assertSame('Weekly Sales', $results[0]['name']);
    $this->assertSame('Monthly Traffic', $results[1]['name']);
    $this->assertArrayHasKey('schedule_type', $results[0]);
    $this->assertArrayHasKey('report_status', $results[0]);
    $this->assertArrayHasKey('recipients', $results[0]);
  }

  /**
   * Tests getScheduledReports with tenant filter.
   *
   * @covers ::getScheduledReports
   */
  public function testGetScheduledReportsFiltersByTenant(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $conditionCalls = [];
    $query->method('condition')
      ->willReturnCallback(function ($field, $value = NULL) use ($query, &$conditionCalls) {
        $conditionCalls[] = [$field, $value];
        return $query;
      });

    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    $results = $this->service->getScheduledReports(42);

    $this->assertEmpty($results);
    $fieldNames = array_column($conditionCalls, 0);
    $this->assertContains('tenant_id', $fieldNames);
  }

  /**
   * Tests getScheduledReports handles exceptions.
   *
   * @covers ::getScheduledReports
   */
  public function testGetScheduledReportsHandlesException(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->logger->expects($this->once())->method('error');

    $results = $this->service->getScheduledReports();

    $this->assertEmpty($results);
  }

  /**
   * Tests processScheduledReports returns 0 when no reports due.
   *
   * @covers ::processScheduledReports
   */
  public function testProcessScheduledReportsReturnsZeroWhenNoDue(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    $result = $this->service->processScheduledReports();

    $this->assertSame(0, $result);
  }

  /**
   * Tests processScheduledReports sends emails to recipients.
   *
   * @covers ::processScheduledReports
   */
  public function testProcessScheduledReportsSendsEmails(): void {
    $report = $this->createMockReport([
      'id' => '1',
      'name' => 'Weekly Report',
      'recipients' => ['user1@example.com', 'user2@example.com'],
      'next_send' => time() - 3600,
    ]);

    $report->expects($this->atLeastOnce())->method('set');
    $report->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);

    // Query for due reports.
    $dueQuery = $this->createMock(QueryInterface::class);
    $dueQuery->method('accessCheck')->willReturnSelf();
    $dueQuery->method('condition')->willReturnSelf();
    $dueQuery->method('sort')->willReturnSelf();
    $dueQuery->method('execute')->willReturn([1 => '1']);

    $storage->method('getQuery')->willReturn($dueQuery);
    $storage->method('loadMultiple')->with([1 => '1'])->willReturn([1 => $report]);
    $storage->method('load')->with(1)->willReturn($report);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    // Mock mail sending - both succeed.
    $this->mailManager->method('mail')
      ->willReturn(['result' => TRUE]);

    $result = $this->service->processScheduledReports();

    $this->assertSame(1, $result);
  }

  /**
   * Tests generateReport returns data for existing report.
   *
   * @covers ::generateReport
   */
  public function testGenerateReportReturnsData(): void {
    $report = $this->createMockReport([
      'id' => '5',
      'name' => 'Monthly Traffic',
      'schedule_type' => 'monthly',
      'report_config' => ['metric' => 'unique_visitors', 'format' => 'csv'],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($report);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    $result = $this->service->generateReport(5);

    $this->assertNotEmpty($result);
    $this->assertSame('Monthly Traffic', $result['title']);
    $this->assertSame('monthly', $result['schedule_type']);
    $this->assertArrayHasKey('generated_at', $result);
    $this->assertArrayHasKey('config', $result);
    $this->assertArrayHasKey('data', $result);
  }

  /**
   * Tests generateReport returns empty for non-existing report.
   *
   * @covers ::generateReport
   */
  public function testGenerateReportReturnsEmptyForMissing(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    $result = $this->service->generateReport(999);

    $this->assertEmpty($result);
  }

  /**
   * Tests processScheduledReports skips reports with invalid emails.
   *
   * @covers ::processScheduledReports
   */
  public function testProcessScheduledReportsSkipsInvalidEmails(): void {
    $report = $this->createMockReport([
      'id' => '1',
      'name' => 'Test Report',
      'recipients' => ['valid@example.com', 'not-an-email', 'also@valid.com'],
    ]);

    $report->expects($this->atLeastOnce())->method('set');
    $report->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => '1']);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $report]);
    $storage->method('load')->with(1)->willReturn($report);

    $this->entityTypeManager->method('getStorage')
      ->with('scheduled_report')
      ->willReturn($storage);

    // Only valid emails should trigger mail sending.
    $this->mailManager->expects($this->exactly(2))
      ->method('mail')
      ->willReturn(['result' => TRUE]);

    // Logger should warn about invalid email.
    $this->logger->expects($this->atLeastOnce())->method('warning');

    $result = $this->service->processScheduledReports();

    $this->assertSame(1, $result);
  }

}
