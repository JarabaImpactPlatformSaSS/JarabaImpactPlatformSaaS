<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiEmprendimientoBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiEmprendimientoBridgeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiEmprendimientoBridgeService
 * @group jaraba_andalucia_ei
 */
class EiEmprendimientoBridgeServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);
  }

  /**
   * Creates a fresh service instance.
   */
  protected function createService(
    ?object $canvasService = NULL,
    ?object $mvpValidationService = NULL,
    ?object $projectionService = NULL,
    ?object $sroiCalculatorService = NULL,
    ?object $tenantContext = NULL,
  ): EiEmprendimientoBridgeService {
    return new EiEmprendimientoBridgeService(
      $this->entityTypeManager,
      $this->logger,
      $canvasService,
      $mvpValidationService,
      $projectionService,
      $sroiCalculatorService,
      $tenantContext,
    );
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithAllNulls(): void {
    $service = $this->createService();
    $this->assertInstanceOf(EiEmprendimientoBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithSomeServices(): void {
    $canvasService = new class {
      public function getCanvas(): array {
        return [];
      }
    };
    $sroiCalculator = new class {
      public function calculate(): float {
        return 1.5;
      }
    };

    $service = $this->createService(
      canvasService: $canvasService,
      sroiCalculatorService: $sroiCalculator,
    );
    $this->assertInstanceOf(EiEmprendimientoBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithAllServices(): void {
    $canvas = new class {};
    $mvp = new class {};
    $projection = new class {};
    $sroi = new class {};
    $tenant = new class {};

    $service = $this->createService($canvas, $mvp, $projection, $sroi, $tenant);
    $this->assertInstanceOf(EiEmprendimientoBridgeService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function crearPlanDesdeParticipanteReturnsErrorWhenNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->crearPlanDesdeParticipante(999);

    $this->assertFalse($result['success']);
    $this->assertNull($result['plan_id']);
    $this->assertStringContainsString('no encontrado', $result['message']);
  }

}
