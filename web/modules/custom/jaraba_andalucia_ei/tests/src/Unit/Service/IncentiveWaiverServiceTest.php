<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\IncentiveWaiverService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para IncentiveWaiverService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\IncentiveWaiverService
 * @group jaraba_andalucia_ei
 */
class IncentiveWaiverServiceTest extends UnitTestCase {

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
  protected function createService(?object $brandedPdfService = NULL): IncentiveWaiverService {
    return new IncentiveWaiverService(
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
  public function constructionSucceeds(): void {
    $service = $this->createService();
    $this->assertInstanceOf(IncentiveWaiverService::class, $service);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithBrandedPdfSucceeds(): void {
    $brandedPdf = new class {

      /**
       *
       */
      public function generateReport(): string {
        return 'pdf-content';
      }

    };
    $service = $this->createService($brandedPdf);
    $this->assertInstanceOf(IncentiveWaiverService::class, $service);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarRenunciaReturnsNullWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->generarRenuncia(999);

    $this->assertNull($result);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarRenunciaLogsWarningWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(55)->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('no encontrado'),
        $this->arrayHasKey('@id'),
      );

    $service = $this->createService();
    $service->generarRenuncia(55);
  }

}
