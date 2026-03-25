<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\ReciboServicioService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ReciboServicioService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ReciboServicioService
 * @group jaraba_andalucia_ei
 */
class ReciboServicioServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;
  protected ExpedienteService $expedienteService;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->expedienteService = $this->createMock(ExpedienteService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);
  }

  /**
   * Creates a fresh service instance.
   */
  protected function createService(?object $brandedPdfService = NULL): ReciboServicioService {
    return new ReciboServicioService(
      $this->entityTypeManager,
      $this->expedienteService,
      $brandedPdfService,
      $this->logger,
    );
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithAllDependencies(): void {
    $service = $this->createService();
    $this->assertInstanceOf(ReciboServicioService::class, $service);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithBrandedPdfService(): void {
    $brandedPdf = new class {

      /**
       *
       */
      public function generateReport(): string {
        return 'pdf-content';
      }

    };
    $service = $this->createService($brandedPdf);
    $this->assertInstanceOf(ReciboServicioService::class, $service);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarReciboReturnsNullWhenEntityTypeNotDefined(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('actuacion_sto')
      ->willReturn(FALSE);

    $service = $this->createService();
    $result = $service->generarRecibo(999);

    $this->assertNull($result);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarReciboReturnsNullWhenActuacionNotFound(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('actuacion_sto')
      ->willReturn(TRUE);
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->generarRecibo(999);

    $this->assertNull($result);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function categoriaMapKeysAreStoTypes(): void {
    $service = $this->createService();
    $reflection = new \ReflectionClass($service);
    $constant = $reflection->getConstant('CATEGORIA_MAP');

    $this->assertIsArray($constant);
    $this->assertArrayHasKey('orientacion_individual', $constant);
    $this->assertArrayHasKey('formacion', $constant);
    $this->assertArrayHasKey('tutoria', $constant);
    $this->assertArrayHasKey('prospeccion', $constant);
    $this->assertArrayHasKey('intermediacion', $constant);
  }

}
