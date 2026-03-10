<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteCompletenessService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ExpedienteCompletenessService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ExpedienteCompletenessService
 * @group jaraba_andalucia_ei
 */
class ExpedienteCompletenessServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected ExpedienteCompletenessService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para expediente_documento.
   */
  protected EntityStorageInterface $documentStorage;

  /**
   * Mock query.
   */
  protected QueryInterface $query;

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
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->documentStorage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('range')->willReturnSelf();

    $this->documentStorage->method('getQuery')->willReturn($this->query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'expediente_documento') {
          return $this->documentStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new ExpedienteCompletenessService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionExitosa(): void {
    $this->assertInstanceOf(ExpedienteCompletenessService::class, $this->service);
  }

  /**
   * @covers ::getCompleteness
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getCompletenessDevuelveArrayConClavesEsperadas(): void {
    // Sin documentos cargados.
    $this->query->method('execute')->willReturn([]);
    $this->documentStorage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getCompleteness(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('sto', $result);
    $this->assertArrayHasKey('programa', $result);
    $this->assertArrayHasKey('mentoria', $result);
    $this->assertArrayHasKey('insercion', $result);
    $this->assertArrayHasKey('total_percent', $result);

    // Cada grupo debe tener las claves esperadas.
    foreach (['sto', 'programa', 'mentoria', 'insercion'] as $group) {
      $this->assertArrayHasKey('label', $result[$group]);
      $this->assertArrayHasKey('required', $result[$group]);
      $this->assertArrayHasKey('completed', $result[$group]);
      $this->assertArrayHasKey('missing', $result[$group]);
      $this->assertArrayHasKey('percent', $result[$group]);
    }
  }

  /**
   * @covers ::getCompleteness
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getCompletenessSinDocumentosDevuelveCeroPorciento(): void {
    $this->query->method('execute')->willReturn([]);
    $this->documentStorage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getCompleteness(1);

    $this->assertSame(0, $result['total_percent']);
    $this->assertSame(0, $result['sto']['percent']);
    $this->assertSame(0, $result['programa']['percent']);
  }

  /**
   * @covers ::isStoComplete
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isStoCompleteDevuelveFalseSinDocumentos(): void {
    $this->query->method('execute')->willReturn([]);
    $this->documentStorage->method('loadMultiple')->willReturn([]);

    $result = $this->service->isStoComplete(1);

    $this->assertIsBool($result);
    $this->assertFalse($result);
  }

  /**
   * @covers ::getCompleteness
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getCompletenessMissingContieneDocumentosRequeridos(): void {
    $this->query->method('execute')->willReturn([]);
    $this->documentStorage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getCompleteness(1);

    // Sin documentos, todos deben estar en missing.
    $this->assertNotEmpty($result['sto']['missing']);
    $this->assertCount(5, $result['sto']['missing']);
    $this->assertEmpty($result['sto']['completed']);
  }

}
