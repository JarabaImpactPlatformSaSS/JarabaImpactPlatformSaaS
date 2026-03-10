<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\AdaptacionItinerarioService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AdaptacionItinerarioService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AdaptacionItinerarioService
 * @group jaraba_andalucia_ei
 */
class AdaptacionItinerarioServiceTest extends UnitTestCase {

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
    ?object $riesgoService = NULL,
    ?object $tenantContext = NULL,
  ): AdaptacionItinerarioService {
    return new AdaptacionItinerarioService(
      $this->entityTypeManager,
      $this->logger,
      $riesgoService,
      $tenantContext,
    );
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionSucceeds(): void {
    $service = $this->createService();
    $this->assertInstanceOf(AdaptacionItinerarioService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithOptionalServices(): void {
    $riesgo = new class {
      public function evaluarRiesgo(): array {
        return [];
      }
    };
    $tenant = new class {
      public function getCurrentTenantId(): int {
        return 1;
      }
    };

    $service = $this->createService($riesgo, $tenant);
    $this->assertInstanceOf(AdaptacionItinerarioService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function barrierWeightsConstantExists(): void {
    $service = $this->createService();
    $reflection = new \ReflectionClass($service);
    $constant = $reflection->getConstant('BARRIER_WEIGHTS');

    $this->assertIsArray($constant);
    $this->assertArrayHasKey('idioma', $constant);
    $this->assertArrayHasKey('brecha_digital', $constant);
    $this->assertArrayHasKey('carga_cuidados', $constant);
    $this->assertArrayHasKey('situacion_administrativa', $constant);
    $this->assertArrayHasKey('vivienda', $constant);
    $this->assertArrayHasKey('salud_mental', $constant);
    $this->assertArrayHasKey('violencia_genero', $constant);
    $this->assertArrayHasKey('movilidad_geografica', $constant);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function barrierWeightsArePositiveIntegers(): void {
    $reflection = new \ReflectionClass(AdaptacionItinerarioService::class);
    $weights = $reflection->getConstant('BARRIER_WEIGHTS');

    foreach ($weights as $tipo => $peso) {
      $this->assertIsInt($peso, "Peso para barrera '$tipo' debe ser entero.");
      $this->assertGreaterThan(0, $peso, "Peso para barrera '$tipo' debe ser positivo.");
    }
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarBarrerasReturnsDefaultStructureWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->evaluarBarreras(999);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('barreras', $result);
    $this->assertArrayHasKey('complejidad', $result);
    $this->assertArrayHasKey('nivel_complejidad', $result);
    $this->assertSame([], $result['barreras']);
    $this->assertSame(0, $result['complejidad']);
    $this->assertSame('baja', $result['nivel_complejidad']);
  }

}
