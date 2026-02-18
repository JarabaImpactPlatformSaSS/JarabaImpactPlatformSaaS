<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sla\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sla\Service\SlaCalculatorService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for SlaCalculatorService.
 *
 * @coversDefaultClass \Drupal\jaraba_sla\Service\SlaCalculatorService
 * @group jaraba_sla
 */
class SlaCalculatorServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TenantContextService $tenantContext;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected Connection $database;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_sla\Service\SlaCalculatorService
   */
  protected SlaCalculatorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->database = $this->createMock(Connection::class);

    $this->service = new SlaCalculatorService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->database,
    );
  }

  /**
   * Helper to create a mock field item with a value property.
   */
  protected function createFieldItem(mixed $value): object {
    $field = new \stdClass();
    $field->value = $value;
    return $field;
  }

  /**
   * Helper to create a mock field item with a target_id property.
   */
  protected function createFieldItemRef(?int $targetId): object {
    $field = new \stdClass();
    $field->target_id = $targetId;
    return $field;
  }

  /**
   * Tests calculateUptime returns 100% when there are no incidents.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeNoDowntime(): void {
    // Mock incident storage returning no incidents.
    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn([]);

    $incidentStorage = $this->createMock(EntityStorageInterface::class);
    $incidentStorage->method('getQuery')->willReturn($incidentQuery);

    // Mock agreement storage returning no agreements.
    $agreementQuery = $this->createMock(QueryInterface::class);
    $agreementQuery->method('accessCheck')->willReturnSelf();
    $agreementQuery->method('condition')->willReturnSelf();
    $agreementQuery->method('range')->willReturnSelf();
    $agreementQuery->method('execute')->willReturn([]);

    $agreementStorage = $this->createMock(EntityStorageInterface::class);
    $agreementStorage->method('getQuery')->willReturn($agreementQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($incidentStorage, $agreementStorage) {
        return $type === 'sla_incident' ? $incidentStorage : $agreementStorage;
      });

    $result = $this->service->calculateUptime(1, '2026-01-01T00:00:00', '2026-01-31T23:59:59');

    $this->assertSame(100.0, $result['uptime_pct']);
    $this->assertSame(0.0, $result['downtime_minutes']);
    $this->assertTrue($result['sla_met']);
    $this->assertGreaterThan(0, $result['total_minutes']);
  }

  /**
   * Tests calculateUptime with incidents causing downtime.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeWithIncidents(): void {
    // Create a mock incident with 60 minutes of downtime.
    $incident = $this->createMock(\stdClass::class);
    $incident->method('get')->willReturnCallback(function (string $field) {
      $fields = [
        'started_at' => $this->createFieldItem('2026-01-15T10:00:00'),
        'resolved_at' => $this->createFieldItem('2026-01-15T11:00:00'),
        'status' => $this->createFieldItem('resolved'),
      ];
      return $fields[$field] ?? $this->createFieldItem(NULL);
    });

    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn([1]);

    $incidentStorage = $this->createMock(EntityStorageInterface::class);
    $incidentStorage->method('getQuery')->willReturn($incidentQuery);
    $incidentStorage->method('loadMultiple')->with([1])->willReturn([$incident]);

    $agreementQuery = $this->createMock(QueryInterface::class);
    $agreementQuery->method('accessCheck')->willReturnSelf();
    $agreementQuery->method('condition')->willReturnSelf();
    $agreementQuery->method('range')->willReturnSelf();
    $agreementQuery->method('execute')->willReturn([]);

    $agreementStorage = $this->createMock(EntityStorageInterface::class);
    $agreementStorage->method('getQuery')->willReturn($agreementQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($incidentStorage, $agreementStorage) {
        return $type === 'sla_incident' ? $incidentStorage : $agreementStorage;
      });

    $result = $this->service->calculateUptime(1, '2026-01-01T00:00:00', '2026-01-31T23:59:59');

    $this->assertSame(60.0, $result['downtime_minutes']);
    $this->assertLessThan(100.0, $result['uptime_pct']);
    $this->assertGreaterThan(99.0, $result['uptime_pct']);
  }

  /**
   * Tests calculateCredit with standard tier policy (above all thresholds = 0%).
   *
   * @covers ::calculateCredit
   */
  public function testCalculateCreditStandardTier(): void {
    $creditPolicy = [
      ['threshold' => 99.9, 'credit_pct' => 0],
      ['threshold' => 99.0, 'credit_pct' => 10],
      ['threshold' => 95.0, 'credit_pct' => 25],
      ['threshold' => 0, 'credit_pct' => 50],
    ];

    // Uptime 99.95% >= 99.9% threshold => 0% credit.
    $result = $this->service->calculateCredit(99.95, $creditPolicy);
    $this->assertSame(0.0, $result);
  }

  /**
   * Tests calculateCredit with premium tier policy (below first threshold).
   *
   * @covers ::calculateCredit
   */
  public function testCalculateCreditPremiumTier(): void {
    $creditPolicy = [
      ['threshold' => 99.95, 'credit_pct' => 0],
      ['threshold' => 99.5, 'credit_pct' => 10],
      ['threshold' => 99.0, 'credit_pct' => 25],
      ['threshold' => 0, 'credit_pct' => 50],
    ];

    // Uptime 99.7% < 99.95% but >= 99.5% => 10% credit.
    $result = $this->service->calculateCredit(99.7, $creditPolicy);
    $this->assertSame(10.0, $result);
  }

  /**
   * Tests SLA is met when uptime is above the target.
   *
   * @covers ::calculateUptime
   */
  public function testSlaMetWhenAboveTarget(): void {
    // No incidents => 100% uptime.
    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn([]);

    $incidentStorage = $this->createMock(EntityStorageInterface::class);
    $incidentStorage->method('getQuery')->willReturn($incidentQuery);

    // Agreement with 99.9% target.
    $agreement = $this->createMock(\stdClass::class);
    $agreement->method('get')->willReturnCallback(function (string $field) {
      $fields = [
        'uptime_target' => $this->createFieldItem('99.900'),
      ];
      return $fields[$field] ?? $this->createFieldItem(NULL);
    });

    $agreementQuery = $this->createMock(QueryInterface::class);
    $agreementQuery->method('accessCheck')->willReturnSelf();
    $agreementQuery->method('condition')->willReturnSelf();
    $agreementQuery->method('range')->willReturnSelf();
    $agreementQuery->method('execute')->willReturn([1]);

    $agreementStorage = $this->createMock(EntityStorageInterface::class);
    $agreementStorage->method('getQuery')->willReturn($agreementQuery);
    $agreementStorage->method('load')->with(1)->willReturn($agreement);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($incidentStorage, $agreementStorage) {
        return $type === 'sla_incident' ? $incidentStorage : $agreementStorage;
      });

    $result = $this->service->calculateUptime(1, '2026-01-01T00:00:00', '2026-01-31T23:59:59');
    $this->assertTrue($result['sla_met']);
  }

  /**
   * Tests SLA is not met when uptime is below the target.
   *
   * @covers ::calculateUptime
   */
  public function testSlaNotMetWhenBelowTarget(): void {
    // Create incidents totaling significant downtime.
    $incident = $this->createMock(\stdClass::class);
    $incident->method('get')->willReturnCallback(function (string $field) {
      $fields = [
        // 3 days of downtime.
        'started_at' => $this->createFieldItem('2026-01-10T00:00:00'),
        'resolved_at' => $this->createFieldItem('2026-01-13T00:00:00'),
        'status' => $this->createFieldItem('resolved'),
      ];
      return $fields[$field] ?? $this->createFieldItem(NULL);
    });

    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn([1]);

    $incidentStorage = $this->createMock(EntityStorageInterface::class);
    $incidentStorage->method('getQuery')->willReturn($incidentQuery);
    $incidentStorage->method('loadMultiple')->with([1])->willReturn([$incident]);

    // Agreement with 99.99% target.
    $agreement = $this->createMock(\stdClass::class);
    $agreement->method('get')->willReturnCallback(function (string $field) {
      $fields = [
        'uptime_target' => $this->createFieldItem('99.990'),
      ];
      return $fields[$field] ?? $this->createFieldItem(NULL);
    });

    $agreementQuery = $this->createMock(QueryInterface::class);
    $agreementQuery->method('accessCheck')->willReturnSelf();
    $agreementQuery->method('condition')->willReturnSelf();
    $agreementQuery->method('range')->willReturnSelf();
    $agreementQuery->method('execute')->willReturn([1]);

    $agreementStorage = $this->createMock(EntityStorageInterface::class);
    $agreementStorage->method('getQuery')->willReturn($agreementQuery);
    $agreementStorage->method('load')->with(1)->willReturn($agreement);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($incidentStorage, $agreementStorage) {
        return $type === 'sla_incident' ? $incidentStorage : $agreementStorage;
      });

    $result = $this->service->calculateUptime(1, '2026-01-01T00:00:00', '2026-01-31T23:59:59');

    // 3 days = 4320 minutes downtime out of ~44640 total => ~90.3% uptime, well below 99.99%.
    $this->assertFalse($result['sla_met']);
    $this->assertGreaterThan(0.0, $result['downtime_minutes']);
  }

}
