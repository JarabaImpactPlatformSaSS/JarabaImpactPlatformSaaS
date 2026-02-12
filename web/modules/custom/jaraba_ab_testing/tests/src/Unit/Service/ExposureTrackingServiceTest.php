<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ab_testing\Service\ExposureTrackingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para ExposureTrackingService.
 *
 * Verifica la logica de registro de exposiciones y conversiones
 * de visitantes en experimentos A/B.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\ExposureTrackingService
 * @group jaraba_ab_testing
 */
class ExposureTrackingServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_ab_testing\Service\ExposureTrackingService
   */
  protected ExposureTrackingService $service;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->exposureStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['experiment_exposure', $this->exposureStorage],
      ]);

    // Mock del contenedor de Drupal para \Drupal::time().
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1707700000);

    $container = new ContainerBuilder();
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $this->service = new ExposureTrackingService(
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
   * Verifica que recordExposure() crea una entidad ExperimentExposure.
   *
   * @covers ::recordExposure
   */
  public function testRecordExposureCreatesEntity(): void {
    $experimentId = 1;
    $variantId = 'variant_a';
    $visitorId = 'visitor_abc123';

    // Crear mock de la entidad que sera creada.
    $mockEntity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save', 'id'])
      ->getMock();

    $mockEntity->method('id')->willReturn(42);
    $mockEntity->expects($this->once())->method('save');

    $this->exposureStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($experimentId, $variantId, $visitorId) {
        return $values['experiment_id'] === $experimentId
          && $values['variant_id'] === $variantId
          && $values['visitor_id'] === $visitorId
          && $values['converted'] === FALSE;
      }))
      ->willReturn($mockEntity);

    $this->logger
      ->expects($this->once())
      ->method('info');

    $result = $this->service->recordExposure($experimentId, $variantId, $visitorId);

    $this->assertNotEmpty($result);
    $this->assertSame(42, $result['id']);
    $this->assertSame($experimentId, $result['experiment_id']);
    $this->assertSame($variantId, $result['variant_id']);
    $this->assertSame($visitorId, $result['visitor_id']);
    $this->assertArrayHasKey('exposed_at', $result);
  }

  /**
   * Verifica que getExposuresForExperiment() devuelve array vacio sin datos.
   *
   * @covers ::getExposuresForExperiment
   */
  public function testGetExposuresEmpty(): void {
    $experimentId = 99;

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->exposureStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getExposuresForExperiment($experimentId);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
