<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_verifactu\Service\VeriFactuHashService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuHashService.
 *
 * Verifica el algoritmo SHA-256 conforme Anexo II RD 1007/2023,
 * el encadenamiento de hashes, y la validacion de campos.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuHashService
 */
class VeriFactuHashServiceTest extends UnitTestCase {

  protected VeriFactuHashService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LockBackendInterface $lock;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuHashService(
      $this->entityTypeManager,
      $this->lock,
      $this->logger,
    );
  }

  /**
   * Tests hash calculation produces correct 64-char hex SHA-256.
   */
  public function testCalculateAltaHashProduces64CharHex(): void {
    $fields = $this->getValidFields();
    $hash = $this->service->calculateAltaHash($fields, NULL);

    $this->assertSame(64, strlen($hash));
    $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
  }

  /**
   * Tests that the same input always produces the same hash.
   */
  public function testHashIsDeterministic(): void {
    $fields = $this->getValidFields();

    $hash1 = $this->service->calculateAltaHash($fields, NULL);
    $hash2 = $this->service->calculateAltaHash($fields, NULL);

    $this->assertSame($hash1, $hash2);
  }

  /**
   * Tests hash chain: different previous hash produces different result.
   */
  public function testHashChainDifferentPreviousHash(): void {
    $fields = $this->getValidFields();

    $hashFirst = $this->service->calculateAltaHash($fields, NULL);
    $hashChained = $this->service->calculateAltaHash($fields, 'abc123');

    $this->assertNotSame($hashFirst, $hashChained);
  }

  /**
   * Tests that first record (no previous hash) uses empty string.
   */
  public function testFirstRecordUsesEmptyPreviousHash(): void {
    $fields = $this->getValidFields();

    // Hash with NULL (first record) should be deterministic.
    $hash = $this->service->calculateAltaHash($fields, NULL);
    $this->assertSame(64, strlen($hash));

    // Verify the expected SHA-256 of the concatenated fields.
    $expected = hash('sha256', implode(',', [
      $fields['nif_emisor'],
      $fields['numero_factura'],
      $fields['fecha_expedicion'],
      $fields['tipo_factura'],
      $fields['cuota_tributaria'],
      $fields['importe_total'],
      'alta',
      '',
    ]));
    $this->assertSame($expected, $hash);
  }

  /**
   * Tests anulacion hash differs from alta hash for same fields.
   */
  public function testAnulacionHashDiffersFromAlta(): void {
    $fields = $this->getValidFields();

    $altaHash = $this->service->calculateAltaHash($fields, NULL);
    $anulacionHash = $this->service->calculateAnulacionHash($fields, NULL);

    $this->assertNotSame($altaHash, $anulacionHash);
  }

  /**
   * Tests that missing required fields throws InvalidArgumentException.
   */
  public function testMissingFieldsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Missing required fields');

    $incompleteFields = [
      'nif_emisor' => 'B12345678',
      // Missing: numero_factura, fecha_expedicion, etc.
    ];

    $this->service->calculateAltaHash($incompleteFields, NULL);
  }

  /**
   * Tests that empty array throws InvalidArgumentException.
   */
  public function testEmptyFieldsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);

    $this->service->calculateAltaHash([], NULL);
  }

  /**
   * Tests different field values produce different hashes.
   */
  public function testDifferentFieldsProduceDifferentHashes(): void {
    $fields1 = $this->getValidFields();
    $fields2 = $this->getValidFields();
    $fields2['importe_total'] = '999.99';

    $hash1 = $this->service->calculateAltaHash($fields1, NULL);
    $hash2 = $this->service->calculateAltaHash($fields2, NULL);

    $this->assertNotSame($hash1, $hash2);
  }

  /**
   * Tests chain integrity verification when lock cannot be acquired.
   */
  public function testVerifyChainIntegrityLockFailed(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->willReturn(FALSE);

    $result = $this->service->verifyChainIntegrity(1);

    $this->assertFalse($result->isValid);
    $this->assertStringContainsString('lock', $result->errorMessage);
  }

  /**
   * Tests chain integrity verification with empty chain.
   */
  public function testVerifyChainIntegrityEmptyChain(): void {
    $this->lock->method('acquire')->willReturn(TRUE);

    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('verifactu_invoice_record')
      ->willReturn($storage);

    $result = $this->service->verifyChainIntegrity(1);

    $this->assertTrue($result->isValid);
    $this->assertSame(0, $result->totalRecords);
  }

  /**
   * Returns a valid set of fields for testing.
   */
  protected function getValidFields(): array {
    return [
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'cuota_tributaria' => '210.00',
      'importe_total' => '1210.00',
    ];
  }

}
