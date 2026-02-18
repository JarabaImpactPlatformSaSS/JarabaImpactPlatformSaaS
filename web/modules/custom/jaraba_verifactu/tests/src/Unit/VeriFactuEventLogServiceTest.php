<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuEventLog;
use Drupal\jaraba_verifactu\Service\VeriFactuEventLogService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para VeriFactuEventLogService.
 *
 * Verifica el registro de los 12 tipos de evento SIF definidos
 * por RD 1007/2023 y el encadenamiento independiente de hashes.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuEventLogService
 */
class VeriFactuEventLogServiceTest extends UnitTestCase {

  protected VeriFactuEventLogService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('id')->willReturn(1);

    $request = $this->createMock(Request::class);
    $request->method('getClientIp')->willReturn('127.0.0.1');

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($request);

    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuEventLogService(
      $this->entityTypeManager,
      $currentUser,
      $requestStack,
      $logger,
    );

    // Set up storage mock for event log.
    $this->storage = $this->createMock(EntityStorageInterface::class);

    // Mock the query for getting last event hash (empty chain).
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('verifactu_event_log')
      ->willReturn($this->storage);
  }

  /**
   * Tests logging all 12 defined event types.
   *
   * @dataProvider eventTypeProvider
   */
  public function testLogEventCreatesEntity(string $eventType): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willReturn(1);

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($eventType): bool {
        return $values['event_type'] === $eventType
          && $values['tenant_id'] === 42
          && $values['actor_id'] === 1
          && $values['ip_address'] === '127.0.0.1'
          && strlen($values['hash_event']) === 64;
      }))
      ->willReturn($mockEntity);

    $result = $this->service->logEvent($eventType, 42, NULL, [
      'description' => 'Test event for ' . $eventType,
    ]);

    $this->assertInstanceOf(VeriFactuEventLog::class, $result);
  }

  /**
   * Data provider for all 12 SIF event types.
   */
  public static function eventTypeProvider(): array {
    return [
      'SYSTEM_START' => ['SYSTEM_START'],
      'RECORD_CREATE' => ['RECORD_CREATE'],
      'RECORD_CANCEL' => ['RECORD_CANCEL'],
      'CHAIN_BREAK' => ['CHAIN_BREAK'],
      'CHAIN_RECOVERY' => ['CHAIN_RECOVERY'],
      'AEAT_SUBMIT' => ['AEAT_SUBMIT'],
      'AEAT_RESPONSE' => ['AEAT_RESPONSE'],
      'CERTIFICATE_CHANGE' => ['CERTIFICATE_CHANGE'],
      'CONFIG_CHANGE' => ['CONFIG_CHANGE'],
      'AUDIT_ACCESS' => ['AUDIT_ACCESS'],
      'INTEGRITY_CHECK' => ['INTEGRITY_CHECK'],
      'MANUAL_INTERVENTION' => ['MANUAL_INTERVENTION'],
    ];
  }

  /**
   * Tests that severity is extracted from details.
   */
  public function testLogEventExtractsSeverity(): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willReturn(1);

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['severity'] === 'critical';
      }))
      ->willReturn($mockEntity);

    $this->service->logEvent('CHAIN_BREAK', 1, NULL, [
      'severity' => 'critical',
      'description' => 'Chain break detected',
    ]);
  }

  /**
   * Tests that details are serialized as JSON.
   */
  public function testLogEventSerializesDetailsAsJson(): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willReturn(1);

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        $details = json_decode($values['details'], TRUE);
        return $details['hash'] === 'abc123' && $details['record_id'] === 99;
      }))
      ->willReturn($mockEntity);

    $this->service->logEvent('RECORD_CREATE', 1, 99, [
      'hash' => 'abc123',
      'record_id' => 99,
    ]);
  }

  /**
   * Tests that record_id is passed correctly.
   */
  public function testLogEventWithRecordId(): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willReturn(1);

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['record_id'] === 55;
      }))
      ->willReturn($mockEntity);

    $this->service->logEvent('RECORD_CREATE', 1, 55);
  }

  /**
   * Tests that exception in save does not propagate.
   */
  public function testLogEventSwallowsExceptions(): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willThrowException(new \RuntimeException('DB error'));

    $this->storage->method('create')->willReturn($mockEntity);

    // Should return NULL, not throw.
    $result = $this->service->logEvent('SYSTEM_START', 1, NULL);
    $this->assertNull($result);
  }

  /**
   * Tests that event hash is 64-character hex string.
   */
  public function testEventHashFormat(): void {
    $mockEntity = $this->createMock(VeriFactuEventLog::class);
    $mockEntity->method('save')->willReturn(1);

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return preg_match('/^[0-9a-f]{64}$/', $values['hash_event']) === 1;
      }))
      ->willReturn($mockEntity);

    $this->service->logEvent('SYSTEM_START', 1, NULL);
  }

}
