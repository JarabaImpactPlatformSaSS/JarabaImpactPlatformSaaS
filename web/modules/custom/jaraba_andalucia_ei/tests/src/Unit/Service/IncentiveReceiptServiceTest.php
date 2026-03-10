<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\IncentiveReceiptService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para IncentiveReceiptService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\IncentiveReceiptService
 * @group jaraba_andalucia_ei
 */
class IncentiveReceiptServiceTest extends UnitTestCase {

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
  protected function createService(?object $brandedPdfService = NULL): IncentiveReceiptService {
    return new IncentiveReceiptService(
      $this->entityTypeManager,
      $this->expedienteService,
      $brandedPdfService,
      $this->logger,
    );
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithoutBrandedPdf(): void {
    $service = $this->createService();
    $this->assertInstanceOf(IncentiveReceiptService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function constructionWithBrandedPdf(): void {
    $brandedPdf = new class {
      public function generateReport(): string {
        return 'pdf-content';
      }
    };
    $service = $this->createService($brandedPdf);
    $this->assertInstanceOf(IncentiveReceiptService::class, $service);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function generarRecibiReturnsNullWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $service = $this->createService();
    $result = $service->generarRecibi(999);

    $this->assertNull($result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function generarRecibiLogsWarningWhenParticipanteNotFound(): void {
    $this->storage->method('load')->with(42)->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('no encontrado'),
        $this->arrayHasKey('@id'),
      );

    $service = $this->createService();
    $service->generarRecibi(42);
  }

}
