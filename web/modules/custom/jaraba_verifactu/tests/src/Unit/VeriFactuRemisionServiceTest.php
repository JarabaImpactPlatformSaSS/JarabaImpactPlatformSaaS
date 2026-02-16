<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\jaraba_verifactu\Service\VeriFactuEventLogService;
use Drupal\jaraba_verifactu\Service\VeriFactuRemisionService;
use Drupal\jaraba_verifactu\Service\VeriFactuXmlService;
use Drupal\jaraba_verifactu\ValueObject\AeatResponse;
use Drupal\jaraba_verifactu\ValueObject\RemisionResult;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuRemisionService.
 *
 * Verifica el envio de batches, control de flujo, reintentos y circuit breaker.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuRemisionService
 */
class VeriFactuRemisionServiceTest extends UnitTestCase {

  protected VeriFactuRemisionService $service;
  protected StateInterface $state;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $xmlService = $this->createMock(VeriFactuXmlService::class);
    $eventLogService = $this->createMock(VeriFactuEventLogService::class);
    $certificateManager = $this->createMock(CertificateManagerService::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(function (string $key) {
      return match ($key) {
        'flow_control_seconds' => 60,
        'max_retries' => 5,
        'retry_backoff_base_seconds' => 1,
        'max_records_per_batch' => 1000,
        default => '',
      };
    });

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $this->state = $this->createMock(StateInterface::class);
    $lock = $this->createMock(LockBackendInterface::class);

    $queue = $this->createMock(QueueInterface::class);
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuRemisionService(
      $this->entityTypeManager,
      $xmlService,
      $eventLogService,
      $certificateManager,
      $configFactory,
      $this->state,
      $lock,
      $queueFactory,
      $logger,
    );
  }

  /**
   * Tests processQueue returns 0 when no pending records.
   */
  public function testProcessQueueNoPendingRecords(): void {
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $result = $this->service->processQueue();
    $this->assertSame(0, $result);
  }

  /**
   * Tests circuit breaker blocks submission after threshold.
   */
  public function testCircuitBreakerBlocks(): void {
    // Simulate circuit breaker open (pause until future).
    $this->state->method('get')->willReturnCallback(function (string $key, $default = NULL) {
      if ($key === VeriFactuRemisionService::STATE_CIRCUIT_BREAKER_UNTIL) {
        return time() + 300;
      }
      return $default;
    });

    $batch = $this->createMockBatch();
    $result = $this->service->submitBatch($batch);

    $this->assertFalse($result->isSuccess);
    $this->assertStringContainsString('circuit breaker', strtolower($result->errorMessage));
  }

  /**
   * Tests flow control blocks when too soon.
   */
  public function testFlowControlBlocks(): void {
    // Circuit breaker NOT open, but flow control triggered.
    $this->state->method('get')->willReturnCallback(function (string $key, $default = NULL) {
      if ($key === VeriFactuRemisionService::STATE_CIRCUIT_BREAKER_UNTIL) {
        return 0;
      }
      if ($key === VeriFactuRemisionService::STATE_LAST_SUBMIT) {
        return time(); // Just submitted.
      }
      return $default;
    });

    $batch = $this->createMockBatch();
    $result = $this->service->submitBatch($batch);

    $this->assertFalse($result->isSuccess);
    $this->assertStringContainsString('flow control', strtolower($result->errorMessage));
  }

  /**
   * Tests RemisionResult success factory.
   */
  public function testRemisionResultSuccess(): void {
    $aeatResponse = AeatResponse::success('CSV123', [], 5, '<xml/>');
    $result = RemisionResult::success(1, $aeatResponse, 150.5);

    $this->assertTrue($result->isSuccess);
    $this->assertSame(1, $result->batchId);
    $this->assertSame(150.5, $result->durationMs);
    $this->assertNotNull($result->aeatResponse);
  }

  /**
   * Tests RemisionResult failure factory.
   */
  public function testRemisionResultFailure(): void {
    $result = RemisionResult::failure(1, 'Connection timeout', 3);

    $this->assertFalse($result->isSuccess);
    $this->assertSame('Connection timeout', $result->errorMessage);
    $this->assertSame(3, $result->retryCount);
  }

  /**
   * Tests RemisionResult toArray.
   */
  public function testRemisionResultToArray(): void {
    $result = RemisionResult::failure(5, 'Error', 2);
    $array = $result->toArray();

    $this->assertArrayHasKey('is_success', $array);
    $this->assertArrayHasKey('batch_id', $array);
    $this->assertArrayHasKey('retry_count', $array);
    $this->assertArrayHasKey('error_message', $array);
    $this->assertSame(5, $array['batch_id']);
  }

  /**
   * Creates a mock VeriFactuRemisionBatch for testing.
   */
  protected function createMockBatch(): object {
    $batch = $this->createMock(\Drupal\jaraba_verifactu\Entity\VeriFactuRemisionBatch::class);
    $batch->method('id')->willReturn(1);

    $batch->method('get')->willReturnCallback(function (string $field) {
      $item = new \stdClass();
      $item->value = match ($field) {
        'status' => 'queued',
        'aeat_environment' => 'testing',
        default => NULL,
      };
      $item->target_id = $field === 'tenant_id' ? 42 : NULL;
      return $item;
    });

    $batch->method('set')->willReturnSelf();
    $batch->method('save')->willReturn(SAVED_UPDATED);

    return $batch;
  }

}
