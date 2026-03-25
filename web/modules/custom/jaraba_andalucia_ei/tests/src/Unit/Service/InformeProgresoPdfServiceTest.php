<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\InformeProgresoPdfService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para InformeProgresoPdfService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\InformeProgresoPdfService
 * @group jaraba_andalucia_ei
 */
class InformeProgresoPdfServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected InformeProgresoPdfService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock expediente service.
   */
  protected ExpedienteService $expedienteService;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->expedienteService = $this->createMock(ExpedienteService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new InformeProgresoPdfService(
      $this->entityTypeManager,
      NULL,
      $this->expedienteService,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConBrandedPdfNull(): void {
    $service = new InformeProgresoPdfService(
      $this->entityTypeManager,
      NULL,
      $this->expedienteService,
      $this->logger,
    );

    $this->assertInstanceOf(InformeProgresoPdfService::class, $service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConTodosLosServicios(): void {
    $brandedPdf = new class {

      /**
       *
       */
      public function generateReport(array $data, ?int $tenantId = NULL): string {
        return 'private://reports/test.pdf';
      }

    };

    $service = new InformeProgresoPdfService(
      $this->entityTypeManager,
      $brandedPdf,
      $this->expedienteService,
      $this->logger,
    );

    $this->assertInstanceOf(InformeProgresoPdfService::class, $service);
  }

  /**
   * @covers ::generarInforme
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarInformeDevuelveNullSinBrandedPdf(): void {
    $participante = $this->createMock(ProgramaParticipanteEiInterface::class);
    $participante->method('id')->willReturn(1);
    $participante->method('getDniNie')->willReturn('12345678A');
    $participante->method('getFaseActual')->willReturn('acogida');
    $participante->method('getHorasMentoriaIa')->willReturn(0.0);
    $participante->method('getHorasMentoriaHumana')->willReturn(0.0);
    $participante->method('getTotalHorasOrientacion')->willReturn(0.0);
    $participante->method('canTransitToInsercion')->willReturn(FALSE);
    $participante->method('hasField')->willReturn(FALSE);

    $fieldMock = new class {
      public mixed $value = NULL;
      public mixed $target_id = NULL;

      /**
       *
       */
      public function isEmpty(): bool {
        return TRUE;
      }

    };
    $participante->method('get')->willReturn($fieldMock);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('BrandedPdfService not available'));

    $result = $this->service->generarInforme($participante);
    $this->assertNull($result);
  }

}
