<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_andalucia_ei\Service\ActuacionComputeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ActuacionComputeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ActuacionComputeService
 * @group jaraba_andalucia_ei
 */
class ActuacionComputeServiceTest extends UnitTestCase {

  protected ActuacionComputeService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $participanteStorage;
  protected EntityStorageInterface $inscripcionStorage;
  protected EntityStorageInterface $sesionStorage;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $this->inscripcionStorage = $this->createMock(EntityStorageInterface::class);
    $this->sesionStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['programa_participante_ei', $this->participanteStorage],
        ['inscripcion_sesion_ei', $this->inscripcionStorage],
        ['sesion_programada_ei', $this->sesionStorage],
      ]);

    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $this->service = new ActuacionComputeService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::recalcularIndicadores
   */
  public function testRecalcularIndicadoresParticipanteNotFound(): void {
    $this->participanteStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->recalcularIndicadores(999);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::recalcularIndicadores
   */
  public function testRecalcularIndicadoresSinInscripciones(): void {
    $participante = $this->createParticipanteMock();
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    // No inscripciones asistio.
    $queryAsistio = $this->createQueryMock([]);
    $queryTotal = $this->createQueryMock(0, TRUE);

    $this->inscripcionStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls($queryAsistio, $queryTotal);

    $participante->expects($this->atLeast(1))->method('set');
    $participante->expects($this->once())->method('save');

    $result = $this->service->recalcularIndicadores(1);

    $this->assertArrayHasKey('es_persona_atendida', $result);
    $this->assertFalse($result['es_persona_atendida']);
    $this->assertFalse($result['es_persona_insertada']);
    $this->assertEquals(0.0, $result['horas_orientacion_laboral']);
    $this->assertEquals(0.0, $result['horas_formacion']);
  }

  /**
   * @covers ::recalcularPrograma
   */
  public function testRecalcularProgramaEmpty(): void {
    $query = $this->createQueryMock([]);
    $this->participanteStorage->method('getQuery')->willReturn($query);

    $result = $this->service->recalcularPrograma(1);
    $this->assertEquals(0, $result);
  }

  /**
   * @covers ::recalcularPrograma
   */
  public function testRecalcularProgramaNoTenant(): void {
    $query = $this->createQueryMock([]);
    $this->participanteStorage->method('getQuery')->willReturn($query);

    $result = $this->service->recalcularPrograma();
    $this->assertEquals(0, $result);
  }

  /**
   * Creates a mock participante entity.
   */
  protected function createParticipanteMock(): ContentEntityInterface {
    $participante = $this->createMock(ContentEntityInterface::class);
    $participante->method('id')->willReturn(1);

    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->value = NULL;
    $participante->method('get')->willReturn($fieldList);

    return $participante;
  }

  /**
   * Creates a mock query.
   */
  protected function createQueryMock(array|int $result, bool $isCount = FALSE): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();

    if ($isCount) {
      $query->method('count')->willReturnSelf();
      $query->method('execute')->willReturn($result);
    }
    else {
      $query->method('execute')->willReturn($result);
    }

    return $query;
  }

}
