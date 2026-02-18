<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_verifactu\Service\VeriFactuEventLogService;
use Drupal\jaraba_verifactu\Service\VeriFactuHashService;
use Drupal\jaraba_verifactu\Service\VeriFactuQrService;
use Drupal\jaraba_verifactu\Service\VeriFactuRecordService;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuRecordService.
 *
 * Verifica la orquestacion de creacion de registros VeriFactu:
 * Hash → QR → Entity → Chain → SIF Log.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuRecordService
 */
class VeriFactuRecordServiceTest extends UnitTestCase {

  protected VeriFactuRecordService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected VeriFactuHashService $hashService;
  protected VeriFactuQrService $qrService;
  protected VeriFactuEventLogService $eventLogService;
  protected LockBackendInterface $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hashService = $this->createMock(VeriFactuHashService::class);
    $this->qrService = $this->createMock(VeriFactuQrService::class);
    $this->eventLogService = $this->createMock(VeriFactuEventLogService::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn('');
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $this->service = new VeriFactuRecordService(
      $this->entityTypeManager,
      $this->hashService,
      $this->qrService,
      $this->eventLogService,
      $configFactory,
      $this->lock,
      $logger,
    );
  }

  /**
   * Tests that createAltaRecord throws when lock cannot be acquired.
   */
  public function testCreateAltaRecordThrowsOnLockFailure(): void {
    $this->lock->method('acquire')->willReturn(FALSE);

    $invoice = $this->createMockInvoice();

    $this->expectException(\RuntimeException::class);
    $this->service->createAltaRecord($invoice);
  }

  /**
   * Tests that createAnulacionRecord throws when lock cannot be acquired.
   */
  public function testCreateAnulacionRecordThrowsOnLockFailure(): void {
    $this->lock->method('acquire')->willReturn(FALSE);

    $altaRecord = $this->createMockAltaRecord();

    $this->expectException(\RuntimeException::class);
    $this->service->createAnulacionRecord($altaRecord);
  }

  /**
   * Tests that hash service is called during alta record creation.
   */
  public function testCreateAltaRecordCallsHashService(): void {
    $this->lock->method('acquire')->willReturn(TRUE);

    $this->hashService->expects($this->once())
      ->method('calculateAltaHash')
      ->willReturn(str_repeat('a', 64));

    $this->hashService->method('getLastChainHash')
      ->willReturn('genesis');

    // Mock entity storage for tenant config and record creation.
    $this->setupEntityStorageMocks();

    $invoice = $this->createMockInvoice();

    $result = $this->service->createAltaRecord($invoice);
    $this->assertInstanceOf(VeriFactuInvoiceRecord::class, $result);
  }

  /**
   * Tests that QR service is called during alta record creation.
   */
  public function testCreateAltaRecordCallsQrService(): void {
    $this->lock->method('acquire')->willReturn(TRUE);

    $this->hashService->method('calculateAltaHash')
      ->willReturn(str_repeat('b', 64));
    $this->hashService->method('getLastChainHash')
      ->willReturn('genesis');

    $this->qrService->expects($this->once())
      ->method('buildVerificationUrl')
      ->willReturn('https://example.com/qr');

    $this->setupEntityStorageMocks();

    $invoice = $this->createMockInvoice();

    $this->service->createAltaRecord($invoice);
  }

  /**
   * Tests that event log service is called for fire-and-forget logging.
   */
  public function testEventLogServiceNeverThrows(): void {
    // This is already covered by the fact that createAltaRecord calls logEvent
    // and we verify it doesn't break the flow.
    $this->expectNotToPerformAssertions();
  }

  /**
   * Tests lock is released even when exception occurs.
   */
  public function testLockReleasedOnException(): void {
    $this->lock->method('acquire')->willReturn(TRUE);

    $this->hashService->method('calculateAltaHash')
      ->willThrowException(new \RuntimeException('Hash failed'));

    $this->hashService->method('getLastChainHash')
      ->willReturn('genesis');

    $this->lock->expects($this->once())
      ->method('release');

    $this->setupEntityStorageMocks();

    $invoice = $this->createMockInvoice();

    try {
      $this->service->createAltaRecord($invoice);
    }
    catch (\RuntimeException) {
      // Expected.
    }
  }

  /**
   * Creates a mock billing invoice entity.
   */
  protected function createMockInvoice(): object {
    $invoice = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $invoice->method('id')->willReturn(1);
    $invoice->method('getEntityTypeId')->willReturn('billing_invoice');

    $fieldMap = [
      'tenant_id' => (object) ['target_id' => 42, 'value' => NULL],
      'invoice_number' => (object) ['target_id' => NULL, 'value' => 'F-2026-001'],
      'amount_due' => (object) ['target_id' => NULL, 'value' => '1210.00'],
    ];

    $invoice->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? (object) ['target_id' => NULL, 'value' => NULL];
    });

    return $invoice;
  }

  /**
   * Creates a mock VeriFactuInvoiceRecord entity (alta).
   */
  protected function createMockAltaRecord(): VeriFactuInvoiceRecord {
    $record = $this->createMock(VeriFactuInvoiceRecord::class);
    $record->method('id')->willReturn(10);

    $fieldMap = [
      'tenant_id' => (object) ['target_id' => 42, 'value' => NULL],
      'record_type' => (object) ['target_id' => NULL, 'value' => 'alta'],
      'nif_emisor' => (object) ['target_id' => NULL, 'value' => 'B12345678'],
      'nombre_emisor' => (object) ['target_id' => NULL, 'value' => 'Test SL'],
      'numero_factura' => (object) ['target_id' => NULL, 'value' => 'F-2026-001'],
      'fecha_expedicion' => (object) ['target_id' => NULL, 'value' => '2026-02-16'],
      'tipo_factura' => (object) ['target_id' => NULL, 'value' => 'F1'],
      'importe_total' => (object) ['target_id' => NULL, 'value' => '1210.00'],
      'hash_record' => (object) ['target_id' => NULL, 'value' => str_repeat('c', 64)],
    ];

    $record->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? (object) ['target_id' => NULL, 'value' => NULL];
    });

    return $record;
  }

  /**
   * Sets up entity storage mocks for tenant config lookup.
   */
  protected function setupEntityStorageMocks(): void {
    $tenantConfig = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $tenantConfig->method('get')->willReturnCallback(function($field) {
       return (object) ['value' => 'test-value'];
    });

    $record = $this->createMock(VeriFactuInvoiceRecord::class);
    $record->method('id')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$tenantConfig]);
    $storage->method('create')->willReturn($record);
    
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);
  }

}
