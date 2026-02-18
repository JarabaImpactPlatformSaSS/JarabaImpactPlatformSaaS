<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_institutional\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_institutional\Service\ProgramManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ProgramManagerService.
 *
 * @coversDefaultClass \Drupal\jaraba_institutional\Service\ProgramManagerService
 * @group jaraba_institutional
 */
class ProgramManagerServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_institutional\Service\ProgramManagerService
   */
  protected ProgramManagerService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock tenant context.
   *
   * @var object|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Create a mock tenant context with getCurrentTenantId().
    $this->tenantContext = new class {

      /**
       * Returns a fixed tenant ID for testing.
       */
      public function getCurrentTenantId(): string {
        return 'tenant_test_001';
      }

    };

    $this->service = new ProgramManagerService(
      $this->entityTypeManager,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Creates a mock entity query that supports fluent chaining.
   *
   * @param mixed $executeResult
   *   The value to return from execute().
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock query.
   */
  protected function createMockQuery($executeResult = []): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($executeResult);
    return $query;
  }

  /**
   * Creates a mock entity storage with a preconfigured query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to return from getQuery().
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock storage.
   */
  protected function createMockStorage(QueryInterface $query): EntityStorageInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    return $storage;
  }

  /**
   * Creates a mock program entity with configurable field values.
   *
   * @param array $fields
   *   Associative array of field_name => value. Special handling for
   *   'tenant_id' (uses target_id) and 'status' (uses value).
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   The mock entity.
   */
  protected function createMockProgram(array $fields): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get', 'set', 'save', 'id'])
      ->getMock();

    $entity->method('id')->willReturn($fields['id'] ?? 1);

    $entity->method('get')->willReturnCallback(function ($fieldName) use ($fields) {
      $fieldObject = new \stdClass();

      if ($fieldName === 'tenant_id') {
        $fieldObject->target_id = $fields['tenant_id'] ?? 'tenant_test_001';
        return $fieldObject;
      }

      if ($fieldName === 'status') {
        $fieldObject->value = $fields['status'] ?? 'draft';
        return $fieldObject;
      }

      if ($fieldName === 'total_budget') {
        $fieldObject->value = $fields['total_budget'] ?? 0;
        return $fieldObject;
      }

      if ($fieldName === 'budget_executed') {
        $fieldObject->value = $fields['budget_executed'] ?? 0;
        return $fieldObject;
      }

      $fieldObject->value = $fields[$fieldName] ?? NULL;
      return $fieldObject;
    });

    return $entity;
  }

  /**
   * @covers ::getActivePrograms
   */
  public function testGetActiveProgramsReturnsResults(): void {
    $countQuery = $this->createMockQuery(3);
    $listQuery = $this->createMockQuery([10 => 10, 11 => 11, 12 => 12]);

    $program1 = $this->createMockProgram(['id' => 10]);
    $program2 = $this->createMockProgram(['id' => 11]);
    $program3 = $this->createMockProgram(['id' => 12]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);
    $storage->method('loadMultiple')
      ->with([10 => 10, 11 => 11, 12 => 12])
      ->willReturn([10 => $program1, 11 => $program2, 12 => $program3]);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getActivePrograms();

    $this->assertArrayHasKey('programs', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertCount(3, $result['programs']);
    $this->assertEquals(3, $result['total']);
  }

  /**
   * @covers ::getActivePrograms
   */
  public function testGetActiveProgramsWithPagination(): void {
    $countQuery = $this->createMockQuery(50);
    $listQuery = $this->createMockQuery([20 => 20, 21 => 21]);

    $program1 = $this->createMockProgram(['id' => 20]);
    $program2 = $this->createMockProgram(['id' => 21]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);
    $storage->method('loadMultiple')
      ->willReturn([20 => $program1, 21 => $program2]);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getActivePrograms(2, 10);

    $this->assertEquals(50, $result['total']);
    $this->assertCount(2, $result['programs']);
  }

  /**
   * @covers ::getActivePrograms
   */
  public function testGetActiveProgramsReturnsEmptyOnNoResults(): void {
    $countQuery = $this->createMockQuery(0);
    $listQuery = $this->createMockQuery([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getActivePrograms();

    $this->assertEmpty($result['programs']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::getActivePrograms
   */
  public function testGetActiveProgramsReturnsEmptyOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al obtener programas activos'),
        $this->anything()
      );

    $result = $this->service->getActivePrograms();

    $this->assertEmpty($result['programs']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::getProgramsFiltered
   */
  public function testGetProgramsFilteredWithStatusFilter(): void {
    $countQuery = $this->createMockQuery(2);
    $listQuery = $this->createMockQuery([1 => 1, 2 => 2]);

    $program1 = $this->createMockProgram(['id' => 1]);
    $program2 = $this->createMockProgram(['id' => 2]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);
    $storage->method('loadMultiple')
      ->willReturn([1 => $program1, 2 => $program2]);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getProgramsFiltered(['status' => 'active']);

    $this->assertCount(2, $result['programs']);
    $this->assertEquals(2, $result['total']);
  }

  /**
   * @covers ::getProgramsFiltered
   */
  public function testGetProgramsFilteredWithArrayStatusFilter(): void {
    $countQuery = $this->createMockQuery(5);
    $listQuery = $this->createMockQuery([1 => 1]);

    $program1 = $this->createMockProgram(['id' => 1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);
    $storage->method('loadMultiple')
      ->willReturn([1 => $program1]);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getProgramsFiltered([
      'status' => ['active', 'reporting'],
    ]);

    $this->assertCount(1, $result['programs']);
    $this->assertEquals(5, $result['total']);
  }

  /**
   * @covers ::getProgramsFiltered
   */
  public function testGetProgramsFilteredWithMultipleFilters(): void {
    $countQuery = $this->createMockQuery(1);
    $listQuery = $this->createMockQuery([5 => 5]);

    $program = $this->createMockProgram(['id' => 5]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);
    $storage->method('loadMultiple')
      ->willReturn([5 => $program]);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getProgramsFiltered([
      'status' => 'active',
      'program_type' => 'FUNDAE',
      'funding_entity' => 'FSE+',
    ]);

    $this->assertCount(1, $result['programs']);
    $this->assertEquals(1, $result['total']);
  }

  /**
   * @covers ::getProgramsFiltered
   */
  public function testGetProgramsFilteredReturnsEmptyOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Query failed'));

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getProgramsFiltered(['status' => 'active']);

    $this->assertEmpty($result['programs']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramSuccessfully(): void {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save', 'id'])
      ->getMock();
    $entity->method('id')->willReturn(42);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Programa institucional creado'),
        $this->anything()
      );

    $result = $this->service->createProgram([
      'name' => 'Programa Formacion 2026',
      'program_type' => 'FUNDAE',
      'funding_entity' => 'FSE+',
      'total_budget' => 150000.00,
    ]);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['program_id']);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramFailsWithoutName(): void {
    $result = $this->service->createProgram([
      'program_type' => 'FUNDAE',
    ]);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('name', $result['error']);
    $this->assertStringContainsString('obligatorio', $result['error']);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramFailsWithoutProgramType(): void {
    $result = $this->service->createProgram([
      'name' => 'Some Program',
    ]);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('program_type', $result['error']);
    $this->assertStringContainsString('obligatorio', $result['error']);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramFailsWithEmptyData(): void {
    $result = $this->service->createProgram([]);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramHandlesStorageException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willThrowException(new \RuntimeException('Storage failure'));

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->createProgram([
      'name' => 'Test',
      'program_type' => 'PIIL',
    ]);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al crear el programa', $result['error']);
  }

  /**
   * @covers ::createProgram
   */
  public function testCreateProgramIncludesOptionalFields(): void {
    $capturedValues = NULL;

    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save', 'id'])
      ->getMock();
    $entity->method('id')->willReturn(99);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willReturnCallback(function ($values) use ($entity, &$capturedValues) {
        $capturedValues = $values;
        return $entity;
      });

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->service->createProgram([
      'name' => 'Full Program',
      'program_type' => 'STO',
      'funding_entity' => 'SEPE',
      'start_date' => '2026-01-01',
      'end_date' => '2026-12-31',
      'total_budget' => 200000,
      'description' => 'Full description',
      'program_code' => 'STO-2026-001',
      'max_participants' => 100,
    ]);

    $this->assertNotNull($capturedValues);
    $this->assertEquals('draft', $capturedValues['status']);
    $this->assertEquals('tenant_test_001', $capturedValues['tenant_id']);
    $this->assertEquals('SEPE', $capturedValues['funding_entity']);
    $this->assertEquals('2026-01-01', $capturedValues['start_date']);
    $this->assertEquals('2026-12-31', $capturedValues['end_date']);
    $this->assertEquals(200000, $capturedValues['total_budget']);
    $this->assertEquals('Full description', $capturedValues['description']);
    $this->assertEquals('STO-2026-001', $capturedValues['program_code']);
    $this->assertEquals(100, $capturedValues['max_participants']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusDraftToActive(): void {
    $program = $this->createMockProgram([
      'id' => 1,
      'tenant_id' => 'tenant_test_001',
      'status' => 'draft',
    ]);
    $program->expects($this->once())->method('set')->with('status', 'active');
    $program->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(1, 'active');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusActiveToReporting(): void {
    $program = $this->createMockProgram([
      'id' => 2,
      'tenant_id' => 'tenant_test_001',
      'status' => 'active',
    ]);
    $program->expects($this->once())->method('set')->with('status', 'reporting');
    $program->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(2)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(2, 'reporting');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusReportingToClosed(): void {
    $program = $this->createMockProgram([
      'id' => 3,
      'tenant_id' => 'tenant_test_001',
      'status' => 'reporting',
    ]);
    $program->expects($this->once())->method('set')->with('status', 'closed');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(3)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(3, 'closed');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusClosedToAudited(): void {
    $program = $this->createMockProgram([
      'id' => 4,
      'tenant_id' => 'tenant_test_001',
      'status' => 'closed',
    ]);
    $program->expects($this->once())->method('set')->with('status', 'audited');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(4)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(4, 'audited');

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusRejectsInvalidTransitionDraftToReporting(): void {
    $program = $this->createMockProgram([
      'id' => 5,
      'tenant_id' => 'tenant_test_001',
      'status' => 'draft',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(5, 'reporting');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no permitida', $result['error']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusRejectsBackwardTransition(): void {
    $program = $this->createMockProgram([
      'id' => 6,
      'tenant_id' => 'tenant_test_001',
      'status' => 'active',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(6)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(6, 'draft');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no permitida', $result['error']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusRejectsTransitionFromAudited(): void {
    $program = $this->createMockProgram([
      'id' => 7,
      'tenant_id' => 'tenant_test_001',
      'status' => 'audited',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(7)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(7, 'active');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no permitida', $result['error']);
    $this->assertStringContainsString('ninguna', $result['error']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusRejectsProgramNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(999, 'active');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrado', $result['error']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusRejectsWrongTenant(): void {
    $program = $this->createMockProgram([
      'id' => 8,
      'tenant_id' => 'other_tenant_999',
      'status' => 'draft',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(8)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->updateStatus(8, 'active');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('permisos', $result['error']);
  }

  /**
   * @covers ::updateStatus
   */
  public function testUpdateStatusHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->updateStatus(1, 'active');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al actualizar el estado', $result['error']);
  }

  /**
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStatsWithPrograms(): void {
    // Set up program storage with 3 queries: total count, active count, IDs.
    $totalCountQuery = $this->createMockQuery(5);
    $activeCountQuery = $this->createMockQuery(2);
    $idsQuery = $this->createMockQuery([1 => 1, 2 => 2]);

    $programStorage = $this->createMock(EntityStorageInterface::class);
    $programStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalCountQuery, $activeCountQuery, $idsQuery);

    // Create mock programs with budget data.
    $program1 = $this->createMockProgram([
      'id' => 1,
      'total_budget' => 100000,
      'budget_executed' => 60000,
    ]);
    $program2 = $this->createMockProgram([
      'id' => 2,
      'total_budget' => 50000,
      'budget_executed' => 25000,
    ]);
    $programStorage->method('loadMultiple')->willReturn([
      1 => $program1,
      2 => $program2,
    ]);

    // Set up participant storage with 3 queries: total count, employed count,
    // with-outcome count.
    $participantTotalQuery = $this->createMockQuery(40);
    $employedQuery = $this->createMockQuery(15);
    $withOutcomeQuery = $this->createMockQuery(30);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($participantTotalQuery, $employedQuery, $withOutcomeQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entityType) use ($programStorage, $participantStorage) {
        return $entityType === 'institutional_program'
          ? $programStorage
          : $participantStorage;
      });

    $stats = $this->service->getDashboardStats();

    $this->assertEquals(5, $stats['total_programs']);
    $this->assertEquals(2, $stats['active_programs']);
    $this->assertEquals(40, $stats['total_participants']);
    $this->assertEquals(150000.0, $stats['total_budget']);
    $this->assertEquals(85000.0, $stats['budget_executed']);
    $this->assertEquals(50.0, $stats['avg_insertion_rate']);
  }

  /**
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStatsWithNoPrograms(): void {
    $totalCountQuery = $this->createMockQuery(0);
    $activeCountQuery = $this->createMockQuery(0);
    $idsQuery = $this->createMockQuery([]);

    $programStorage = $this->createMock(EntityStorageInterface::class);
    $programStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalCountQuery, $activeCountQuery, $idsQuery);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($programStorage);

    $stats = $this->service->getDashboardStats();

    $this->assertEquals(0, $stats['total_programs']);
    $this->assertEquals(0, $stats['active_programs']);
    $this->assertEquals(0, $stats['total_participants']);
    $this->assertEquals(0.0, $stats['total_budget']);
    $this->assertEquals(0.0, $stats['budget_executed']);
    $this->assertEquals(0.0, $stats['avg_insertion_rate']);
  }

  /**
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStatsZeroInsertionRateWhenNoOutcomes(): void {
    $totalCountQuery = $this->createMockQuery(1);
    $activeCountQuery = $this->createMockQuery(1);
    $idsQuery = $this->createMockQuery([1 => 1]);

    $programStorage = $this->createMock(EntityStorageInterface::class);
    $programStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalCountQuery, $activeCountQuery, $idsQuery);

    $program1 = $this->createMockProgram([
      'id' => 1,
      'total_budget' => 10000,
      'budget_executed' => 5000,
    ]);
    $programStorage->method('loadMultiple')->willReturn([1 => $program1]);

    // Participant queries: total=10, employed=0, with_outcome=0.
    $participantTotalQuery = $this->createMockQuery(10);
    $employedQuery = $this->createMockQuery(0);
    $withOutcomeQuery = $this->createMockQuery(0);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($participantTotalQuery, $employedQuery, $withOutcomeQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entityType) use ($programStorage, $participantStorage) {
        return $entityType === 'institutional_program'
          ? $programStorage
          : $participantStorage;
      });

    $stats = $this->service->getDashboardStats();

    $this->assertEquals(0.0, $stats['avg_insertion_rate']);
  }

  /**
   * @covers ::getDashboardStats
   */
  public function testGetDashboardStatsReturnsDefaultsOnException(): void {
    $programStorage = $this->createMock(EntityStorageInterface::class);
    $programStorage->method('getQuery')
      ->willThrowException(new \RuntimeException('Connection lost'));

    $this->entityTypeManager->method('getStorage')
      ->willReturn($programStorage);

    $this->logger->expects($this->once())
      ->method('error');

    $stats = $this->service->getDashboardStats();

    $this->assertEquals(0, $stats['total_programs']);
    $this->assertEquals(0, $stats['active_programs']);
    $this->assertEquals(0, $stats['total_participants']);
    $this->assertEquals(0.0, $stats['total_budget']);
    $this->assertEquals(0.0, $stats['budget_executed']);
    $this->assertEquals(0.0, $stats['avg_insertion_rate']);
  }

  /**
   * @covers ::getProgram
   */
  public function testGetProgramReturnsEntityForCurrentTenant(): void {
    $program = $this->createMockProgram([
      'id' => 10,
      'tenant_id' => 'tenant_test_001',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(10)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getProgram(10);

    $this->assertNotNull($result);
  }

  /**
   * @covers ::getProgram
   */
  public function testGetProgramReturnsNullForNonexistent(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $result = $this->service->getProgram(999);

    $this->assertNull($result);
  }

  /**
   * @covers ::getProgram
   */
  public function testGetProgramReturnsNullForWrongTenant(): void {
    $program = $this->createMockProgram([
      'id' => 20,
      'tenant_id' => 'other_tenant_999',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(20)->willReturn($program);

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Intento de acceso a programa'),
        $this->anything()
      );

    $result = $this->service->getProgram(20);

    $this->assertNull($result);
  }

  /**
   * @covers ::getProgram
   */
  public function testGetProgramReturnsNullOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->willThrowException(new \RuntimeException('Unexpected error'));

    $this->entityTypeManager->method('getStorage')
      ->with('institutional_program')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getProgram(1);

    $this->assertNull($result);
  }

}
