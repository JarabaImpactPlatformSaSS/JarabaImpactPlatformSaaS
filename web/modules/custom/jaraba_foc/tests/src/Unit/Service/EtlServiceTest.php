<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\EtlService;
use Drupal\jaraba_foc\Service\MetricsCalculatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EtlService.
 *
 * COBERTURA:
 * Verifica la logica ETL (Extract-Transform-Load) para datos financieros.
 * Se centra en la deteccion de duplicados, importacion CSV y creacion
 * de snapshots de metricas.
 *
 * FLUJO VERIFICADO:
 * 1. Extract: Lee datos de CSV
 * 2. Transform: Mapea a estructura FinancialTransaction
 * 3. Load: Crea entidades evitando duplicados (via external_id)
 *
 * @group jaraba_foc
 * @coversDefaultClass \Drupal\jaraba_foc\Service\EtlService
 */
class EtlServiceTest extends UnitTestCase {

  /**
   * El servicio bajo prueba.
   */
  protected EtlService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del servicio de metricas.
   */
  protected MetricsCalculatorService $metricsCalculator;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock del storage de financial_transaction.
   */
  protected EntityStorageInterface $transactionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->metricsCalculator = $this->createMock(MetricsCalculatorService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->transactionStorage = $this->createMock(EntityStorageInterface::class);

    $this->service = new EtlService(
      $this->entityTypeManager,
      $this->metricsCalculator,
      $this->logger,
    );
  }

  // =========================================================================
  // TESTS: transactionExists() via Reflection
  // =========================================================================

  /**
   * Verifica que transactionExists() devuelve TRUE cuando encuentra duplicado.
   *
   * @covers ::transactionExists
   */
  public function testTransactionExistsReturnsTrueForExistingExternalId(): void {
    $existingEntity = $this->createMock(ContentEntityInterface::class);

    $this->transactionStorage->method('loadByProperties')
      ->with(['external_id' => 'pi_existing_123'])
      ->willReturn([$existingEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('financial_transaction')
      ->willReturn($this->transactionStorage);

    $reflection = new \ReflectionMethod($this->service, 'transactionExists');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, 'pi_existing_123');
    $this->assertTrue($result);
  }

  /**
   * Verifica que transactionExists() devuelve FALSE cuando no hay duplicado.
   *
   * @covers ::transactionExists
   */
  public function testTransactionExistsReturnsFalseForNewExternalId(): void {
    $this->transactionStorage->method('loadByProperties')
      ->with(['external_id' => 'pi_new_456'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('financial_transaction')
      ->willReturn($this->transactionStorage);

    $reflection = new \ReflectionMethod($this->service, 'transactionExists');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, 'pi_new_456');
    $this->assertFalse($result);
  }

  // =========================================================================
  // TESTS: importFromCsv()
  // =========================================================================

  /**
   * Verifica que importFromCsv() reporta error para archivo inexistente.
   *
   * @covers ::importFromCsv
   */
  public function testImportFromCsvReturnsErrorForMissingFile(): void {
    $result = $this->service->importFromCsv('/nonexistent/path/data.csv');

    $this->assertSame(0, $result['imported']);
    $this->assertSame(0, $result['skipped']);
    $this->assertNotEmpty($result['errors']);
    $this->assertStringContainsString('no encontrado', $result['errors'][0]);
  }

  /**
   * Verifica que importFromCsv() importa filas validas de un CSV temporal.
   *
   * @covers ::importFromCsv
   */
  public function testImportFromCsvImportsValidRows(): void {
    // Create a temporary CSV file.
    $tmpFile = tempnam(sys_get_temp_dir(), 'etl_test_');
    $handle = fopen($tmpFile, 'w');
    fputcsv($handle, ['amount', 'currency', 'type', 'date', 'tenant_id', 'external_id', 'description']);
    fputcsv($handle, ['100.00', 'EUR', 'recurring_revenue', '2026-01-15', '1', 'INV-001', 'Monthly fee']);
    fputcsv($handle, ['200.00', 'EUR', 'one_time_sale', '2026-01-16', '2', 'INV-002', 'Setup fee']);
    fclose($handle);

    // Mock: no existing transactions (no duplicates).
    $this->transactionStorage->method('loadByProperties')
      ->willReturn([]);

    // Mock: create() returns a saveable entity.
    $mockEntity = $this->createMock(ContentEntityInterface::class);
    $mockEntity->method('save')->willReturn(1);
    $mockEntity->method('id')->willReturn('1');

    $this->transactionStorage->method('create')
      ->willReturn($mockEntity);

    $this->entityTypeManager->method('getStorage')
      ->with('financial_transaction')
      ->willReturn($this->transactionStorage);

    $result = $this->service->importFromCsv($tmpFile);

    $this->assertSame(2, $result['imported']);
    $this->assertSame(0, $result['skipped']);
    $this->assertEmpty($result['errors']);

    // Clean up.
    unlink($tmpFile);
  }

  /**
   * Verifica que importFromCsv() salta filas duplicadas (skip_duplicates=TRUE).
   *
   * @covers ::importFromCsv
   */
  public function testImportFromCsvSkipsDuplicates(): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'etl_dup_');
    $handle = fopen($tmpFile, 'w');
    fputcsv($handle, ['amount', 'currency', 'type', 'date', 'tenant_id', 'external_id', 'description']);
    fputcsv($handle, ['100.00', 'EUR', 'recurring_revenue', '2026-01-15', '1', 'INV-DUP', 'Duplicate']);
    fclose($handle);

    // Mock: transaction with INV-DUP already exists.
    $existingEntity = $this->createMock(ContentEntityInterface::class);
    $this->transactionStorage->method('loadByProperties')
      ->with(['external_id' => 'INV-DUP'])
      ->willReturn([$existingEntity]);

    $this->entityTypeManager->method('getStorage')
      ->with('financial_transaction')
      ->willReturn($this->transactionStorage);

    $result = $this->service->importFromCsv($tmpFile, ['skip_duplicates' => TRUE]);

    $this->assertSame(0, $result['imported']);
    $this->assertSame(1, $result['skipped']);
    $this->assertEmpty($result['errors']);

    unlink($tmpFile);
  }

