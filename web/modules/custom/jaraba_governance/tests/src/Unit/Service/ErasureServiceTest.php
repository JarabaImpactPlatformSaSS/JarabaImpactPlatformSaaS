<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_governance\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Entity\ErasureRequestInterface;
use Drupal\jaraba_governance\Service\ErasureService;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for ErasureService.
 *
 * @group jaraba_governance
 * @coversDefaultClass \Drupal\jaraba_governance\Service\ErasureService
 */
class ErasureServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected FileSystemInterface $fileSystem;
  protected ErasureService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);

    // Mock container for \Drupal static calls.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());

    $currentUser = $this->createMock(AccountProxyInterface::class);
    $currentUser->method('id')->willReturn(1);
    $container->set('current_user', $currentUser);

    \Drupal::setContainer($container);

    $this->service = new ErasureService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->database,
      $this->logger,
      $this->fileSystem,
    );
  }

  /**
   * Tests that createRequest sets status to pending.
   *
   * @covers ::createRequest
   */
  public function testCreateRequestSetsStatusPending(): void {
    $request = $this->createMock(ErasureRequestInterface::class);
    $request->method('id')->willReturn(1);
    $request->method('getStatus')->willReturn('pending');
    $request->method('getRequestType')->willReturn('erasure');
    $request->method('getSubjectUserId')->willReturn(42);
    $request->method('getCreatedTime')->willReturn(time());
    $request->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willReturnCallback(function (array $values) use ($request) {
        // Verify status is set to pending.
        $this->assertEquals('pending', $values['status']);
        $this->assertEquals(42, $values['subject_user_id']);
        $this->assertEquals('erasure', $values['request_type']);
        return $request;
      });

    $this->entityTypeManager->method('getStorage')
      ->with('erasure_request')
      ->willReturn($storage);

    $result = $this->service->createRequest(42, 'erasure', 'User requested data deletion');

    $this->assertEquals('pending', $result->getStatus());
    $this->assertEquals('erasure', $result->getRequestType());
  }

  /**
   * Tests that processRequest anonymizes user data.
   *
   * @covers ::processRequest
   */
  public function testProcessRequestAnonymizesUserData(): void {
    // Mock the erasure request entity.
    $request = $this->createMock(ErasureRequestInterface::class);
    $request->method('id')->willReturn(1);
    $request->method('getStatus')->willReturn('pending');
    $request->method('getSubjectUserId')->willReturn(42);
    $request->method('getRequestType')->willReturn('erasure');
    $request->method('setStatus')->willReturnSelf();
    $request->method('setEntitiesAffected')->willReturnSelf();
    $request->method('set')->willReturnSelf();
    $request->expects($this->atLeast(2))->method('save');

    // Erasure storage needs both load() for processRequest() and getQuery()
    // for getAffectedEntities() which iterates over 'erasure_request' type.
    $erasureQuery = $this->createMock(QueryInterface::class);
    $erasureQuery->method('accessCheck')->willReturnSelf();
    $erasureQuery->method('condition')->willReturnSelf();
    $erasureQuery->method('execute')->willReturn([]);

    $erasureStorage = $this->createMock(EntityStorageInterface::class);
    $erasureStorage->method('load')->with(1)->willReturn($request);
    $erasureStorage->method('getQuery')->willReturn($erasureQuery);

    // Mock entity query for affected entities — for node type.
    $nodeQuery = $this->createMock(QueryInterface::class);
    $nodeQuery->method('accessCheck')->willReturnSelf();
    $nodeQuery->method('condition')->willReturnSelf();
    $nodeQuery->method('execute')->willReturn([100, 101]);

    $nodeEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $nodeEntity->method('hasField')->willReturn(TRUE);
    $nodeEntity->method('set')->willReturnSelf();
    $nodeEntity->expects($this->atLeastOnce())->method('save');

    $nodeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeStorage->method('getQuery')->willReturn($nodeQuery);
    $nodeStorage->method('load')->willReturn($nodeEntity);

    // Other entity types return empty.
    $emptyQuery = $this->createMock(QueryInterface::class);
    $emptyQuery->method('accessCheck')->willReturnSelf();
    $emptyQuery->method('condition')->willReturnSelf();
    $emptyQuery->method('execute')->willReturn([]);

    $emptyStorage = $this->createMock(EntityStorageInterface::class);
    $emptyStorage->method('getQuery')->willReturn($emptyQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($erasureStorage, $nodeStorage, $emptyStorage) {
        return match ($type) {
          'erasure_request' => $erasureStorage,
          'node' => $nodeStorage,
          default => $emptyStorage,
        };
      });

    $result = $this->service->processRequest(1);

    $this->assertArrayHasKey('anonymized', $result);
    $this->assertEquals(2, $result['anonymized']);
    $this->assertEquals(42, $result['subject_user_id']);
  }

  /**
   * Tests that exportUserData collects all user entities.
   *
   * @covers ::exportUserData
   */
  public function testExportUserDataCollectsAllEntities(): void {
    // Mock user entity.
    $user = $this->createMock(UserInterface::class);
    $user->method('id')->willReturn(42);
    $user->method('getAccountName')->willReturn('testuser');
    $user->method('getEmail')->willReturn('test@example.com');
    $user->method('getCreatedTime')->willReturn(1700000000);
    $user->method('getLastAccessedTime')->willReturn(1700001000);
    $user->method('isActive')->willReturn(TRUE);
    $user->method('getRoles')->willReturn(['authenticated']);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')->with(42)->willReturn($user);

    // Mock node query for affected entities.
    $nodeQuery = $this->createMock(QueryInterface::class);
    $nodeQuery->method('accessCheck')->willReturnSelf();
    $nodeQuery->method('condition')->willReturnSelf();
    $nodeQuery->method('execute')->willReturn([100]);

    $nodeEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $nodeEntity->method('id')->willReturn(100);
    $nodeEntity->method('hasField')->willReturn(FALSE);
    $nodeEntity->method('label')->willReturn('Test Node');

    $nodeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeStorage->method('getQuery')->willReturn($nodeQuery);
    $nodeStorage->method('load')->willReturn($nodeEntity);
    $nodeStorage->method('loadMultiple')->willReturn([100 => $nodeEntity]);

    // Empty queries for other types.
    $emptyQuery = $this->createMock(QueryInterface::class);
    $emptyQuery->method('accessCheck')->willReturnSelf();
    $emptyQuery->method('condition')->willReturnSelf();
    $emptyQuery->method('execute')->willReturn([]);

    $emptyStorage = $this->createMock(EntityStorageInterface::class);
    $emptyStorage->method('getQuery')->willReturn($emptyQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($userStorage, $nodeStorage, $emptyStorage) {
        return match ($type) {
          'user' => $userStorage,
          'node' => $nodeStorage,
          default => $emptyStorage,
        };
      });

    $export = $this->service->exportUserData(42);

    $this->assertEquals(42, $export['subject_user_id']);
    $this->assertArrayHasKey('data', $export);
    $this->assertArrayHasKey('user', $export['data']);
    $this->assertEquals('testuser', $export['data']['user']['name']);
    $this->assertEquals('test@example.com', $export['data']['user']['mail']);
    $this->assertArrayHasKey('node', $export['data']);
    $this->assertCount(1, $export['data']['node']);
    $this->assertEquals(100, $export['data']['node'][0]['id']);
  }

}
