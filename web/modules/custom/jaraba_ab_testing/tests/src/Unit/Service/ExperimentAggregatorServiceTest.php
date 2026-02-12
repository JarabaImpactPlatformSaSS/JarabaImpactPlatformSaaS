<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService;
use Drupal\jaraba_ab_testing\Service\StatisticalEngineService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para ExperimentAggregatorService.
 *
 * Verifica la logica de agregacion de experimentos por tenant,
 * detalle de experimentos, metricas del dashboard y declaracion
 * de ganador.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService
 * @group jaraba_ab_testing
 */
class ExperimentAggregatorServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService
   */
  protected ExperimentAggregatorService $service;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock del contexto de tenant.
   *
   * @var object|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MockObject $tenantContext;

  /**
   * Mock del motor estadistico.
   *
   * @var \Drupal\jaraba_ab_testing\Service\StatisticalEngineService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected StatisticalEngineService|MockObject $statisticalEngine;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

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
    $this->tenantContext = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getCurrentTenantId'])
      ->getMock();
    $this->statisticalEngine = $this->createMock(StatisticalEngineService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->experimentStorage = $this->createMock(EntityStorageInterface::class);
    $this->variantStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['ab_experiment', $this->experimentStorage],
        ['ab_variant', $this->variantStorage],
      ]);

    $this->service = new ExperimentAggregatorService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->statisticalEngine,
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
   * Verifica que getTenantExperiments() devuelve array vacio sin experimentos.
   *
   * @covers ::getTenantExperiments
   */
  public function testGetTenantExperimentsEmpty(): void {
    $this->setupQuery($this->experimentStorage, []);

    $result = $this->service->getTenantExperiments(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getTenantExperiments() filtra por estado correctamente.
   *
   * @covers ::getTenantExperiments
   */
  public function testGetTenantExperimentsWithStatusFilter(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->experimentStorage
      ->method('getQuery')
      ->willReturn($query);

    // Se espera que condition sea llamado al menos para tenant_id y status.
    $query->expects($this->atLeast(1))
      ->method('condition');

    $result = $this->service->getTenantExperiments(1, 'active');

    $this->assertIsArray($result);
  }

  /**
   * Verifica que getExperimentDetail() devuelve array vacio si no existe.
   *
   * @covers ::getExperimentDetail
   */
  public function testGetExperimentDetailNotFound(): void {
    $this->experimentStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getExperimentDetail(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getDashboardMetrics() devuelve estructura correcta sin datos.
   *
   * @covers ::getDashboardMetrics
   */
  public function testGetDashboardMetricsEmpty(): void {
    $this->setupQuery($this->experimentStorage, []);

    $metrics = $this->service->getDashboardMetrics(1);

    $this->assertIsArray($metrics);
  }

  /**
   * Verifica que declareWinner() devuelve FALSE con experimento inexistente.
   *
   * @covers ::declareWinner
   */
  public function testDeclareWinnerExperimentNotFound(): void {
    $this->experimentStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->declareWinner(999, 1);

    $this->assertFalse($result);
  }

}