  /**
   * Verifica que importFromCsv() identifica transacciones recurrentes por tipo.
   *
   * @covers ::importFromCsv
   */
  public function testImportFromCsvSetsIsRecurringForSubscriptionType(): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'etl_rec_');
    $handle = fopen($tmpFile, 'w');
    fputcsv($handle, ['amount', 'currency', 'type', 'date', 'tenant_id', 'external_id', 'description']);
    fputcsv($handle, ['50.00', 'EUR', 'subscription', '2026-02-01', '1', 'SUB-001', 'Sub fee']);
    fclose($handle);

    $this->transactionStorage->method('loadByProperties')
      ->willReturn([]);

    $createdValues = NULL;
    $mockEntity = $this->createMock(ContentEntityInterface::class);
    $mockEntity->method('save')->willReturn(1);
    $mockEntity->method('id')->willReturn('1');

    $this->transactionStorage->method('create')
      ->willReturnCallback(function (array $values) use ($mockEntity, &$createdValues) {
        $createdValues = $values;
        return $mockEntity;
      });

    $this->entityTypeManager->method('getStorage')
      ->with('financial_transaction')
      ->willReturn($this->transactionStorage);

    $this->service->importFromCsv($tmpFile);

    // Verify that is_recurring was set to TRUE for 'subscription' type.
    $this->assertNotNull($createdValues);
    $this->assertTrue($createdValues['is_recurring']);

    unlink($tmpFile);
  }

  /**
   * Verifica que importFromCsv() reporta error para CSV vacio.
   *
   * @covers ::importFromCsv
   */
  public function testImportFromCsvHandlesEmptyFile(): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'etl_empty_');
    // Write nothing to the file - just create it empty.
    file_put_contents($tmpFile, '');

    $result = $this->service->importFromCsv($tmpFile);

    $this->assertSame(0, $result['imported']);
    $this->assertNotEmpty($result['errors']);
    $this->assertStringContainsString('vac', $result['errors'][0]);

    unlink($tmpFile);
  }

  // =========================================================================
  // TESTS: createMetricSnapshot()
  // =========================================================================

  /**
   * Verifica que createMetricSnapshot() crea una entidad de snapshot.
   *
   * @covers ::createMetricSnapshot
   */
  public function testCreateMetricSnapshotCreatesEntity(): void {
    // Mock metrics calculator responses.
    $this->metricsCalculator->method('calculateMRR')->willReturn('5000.00');
    $this->metricsCalculator->method('calculateARR')->willReturn('60000.00');
    $this->metricsCalculator->method('calculateGrossMargin')->willReturn('75.00');
    $this->metricsCalculator->method('calculateLTV')->willReturn('15000.00');
    $this->metricsCalculator->method('calculateLTVCACRatio')->willReturn('5.00');
    $this->metricsCalculator->method('calculateCACPayback')->willReturn('8.0');

    // Mock snapshot entity.
    $snapshotEntity = $this->createMock(ContentEntityInterface::class);
    $snapshotEntity->method('save')->willReturn(1);
    $snapshotEntity->method('id')->willReturn('100');

    $snapshotStorage = $this->createMock(EntityStorageInterface::class);
    $snapshotStorage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['scope_type'] === 'platform'
          && $values['mrr'] === '5000.00'
          && $values['arr'] === '60000.00';
      }))
      ->willReturn($snapshotEntity);

    // Mock transaction storage for Quick Ratio and Revenue per Employee.
    $txnStorage = $this->createMock(EntityStorageInterface::class);
    $txnQuery = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $txnQuery->method('accessCheck')->willReturnSelf();
    $txnQuery->method('condition')->willReturnSelf();
    $txnQuery->method('execute')->willReturn([]);
    $txnStorage->method('getQuery')->willReturn($txnQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($snapshotStorage, $txnStorage) {
        return match ($type) {
          'foc_metric_snapshot' => $snapshotStorage,
          'financial_transaction' => $txnStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->createMetricSnapshot('platform');
    $this->assertSame(100, $result);
  }

  /**
   * Verifica que createMetricSnapshot() soporta scope 'tenant'.
   *
   * @covers ::createMetricSnapshot
   */
  public function testCreateMetricSnapshotForTenantScope(): void {
    $this->metricsCalculator->method('calculateMRR')->willReturn('1000.00');
    $this->metricsCalculator->method('calculateARR')->willReturn('12000.00');
    $this->metricsCalculator->method('calculateGrossMargin')->willReturn('80.00');
    $this->metricsCalculator->method('calculateLTV')->willReturn('20000.00');
    $this->metricsCalculator->method('calculateLTVCACRatio')->willReturn('4.00');
    $this->metricsCalculator->method('calculateCACPayback')->willReturn('6.0');

    $snapshotEntity = $this->createMock(ContentEntityInterface::class);
    $snapshotEntity->method('save')->willReturn(1);
    $snapshotEntity->method('id')->willReturn('200');

    $snapshotStorage = $this->createMock(EntityStorageInterface::class);
    $snapshotStorage->method('create')
      ->with($this->callback(function (array $values) {
        return $values['scope_type'] === 'tenant'
          && $values['scope_id'] === 42;
      }))
      ->willReturn($snapshotEntity);

    $txnStorage = $this->createMock(EntityStorageInterface::class);
    $txnQuery = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $txnQuery->method('accessCheck')->willReturnSelf();
    $txnQuery->method('condition')->willReturnSelf();
    $txnQuery->method('execute')->willReturn([]);
    $txnStorage->method('getQuery')->willReturn($txnQuery);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($snapshotStorage, $txnStorage, $groupStorage) {
        return match ($type) {
          'foc_metric_snapshot' => $snapshotStorage,
          'financial_transaction' => $txnStorage,
          'group' => $groupStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->createMetricSnapshot('tenant', 42);
    $this->assertSame(200, $result);
  }

}
