<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_lexnet\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_legal_lexnet\Service\LexnetApiClient;
use Drupal\jaraba_legal_lexnet\Service\LexnetSyncService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LexnetSyncService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_lexnet\Service\LexnetSyncService
 * @group jaraba_legal_lexnet
 */
class LexnetSyncServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked LexNET API client.
   *
   * @var \Drupal\jaraba_legal_lexnet\Service\LexnetApiClient|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LexnetApiClient $apiClient;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_lexnet\Service\LexnetSyncService
   */
  protected LexnetSyncService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->apiClient = $this->createMock(LexnetApiClient::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn(5);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new LexnetSyncService(
      $this->entityTypeManager,
      $this->apiClient,
      $this->currentUser,
      $this->logger,
    );
  }

  /**
   * Tests fetchNotifications creates entities from API response.
   *
   * @covers ::fetchNotifications
   */
  public function testSyncNotifications(): void {
    $this->apiClient->method('request')
      ->with('GET', 'notifications')
      ->willReturn([
        'notifications' => [
          [
            'id' => 'EXT-001',
            'type' => 'notificacion_electronica',
            'court' => 'Juzgado Civil 1 Madrid',
            'procedure_number' => '123/2025',
            'subject' => 'Emplazamiento',
            'received_at' => '2025-03-01T10:00:00',
            'deadline_days' => 20,
            'attachments' => [],
          ],
          [
            'id' => 'EXT-002',
            'type' => 'notificacion_electronica',
            'court' => 'Juzgado Social 3 Barcelona',
            'procedure_number' => '456/2025',
            'subject' => 'Sentencia',
            'received_at' => '2025-03-02T14:00:00',
            'deadline_days' => 0,
            'attachments' => ['sentencia.pdf'],
          ],
        ],
      ]);

    // Neither exists yet.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entity = $this->createMock(\stdClass::class);
    $entity->expects($this->exactly(2))->method('save');
    $storage->method('create')->willReturn($entity);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('lexnet_notification')
      ->willReturn($storage);

    $result = $this->service->fetchNotifications();

    $this->assertSame(2, $result['count']);
    $this->assertSame(2, $result['total_fetched']);
  }

  /**
   * Tests fetchNotifications skips already existing notifications.
   *
   * @covers ::fetchNotifications
   */
  public function testSyncNotificationsSkipsDuplicates(): void {
    $this->apiClient->method('request')
      ->with('GET', 'notifications')
      ->willReturn([
        'notifications' => [
          [
            'id' => 'EXT-001',
            'type' => 'notificacion_electronica',
            'court' => 'Juzgado Civil 1',
            'subject' => 'Test',
          ],
        ],
      ]);

    // This notification already exists.
    $existingEntity = new \stdClass();
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['external_id' => 'EXT-001'])
      ->willReturn([$existingEntity]);

    // create() should never be called for a duplicate.
    $storage->expects($this->never())->method('create');

    $this->entityTypeManager
      ->method('getStorage')
      ->with('lexnet_notification')
      ->willReturn($storage);

    $result = $this->service->fetchNotifications();

    $this->assertSame(0, $result['count']);
    $this->assertSame(1, $result['total_fetched']);
  }

  /**
   * Tests listNotifications filters by status (jurisdiction simulation).
   *
   * @covers ::listNotifications
   * @covers ::serializeNotification
   */
  public function testFilterByJurisdiction(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturnOnConsecutiveCalls(1, [10]);

    $notification = $this->createNotificationMock(
      10, 'uuid-10', 'EXT-001', 'notificacion_electronica',
      'Juzgado Civil 1 Madrid', '123/2025', 'Emplazamiento',
      '2025-03-01T10:00:00', NULL, 20, NULL, 'pending', NULL, '1709312400',
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([10])->willReturn([$notification]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('lexnet_notification')
      ->willReturn($storage);

    $result = $this->service->listNotifications(['status' => 'pending'], 25, 0);

    $this->assertSame(1, $result['total']);
    $this->assertCount(1, $result['items']);
    $this->assertSame('Juzgado Civil 1 Madrid', $result['items'][0]['court']);
    $this->assertSame('pending', $result['items'][0]['status']);
  }

  /**
   * Tests acknowledgeNotification marks a notification as processed/read.
   *
   * @covers ::acknowledgeNotification
   */
  public function testMarkAsProcessed(): void {
    $notification = $this->createMock(\stdClass::class);

    $externalIdField = new \stdClass();
    $externalIdField->value = 'EXT-001';

    $statusField = new \stdClass();
    $statusField->value = 'pending';

    $acknowledgedAtField = new \stdClass();
    $acknowledgedAtField->value = NULL;

    $notification->method('get')->willReturnCallback(function (string $field) use ($externalIdField, $statusField, $acknowledgedAtField) {
      return match ($field) {
        'external_id' => $externalIdField,
        'status' => $statusField,
        'acknowledged_at' => $acknowledgedAtField,
        default => (object) ['value' => NULL],
      };
    });

    // Expect set() calls for acknowledged_at and status.
    $notification->expects($this->atLeast(1))->method('set');
    $notification->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(10)->willReturn($notification);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('lexnet_notification')
      ->willReturn($storage);

    // API acknowledge succeeds.
    $this->apiClient->method('request')
      ->with('POST', 'notifications/EXT-001/acknowledge')
      ->willReturn(['status' => 'acknowledged']);

    $result = $this->service->acknowledgeNotification(10);

    $this->assertSame(10, $result['id']);
    $this->assertSame('EXT-001', $result['external_id']);
    $this->assertArrayHasKey('acknowledged_at', $result);
  }

  /**
   * Tests acknowledgeNotification returns error when notification not found.
   *
   * @covers ::acknowledgeNotification
   */
  public function testMarkAsProcessedNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('lexnet_notification')
      ->willReturn($storage);

    $result = $this->service->acknowledgeNotification(999);

    $this->assertArrayHasKey('error', $result);
    $this->assertSame('Notification not found.', $result['error']);
  }

  /**
   * Tests fetchNotifications handles API error responses.
   *
   * @covers ::fetchNotifications
   */
  public function testSyncNotificationsHandlesApiError(): void {
    $this->apiClient->method('request')
      ->with('GET', 'notifications')
      ->willReturn(['error' => 'Certificate expired']);

    $result = $this->service->fetchNotifications();

    $this->assertSame(0, $result['count']);
    $this->assertSame('Certificate expired', $result['error']);
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
   * Creates a mock notification entity.
   */
  protected function createNotificationMock(
    int $id,
    string $uuid,
    string $externalId,
    string $notificationType,
    string $court,
    string $procedureNumber,
    string $subject,
    string $receivedAt,
    ?string $acknowledgedAt,
    int $deadlineDays,
    ?string $computedDeadline,
    string $status,
    ?int $caseId,
    string $created,
  ): object {
    $notification = $this->createMock(\stdClass::class);
    $notification->method('id')->willReturn($id);
    $notification->method('uuid')->willReturn($uuid);

    $fieldMap = [
      'external_id' => $this->createFieldItem($externalId),
      'notification_type' => $this->createFieldItem($notificationType),
      'court' => $this->createFieldItem($court),
      'procedure_number' => $this->createFieldItem($procedureNumber),
      'subject' => $this->createFieldItem($subject),
      'received_at' => $this->createFieldItem($receivedAt),
      'acknowledged_at' => $this->createFieldItem($acknowledgedAt),
      'deadline_days' => $this->createFieldItem($deadlineDays),
      'computed_deadline' => $this->createFieldItem($computedDeadline),
      'status' => $this->createFieldItem($status),
      'case_id' => $this->createFieldItemRef($caseId),
      'created' => $this->createFieldItem($created),
    ];

    $notification->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? $this->createFieldItem(NULL);
    });

    return $notification;
  }

}
