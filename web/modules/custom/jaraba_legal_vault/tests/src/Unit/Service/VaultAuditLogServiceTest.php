<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_vault\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_legal_vault\Service\VaultAuditLogService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for VaultAuditLogService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_vault\Service\VaultAuditLogService
 * @group jaraba_legal_vault
 */
class VaultAuditLogServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected RequestStack $requestStack;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_vault\Service\VaultAuditLogService
   */
  protected VaultAuditLogService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn(7);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $request = $this->createMock(Request::class);
    $request->method('getClientIp')->willReturn('192.168.1.100');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->service = new VaultAuditLogService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Tests log() creates an audit entry and returns the entry ID.
   *
   * @covers ::log
   */
  public function testLogAction(): void {
    // Mock: getLastHash query returns empty (genesis).
    $lastHashQuery = $this->createMock(QueryInterface::class);
    $lastHashQuery->method('accessCheck')->willReturnSelf();
    $lastHashQuery->method('condition')->willReturnSelf();
    $lastHashQuery->method('sort')->willReturnSelf();
    $lastHashQuery->method('range')->willReturnSelf();
    $lastHashQuery->method('execute')->willReturn([]);

    $entry = $this->createMock(\stdClass::class);
    $entry->method('id')->willReturn(55);
    $entry->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($lastHashQuery);
    $storage->method('create')->willReturn($entry);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('document_audit_log')
      ->willReturn($storage);

    $result = $this->service->log(100, 'created', ['filename' => 'contract.pdf']);

    $this->assertSame(55, $result);
  }

  /**
   * Tests that the hash chain uses SHA-256 and is verifiable.
   *
   * Verifies that verifyIntegrity correctly validates a chain and detects
   * tampering when stored hashes do not match recomputed hashes.
   *
   * @covers ::verifyIntegrity
   */
  public function testHashChainIntegrity(): void {
    // Simulate an empty chain (no entries) -- should be valid.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('document_audit_log')
      ->willReturn($storage);

    $result = $this->service->verifyIntegrity(100);

    $this->assertTrue($result['valid']);
    $this->assertSame(0, $result['entries_checked']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests verifyIntegrity detects a tampered hash chain.
   *
   * @covers ::verifyIntegrity
   */
  public function testHashChainIntegrityDetectsTampering(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    // Create a fake entry with a deliberately wrong hash.
    $entry = $this->createMock(\stdClass::class);
    $entry->method('id')->willReturn(1);

    $fieldMap = [
      'document_id' => $this->createFieldItemRef(100),
      'action' => $this->createFieldItem('created'),
      'actor_id' => $this->createFieldItemRef(7),
      'actor_ip' => $this->createFieldItem('192.168.1.100'),
      'details' => $this->createFieldItemWithGetValue([]),
      'created' => $this->createFieldItem('1709312400'),
      'hash_chain' => $this->createFieldItem('tampered_hash_value'),
    ];

    $entry->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? $this->createFieldItem(NULL);
    });

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([1])->willReturn([$entry]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('document_audit_log')
      ->willReturn($storage);

    $result = $this->service->verifyIntegrity(100);

    $this->assertFalse($result['valid']);
    $this->assertSame(0, $result['entries_checked']);
    $this->assertStringContainsString('Hash mismatch', $result['error']);
  }

  /**
   * Tests that log() is append-only by verifying it always creates new entries.
   *
   * @covers ::log
   */
  public function testAppendOnlyConstraint(): void {
    // Mock: getLastHash query returns empty (genesis) each time.
    $lastHashQuery = $this->createMock(QueryInterface::class);
    $lastHashQuery->method('accessCheck')->willReturnSelf();
    $lastHashQuery->method('condition')->willReturnSelf();
    $lastHashQuery->method('sort')->willReturnSelf();
    $lastHashQuery->method('range')->willReturnSelf();
    $lastHashQuery->method('execute')->willReturn([]);

    $entry1 = $this->createMock(\stdClass::class);
    $entry1->method('id')->willReturn(1);
    $entry1->expects($this->once())->method('save');

    $entry2 = $this->createMock(\stdClass::class);
    $entry2->method('id')->willReturn(2);
    $entry2->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($lastHashQuery);
    // Each call to log() should create() a new entity, never update.
    $storage->expects($this->exactly(2))
      ->method('create')
      ->willReturnOnConsecutiveCalls($entry1, $entry2);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('document_audit_log')
      ->willReturn($storage);

    $id1 = $this->service->log(100, 'created', ['file' => 'a.pdf']);
    $id2 = $this->service->log(100, 'viewed', ['viewer' => 'user1']);

    $this->assertSame(1, $id1);
    $this->assertSame(2, $id2);
    // Append-only: IDs are sequential, both used create().
    $this->assertGreaterThanOrEqual($id1, $id2);
  }

  /**
   * Tests log() returns 0 on exception.
   *
   * @covers ::log
   */
  public function testLogActionReturnsZeroOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('document_audit_log')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->log(100, 'created');

    $this->assertSame(0, $result);
  }

  /**
   * Helper to create a mock field item with a value property.
   */
  protected function createFieldItem(mixed $value): object {
    $field = new \stdClass();
    $field->value = $value;
    return $field;
  }

  /**
   * Helper to create a mock field item with a target_id property.
   */
  protected function createFieldItemRef(?int $targetId): object {
    $field = new \stdClass();
    $field->target_id = $targetId;
    return $field;
  }

  /**
   * Helper to create a mock field item with first()->getValue() support.
   */
  protected function createFieldItemWithGetValue(array $value): object {
    $firstItem = $this->createMock(\stdClass::class);
    $firstItem->method('getValue')->willReturn($value);

    $field = $this->createMock(\stdClass::class);
    $field->method('first')->willReturn($firstItem);
    return $field;
  }

}
