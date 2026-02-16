<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\jaraba_facturae\Service\FACeClientService;
use Drupal\jaraba_facturae\ValueObject\FACeResponse;
use Drupal\jaraba_facturae\ValueObject\FACeStatus;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the FACeClientService SOAP client.
 *
 * @group jaraba_facturae
 * @coversDefaultClass \Drupal\jaraba_facturae\Service\FACeClientService
 */
class FACeClientServiceTest extends UnitTestCase {

  protected FACeClientService $service;
  protected CertificateManagerService $certificateManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->certificateManager = $this->createMock(CertificateManagerService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new FACeClientService(
      $this->certificateManager,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::sendInvoice
   */
  public function testSendInvoiceReturnsErrorWhenNoSignedXml(): void {
    $document = $this->createMockDocument('', 1);

    $result = $this->service->sendInvoice($document);

    $this->assertInstanceOf(FACeResponse::class, $result);
    $this->assertFalse($result->success);
    $this->assertEquals('LOCAL_ERROR', $result->code);
    $this->assertStringContainsString('no signed XML', $result->description);
  }

  /**
   * @covers ::sendInvoice
   */
  public function testSendInvoiceReturnsErrorWhenNoTenantConfig(): void {
    $document = $this->createMockDocument('<signed-xml/>', 99);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->sendInvoice($document);

    $this->assertInstanceOf(FACeResponse::class, $result);
    $this->assertFalse($result->success);
    $this->assertEquals('LOCAL_ERROR', $result->code);
    $this->assertStringContainsString('No Facturae tenant config', $result->description);
  }

  /**
   * @covers ::queryInvoice
   */
  public function testQueryInvoiceReturnsEmptyStatusWhenNoTenantConfig(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->queryInvoice('REG-001', 99);

    $this->assertInstanceOf(FACeStatus::class, $result);
    $this->assertEquals('REG-001', $result->registryNumber);
    $this->assertEmpty($result->tramitacionCode);
    $this->assertStringContainsString('No tenant config', $result->tramitacionDescription);
  }

  /**
   * @covers ::queryInvoiceList
   */
  public function testQueryInvoiceListReturnsEmptyWhenNoTenantConfig(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->queryInvoiceList(99);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::cancelInvoice
   */
  public function testCancelInvoiceReturnsErrorWhenNoTenantConfig(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->cancelInvoice('REG-001', 'Motivo test', 99);

    $this->assertInstanceOf(FACeResponse::class, $result);
    $this->assertFalse($result->success);
    $this->assertEquals('LOCAL_ERROR', $result->code);
  }

  /**
   * @covers ::testConnection
   */
  public function testConnectionReturnsFalseWhenNoTenantConfig(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->testConnection(99);

    $this->assertFalse($result);
  }

  /**
   * Tests FACeResponse value object success factory.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeResponse
   */
  public function testFACeResponseSuccessFactory(): void {
    $response = FACeResponse::success('0', 'Enviada correctamente', 'REG-2025-001', 'CSV123');

    $this->assertTrue($response->success);
    $this->assertEquals('0', $response->code);
    $this->assertEquals('Enviada correctamente', $response->description);
    $this->assertEquals('REG-2025-001', $response->registryNumber);
    $this->assertEquals('CSV123', $response->csv);
  }

  /**
   * Tests FACeResponse value object error factory.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeResponse
   */
  public function testFACeResponseErrorFactory(): void {
    $response = FACeResponse::error('501', 'Error de validacion');

    $this->assertFalse($response->success);
    $this->assertEquals('501', $response->code);
    $this->assertEquals('Error de validacion', $response->description);
    $this->assertEmpty($response->registryNumber);
    $this->assertEmpty($response->csv);
  }

  /**
   * Tests FACeResponse::toArray returns correct structure.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeResponse
   */
  public function testFACeResponseToArrayStructure(): void {
    $response = FACeResponse::success('0', 'OK', 'REG-001', 'CSV456');
    $array = $response->toArray();

    $this->assertTrue($array['success']);
    $this->assertEquals('0', $array['code']);
    $this->assertEquals('OK', $array['description']);
    $this->assertEquals('REG-001', $array['registry_number']);
    $this->assertEquals('CSV456', $array['csv']);
  }

  /**
   * Tests FACeStatus value object.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeStatus
   */
  public function testFACeStatusToEntityStatusMapping(): void {
    $registered = new FACeStatus('REG-001', '1200', 'Registrada', '', '', '', '');
    $this->assertEquals('registered', $registered->toEntityStatus());

    $rcf = new FACeStatus('REG-001', '1300', 'Registrada en RCF', '', '', '', '');
    $this->assertEquals('registered_rcf', $rcf->toEntityStatus());

    $accounted = new FACeStatus('REG-001', '2400', 'Contabilizada', '', '', '', '');
    $this->assertEquals('accounted', $accounted->toEntityStatus());

    $paid = new FACeStatus('REG-001', '2600', 'Pagada', '', '', '', '');
    $this->assertEquals('paid', $paid->toEntityStatus());

    $unknown = new FACeStatus('REG-001', '9999', 'Desconocido', '', '', '', '');
    $this->assertEquals('sent', $unknown->toEntityStatus());
  }

  /**
   * Tests FACeStatus::hasCancellation detection.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeStatus
   */
  public function testFACeStatusHasCancellation(): void {
    $noCancellation = new FACeStatus('REG-001', '1200', 'Registrada', '', '', '', '');
    $this->assertFalse($noCancellation->hasCancellation());

    $withCancellation = new FACeStatus('REG-001', '1200', 'Registrada', '', '3100', 'Anulacion solicitada', 'Error en datos');
    $this->assertTrue($withCancellation->hasCancellation());
  }

  /**
   * Tests FACeStatus::toArray structure.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\FACeStatus
   */
  public function testFACeStatusToArrayStructure(): void {
    $status = new FACeStatus(
      'REG-001', '2600', 'Pagada', 'Motivo pago',
      '3200', 'Anulacion aceptada', 'Motivo anulacion',
    );

    $array = $status->toArray();

    $this->assertEquals('REG-001', $array['registry_number']);
    $this->assertEquals('2600', $array['tramitacion']['code']);
    $this->assertEquals('Pagada', $array['tramitacion']['description']);
    $this->assertEquals('Motivo pago', $array['tramitacion']['motivo']);
    $this->assertEquals('3200', $array['anulacion']['code']);
    $this->assertEquals('Anulacion aceptada', $array['anulacion']['description']);
    $this->assertEquals('Motivo anulacion', $array['anulacion']['motivo']);
  }

  /**
   * Creates a mock FacturaeDocument entity.
   */
  protected function createMockDocument(string $signedXml, int $tenantId): ContentEntityInterface {
    $document = $this->createMock(ContentEntityInterface::class);
    $fieldMap = [
      'xml_signed' => (object) ['value' => $signedXml],
      'tenant_id' => (object) ['target_id' => $tenantId],
      'facturae_number' => (object) ['value' => 'FAC-2025-001'],
      'status' => (object) ['value' => 'signed'],
    ];
    $document->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? (object) ['value' => NULL, 'target_id' => NULL];
    });
    $document->method('id')->willReturn(42);
    return $document;
  }

}
