<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ab_testing\Service\ResultCalculationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para ResultCalculationService.
 *
 * Verifica la logica de calculo de resultados estadisticos,
 * auto-parada y declaracion de ganador para experimentos A/B.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\ResultCalculationService
 * @group jaraba_ab_testing
 */
class ResultCalculationServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_ab_testing\Service\ResultCalculationService
   */
  protected ResultCalculationService $service;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock del storage de exposiciones.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $exposureStorage;

  /**
   * Mock del storage de resultados.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $resultStorage;

  /**
   * Mock del storage de experimentos.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $experimentStorage;

  /**
   * Mock del storage de variantes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $variantStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->exposureStorage = $this->createMock(EntityStorageInterface::class);
    $this->resultStorage = $this->createMock(EntityStorageInterface::class);
    $this->experimentStorage = $this->createMock(EntityStorageInterface::class);
    $this->variantStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['experiment_exposure', $this->exposureStorage],
        ['experiment_result', $this->resultStorage],
        ['ab_experiment', $this->experimentStorage],
        ['ab_variant', $this->variantStorage],
      ]);

    $this->service = new ResultCalculationService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Helper para crear un mock de campo con un valor simple.
   *
   * @param mixed $value
   *   El valor que devolvera el campo.
   *
   * @return object
   *   Un objeto que actua como field item list.
   */
  protected function createFieldValue(mixed $value): object {
    return (object) ['value' => $value];
  }

  /**
   * Configura un query mock que devuelve los IDs especificados.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage
   *   El mock de storage al que asociar el query.
   * @param array $ids
   *   Los IDs que devolvera el query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   *   El mock de query configurado.
   */
  protected function setupQuery(EntityStorageInterface|MockObject $storage, array $ids): QueryInterface|MockObject {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    return $query;
  }

  /**
   * Verifica que calculateResults() devuelve array vacio sin exposiciones.
   *
   * @covers ::calculateResults
   */
  public function testCalculateResultsEmpty(): void {
    $experimentId = 1;

    $this->setupQuery($this->exposureStorage, []);

    $this->logger
      ->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('No hay exposiciones'),
        $this->arrayHasKey('@experiment')
      );

    $result = $this->service->calculateResults($experimentId);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que checkAutoStop() devuelve FALSE sin resultados significativos.
   *
   * @covers ::checkAutoStop
   */
  public function testCheckAutoStopNoResults(): void {
    $experimentId = 1;

    $this->setupQuery($this->resultStorage, []);

    $result = $this->service->checkAutoStop($experimentId);

    $this->assertFalse($result);
  }

}
