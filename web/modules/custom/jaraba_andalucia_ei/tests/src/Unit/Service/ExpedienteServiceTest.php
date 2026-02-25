<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the ExpedienteService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ExpedienteService
 * @group jaraba_andalucia_ei
 */
class ExpedienteServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected ExpedienteService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('id')->willReturn(1);
    $logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'expediente_documento') {
          return $this->storage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new ExpedienteService(
      $this->entityTypeManager,
      $currentUser,
      NULL,
      NULL,
      $logger,
    );
  }

  /**
   * Tests getCompletuDocumental with no documents.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function completuDocumentalReturnsZeroWithNoDocuments(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getCompletuDocumental(1);

    $this->assertEquals(0, $result['porcentaje']);
    $this->assertEquals(0, $result['completados']);
    // STO categories (excluding sto_otros): sto_dni, sto_empadronamiento,
    // sto_vida_laboral, sto_demanda_empleo, sto_prestaciones, sto_titulo_academico.
    $this->assertEquals(6, $result['total_requeridos']);
    $this->assertNotEmpty($result['por_categoria']);
  }

  /**
   * Tests getCompletuDocumental with some approved STO documents.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function completuDocumentalCountsApprovedDocuments(): void {
    $doc1 = $this->createDocMock('sto_dni', 'aprobado');
    $doc2 = $this->createDocMock('sto_empadronamiento', 'aprobado');
    $doc3 = $this->createDocMock('sto_vida_laboral', 'pendiente');

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([$doc1, $doc2, $doc3]);

    $result = $this->service->getCompletuDocumental(1);

    $this->assertEquals(2, $result['completados']);
    $this->assertGreaterThan(0, $result['porcentaje']);
    $this->assertTrue($result['por_categoria']['sto_dni']['completo']);
    $this->assertTrue($result['por_categoria']['sto_empadronamiento']['completo']);
    $this->assertFalse($result['por_categoria']['sto_vida_laboral']['completo']);
  }

  /**
   * Tests verificarDocumentosCompletos returns false when incomplete.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function verificarDocumentosCompletosReturnsFalseWhenIncomplete(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([]);

    $this->assertFalse($this->service->verificarDocumentosCompletos(1));
  }

  /**
   * Tests verificarDocumentosCompletos returns true when all STO approved.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function verificarDocumentosCompletosReturnsTrueWhenComplete(): void {
    $stoCategories = ['sto_dni', 'sto_empadronamiento', 'sto_vida_laboral', 'sto_demanda_empleo', 'sto_prestaciones', 'sto_titulo_academico'];
    $docs = [];
    foreach ($stoCategories as $cat) {
      $docs[] = $this->createDocMock($cat, 'aprobado');
    }

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn(range(1, 6));

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn($docs);

    $this->assertTrue($this->service->verificarDocumentosCompletos(1));
  }

  /**
   * Creates a mock ExpedienteDocumento.
   */
  protected function createDocMock(string $categoria, string $estadoRevision): ExpedienteDocumentoInterface {
    $doc = $this->createMock(ExpedienteDocumentoInterface::class);
    $doc->method('getCategoria')->willReturn($categoria);
    $doc->method('getEstadoRevision')->willReturn($estadoRevision);
    return $doc;
  }

}
