<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_institutional\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_institutional\Service\ParticipantTrackerService;
use Drupal\jaraba_institutional\Service\StoFichaGeneratorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for StoFichaGeneratorService.
 *
 * @coversDefaultClass \Drupal\jaraba_institutional\Service\StoFichaGeneratorService
 * @group jaraba_institutional
 */
class StoFichaGeneratorServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_institutional\Service\StoFichaGeneratorService
   */
  protected StoFichaGeneratorService $service;

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
   * Mock participant tracker service.
   *
   * @var \Drupal\jaraba_institutional\Service\ParticipantTrackerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $participantTracker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->participantTracker = $this->createMock(ParticipantTrackerService::class);

    $this->service = new StoFichaGeneratorService(
      $this->entityTypeManager,
      $this->logger,
      $this->participantTracker,
    );
  }

  /**
   * Creates a mock entity query supporting fluent chaining.
   *
   * @param mixed $executeResult
   *   Value returned by execute().
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
   * Creates a mock participant entity with configurable field values.
   *
   * @param array $fields
   *   Associative array of field_name => value.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   The mock entity.
   */
  protected function createMockParticipant(array $fields): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get', 'save', 'id'])
      ->getMock();

    $entity->method('id')->willReturn($fields['id'] ?? 1);

    $entity->method('get')->willReturnCallback(function ($fieldName) use ($fields) {
      $fieldObject = new \stdClass();

      if ($fieldName === 'tenant_id') {
        $fieldObject->target_id = $fields['tenant_id'] ?? 'tenant_test_001';
        return $fieldObject;
      }

      $fieldObject->value = $fields[$fieldName] ?? NULL;
      return $fieldObject;
    });

    return $entity;
  }

  /**
   * Creates a mock ficha entity with id, set, get, and save methods.
   *
   * @param int $id
   *   The entity ID.
   * @param array $fields
   *   Associative array of field_name => value for get().
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   The mock ficha entity.
   */
  protected function createMockFicha(int $id, array $fields = []): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get', 'set', 'save', 'id'])
      ->getMock();

    $entity->method('id')->willReturn($id);

    $entity->method('get')->willReturnCallback(function ($fieldName) use ($fields) {
      $fieldObject = new \stdClass();
      $fieldObject->value = $fields[$fieldName] ?? NULL;
      $fieldObject->target_id = $fields[$fieldName] ?? NULL;
      return $fieldObject;
    });

    return $entity;
  }

  /**
   * Sets up entity type manager to return specific storages by entity type.
   *
   * @param array $storageMap
   *   Map of entity_type_id => EntityStorageInterface mock.
   */
  protected function configureStorages(array $storageMap): void {
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entityType) use ($storageMap) {
        return $storageMap[$entityType] ?? $this->createMock(EntityStorageInterface::class);
      });
  }

  /**
   * @covers ::generate
   */
  public function testGenerateInitialFichaSuccessfully(): void {
    $participant = $this->createMockParticipant([
      'id' => 1,
      'first_name' => 'Maria',
      'last_name' => 'Garcia',
      'hours_orientation' => 20.0,
      'hours_training' => 40.0,
      'employment_outcome' => '',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(1)->willReturn($participant);

    $fichaEntity = $this->createMockFicha(100);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')->willReturn($fichaEntity);

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Ficha STO generada'),
        $this->anything()
      );

    $result = $this->service->generate(1, 'initial');

    $this->assertTrue($result['success']);
    $this->assertEquals(100, $result['ficha_id']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateProgressFichaSuccessfully(): void {
    $participant = $this->createMockParticipant([
      'id' => 2,
      'first_name' => 'Carlos',
      'last_name' => 'Lopez',
      'hours_orientation' => 10.0,
      'hours_training' => 25.0,
      'employment_outcome' => '',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(2)->willReturn($participant);

    $fichaEntity = $this->createMockFicha(101);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')->willReturn($fichaEntity);

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->generate(2, 'progress');

    $this->assertTrue($result['success']);
    $this->assertEquals(101, $result['ficha_id']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateFinalFichaSuccessfully(): void {
    $participant = $this->createMockParticipant([
      'id' => 3,
      'first_name' => 'Ana',
      'last_name' => 'Martinez',
      'hours_orientation' => 30.0,
      'hours_training' => 60.0,
      'employment_outcome' => 'employed',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(3)->willReturn($participant);

    $fichaEntity = $this->createMockFicha(102);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')->willReturn($fichaEntity);

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->generate(3, 'final');

    $this->assertTrue($result['success']);
    $this->assertEquals(102, $result['ficha_id']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateRejectsInvalidFichaType(): void {
    $result = $this->service->generate(1, 'invalid_type');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no valido', $result['error']);
    $this->assertStringContainsString('initial', $result['error']);
    $this->assertStringContainsString('progress', $result['error']);
    $this->assertStringContainsString('final', $result['error']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateRejectsNonexistentParticipant(): void {
    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(999)->willReturn(NULL);

    $this->configureStorages([
      'program_participant' => $participantStorage,
    ]);

    $result = $this->service->generate(999, 'initial');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrado', $result['error']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateDefaultsToInitialType(): void {
    $participant = $this->createMockParticipant([
      'id' => 5,
      'first_name' => 'Pedro',
      'last_name' => 'Sanchez',
      'hours_orientation' => 0,
      'hours_training' => 0,
      'employment_outcome' => '',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(5)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(110);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->generate(5);

    $this->assertTrue($result['success']);
    $this->assertNotNull($capturedValues);
    $this->assertEquals('initial', $capturedValues['ficha_type']);
    $this->assertEquals('draft', $capturedValues['status']);
    $this->assertFalse($capturedValues['ai_generated']);
    $this->assertEquals('pending', $capturedValues['signature_status']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateSetsCorrectFichaFields(): void {
    $participant = $this->createMockParticipant([
      'id' => 7,
      'first_name' => 'Laura',
      'last_name' => 'Fernandez',
      'hours_orientation' => 15.5,
      'hours_training' => 32.0,
      'employment_outcome' => 'self_employed',
      'tenant_id' => 'tenant_abc',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(7)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(120);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->service->generate(7, 'final');

    $this->assertNotNull($capturedValues);
    $this->assertEquals(7, $capturedValues['participant_id']);
    $this->assertEquals('tenant_abc', $capturedValues['tenant_id']);
    $this->assertEquals('final', $capturedValues['ficha_type']);
    $this->assertEquals('draft', $capturedValues['status']);
    $this->assertFalse($capturedValues['ai_generated']);
    $this->assertEquals('pending', $capturedValues['signature_status']);
    $this->assertNotEmpty($capturedValues['diagnostico_empleabilidad']);
    $this->assertNotEmpty($capturedValues['itinerario_insercion']);
    $this->assertNotEmpty($capturedValues['acciones_orientacion']);
    $this->assertNotEmpty($capturedValues['resultados']);
    $this->assertNotEmpty($capturedValues['created_date']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateHandlesStorageException(): void {
    $participant = $this->createMockParticipant([
      'id' => 8,
      'first_name' => 'Test',
      'last_name' => 'User',
      'hours_orientation' => 0,
      'hours_training' => 0,
      'employment_outcome' => '',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(8)->willReturn($participant);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willThrowException(new \RuntimeException('Storage write failed'));

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al generar ficha STO'),
        $this->anything()
      );

    $result = $this->service->generate(8, 'initial');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al generar la ficha STO', $result['error']);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiSuccessfully(): void {
    $participant = $this->createMockParticipant([
      'id' => 10,
      'first_name' => 'Elena',
      'last_name' => 'Ruiz',
      'hours_orientation' => 25.0,
      'hours_training' => 50.0,
      'employment_outcome' => 'employed',
      'certifications_obtained' => 'DigComp B2',
      'education_level' => 'Bachillerato',
      'employment_status_prior' => 'unemployed',
      'enrollment_date' => '2025-09-01',
      'tenant_id' => 'tenant_ai',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(10)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(200);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Ficha STO generada con IA'),
        $this->anything()
      );

    $result = $this->service->generateWithAi(10, 'initial');

    $this->assertTrue($result['success']);
    $this->assertEquals(200, $result['ficha_id']);

    // Verify AI-specific fields.
    $this->assertNotNull($capturedValues);
    $this->assertTrue($capturedValues['ai_generated']);
    $this->assertEquals('jaraba-institutional-sto-v1', $capturedValues['ai_model_used']);
    $this->assertEquals('draft', $capturedValues['status']);
    $this->assertEquals('pending', $capturedValues['signature_status']);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiRejectsInvalidFichaType(): void {
    $result = $this->service->generateWithAi(1, 'unknown');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no valido', $result['error']);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiRejectsNonexistentParticipant(): void {
    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(999)->willReturn(NULL);

    $this->configureStorages([
      'program_participant' => $participantStorage,
    ]);

    $result = $this->service->generateWithAi(999, 'initial');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrado', $result['error']);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiProgressFichaContent(): void {
    $participant = $this->createMockParticipant([
      'id' => 15,
      'first_name' => 'Juan',
      'last_name' => 'Diaz',
      'hours_orientation' => 12.0,
      'hours_training' => 30.0,
      'employment_outcome' => '',
      'certifications_obtained' => '',
      'education_level' => 'ESO',
      'employment_status_prior' => 'long_term_unemployed',
      'enrollment_date' => '2025-06-15',
      'tenant_id' => 'tenant_002',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(15)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(210);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->generateWithAi(15, 'progress');

    $this->assertTrue($result['success']);
    $this->assertNotNull($capturedValues);
    $this->assertEquals('progress', $capturedValues['ficha_type']);
    $this->assertStringContainsString('progress', $capturedValues['diagnostico_empleabilidad']);
    $this->assertStringContainsString('progress', $capturedValues['itinerario_insercion']);
    $this->assertStringContainsString('SEGUIMIENTO', $capturedValues['acciones_orientacion']);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiHandlesException(): void {
    $participant = $this->createMockParticipant([
      'id' => 20,
      'first_name' => 'Error',
      'last_name' => 'Test',
      'hours_orientation' => 0,
      'hours_training' => 0,
      'employment_outcome' => '',
      'certifications_obtained' => '',
      'education_level' => '',
      'employment_status_prior' => '',
      'enrollment_date' => '',
      'tenant_id' => 'tenant_001',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(20)->willReturn($participant);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willThrowException(new \RuntimeException('AI processing failed'));

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->generateWithAi(20, 'initial');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al generar la ficha STO con IA', $result['error']);
  }

  /**
   * @covers ::exportToPdf
   */
  public function testExportToPdfSuccessfully(): void {
    $ficha = $this->createMockFicha(50);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')->with(50)->willReturn($ficha);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('exportacion PDF'),
        $this->anything()
      );

    $result = $this->service->exportToPdf(50);

    $this->assertTrue($result['success']);
    $this->assertEquals(50, $result['file_id']);
  }

  /**
   * @covers ::exportToPdf
   */
  public function testExportToPdfRejectsNonexistentFicha(): void {
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')->with(999)->willReturn(NULL);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->exportToPdf(999);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['error']);
  }

  /**
   * @covers ::exportToPdf
   */
  public function testExportToPdfHandlesException(): void {
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')
      ->willThrowException(new \RuntimeException('File system error'));

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->exportToPdf(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al exportar la ficha a PDF', $result['error']);
  }

  /**
   * @covers ::signWithPades
   */
  public function testSignWithPadesSuccessfully(): void {
    $ficha = $this->createMockFicha(60, [
      'signature_status' => 'pending',
    ]);
    $ficha->expects($this->once())->method('set')->with('signature_status', 'signed');
    $ficha->expects($this->once())->method('save');

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')->with(60)->willReturn($ficha);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('marcada como firmada'),
        $this->anything()
      );

    $result = $this->service->signWithPades(60);

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::signWithPades
   */
  public function testSignWithPadesRejectsAlreadySigned(): void {
    $ficha = $this->createMockFicha(61, [
      'signature_status' => 'signed',
    ]);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')->with(61)->willReturn($ficha);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->signWithPades(61);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('ya esta firmada', $result['error']);
  }

  /**
   * @covers ::signWithPades
   */
  public function testSignWithPadesRejectsNonexistentFicha(): void {
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')->with(999)->willReturn(NULL);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->signWithPades(999);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['error']);
  }

  /**
   * @covers ::signWithPades
   */
  public function testSignWithPadesHandlesException(): void {
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('load')
      ->willThrowException(new \RuntimeException('Signature service unavailable'));

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->signWithPades(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Error al firmar la ficha', $result['error']);
  }

  /**
   * @covers ::getFichasByParticipant
   */
  public function testGetFichasByParticipantReturnsFichas(): void {
    $ficha1 = $this->createMockFicha(70, ['ficha_type' => 'initial']);
    $ficha2 = $this->createMockFicha(71, ['ficha_type' => 'progress']);

    $query = $this->createMockQuery([70 => 70, 71 => 71]);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('getQuery')->willReturn($query);
    $fichaStorage->method('loadMultiple')
      ->with([70 => 70, 71 => 71])
      ->willReturn([70 => $ficha1, 71 => $ficha2]);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->getFichasByParticipant(5);

    $this->assertCount(2, $result);
  }

  /**
   * @covers ::getFichasByParticipant
   */
  public function testGetFichasByParticipantReturnsEmptyWhenNone(): void {
    $query = $this->createMockQuery([]);

    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('getQuery')->willReturn($query);

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->getFichasByParticipant(999);

    $this->assertEmpty($result);
  }

  /**
   * @covers ::getFichasByParticipant
   */
  public function testGetFichasByParticipantReturnsEmptyOnException(): void {
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('getQuery')
      ->willThrowException(new \RuntimeException('Query failed'));

    $this->configureStorages([
      'sto_ficha' => $fichaStorage,
    ]);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al obtener fichas'),
        $this->anything()
      );

    $result = $this->service->getFichasByParticipant(1);

    $this->assertEmpty($result);
  }

  /**
   * @covers ::generateWithAi
   */
  public function testGenerateWithAiFinalFichaContainsOutcome(): void {
    $participant = $this->createMockParticipant([
      'id' => 30,
      'first_name' => 'Sofia',
      'last_name' => 'Navarro',
      'hours_orientation' => 40.0,
      'hours_training' => 80.0,
      'employment_outcome' => 'self_employed',
      'certifications_obtained' => 'Certificado Profesionalidad Nivel 2',
      'education_level' => 'FP Grado Medio',
      'employment_status_prior' => 'unemployed',
      'enrollment_date' => '2025-03-01',
      'tenant_id' => 'tenant_final',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(30)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(300);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $result = $this->service->generateWithAi(30, 'final');

    $this->assertTrue($result['success']);
    $this->assertNotNull($capturedValues);
    $this->assertEquals('final', $capturedValues['ficha_type']);
    // Verify the AI-generated resultados contain the outcome information.
    $this->assertStringContainsString('autoempleo', $capturedValues['resultados']);
    // Verify the acciones contain final phase content.
    $this->assertStringContainsString('CIERRE', $capturedValues['acciones_orientacion']);
    // Verify diagnostico contains the participant name.
    $this->assertStringContainsString('Sofia', $capturedValues['diagnostico_empleabilidad']);
    $this->assertStringContainsString('Navarro', $capturedValues['diagnostico_empleabilidad']);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateInitialFichaDiagnosticoContainsName(): void {
    $participant = $this->createMockParticipant([
      'id' => 40,
      'first_name' => 'Roberto',
      'last_name' => 'Vega',
      'hours_orientation' => 5.0,
      'hours_training' => 10.0,
      'employment_outcome' => '',
      'tenant_id' => 'tenant_diag',
    ]);

    $participantStorage = $this->createMock(EntityStorageInterface::class);
    $participantStorage->method('load')->with(40)->willReturn($participant);

    $capturedValues = NULL;
    $fichaEntity = $this->createMockFicha(400);
    $fichaStorage = $this->createMock(EntityStorageInterface::class);
    $fichaStorage->method('create')
      ->willReturnCallback(function ($values) use ($fichaEntity, &$capturedValues) {
        $capturedValues = $values;
        return $fichaEntity;
      });

    $this->configureStorages([
      'program_participant' => $participantStorage,
      'sto_ficha' => $fichaStorage,
    ]);

    $this->service->generate(40, 'initial');

    $this->assertNotNull($capturedValues);
    // Diagnostico should mention the participant's name.
    $this->assertStringContainsString('Roberto Vega', $capturedValues['diagnostico_empleabilidad']);
    $this->assertStringContainsString('inicial', $capturedValues['diagnostico_empleabilidad']);
    // Itinerario should reference hours.
    $this->assertStringContainsString('5', $capturedValues['itinerario_insercion']);
    $this->assertStringContainsString('10', $capturedValues['itinerario_insercion']);
    // Initial ficha resultados should be pending.
    $this->assertStringContainsString('Pendientes', $capturedValues['resultados']);
  }

}
