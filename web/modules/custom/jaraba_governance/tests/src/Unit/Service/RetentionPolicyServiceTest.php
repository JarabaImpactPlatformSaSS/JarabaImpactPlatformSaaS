<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_governance\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Service\RetentionPolicyService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for RetentionPolicyService.
 *
 * @group jaraba_governance
 * @coversDefaultClass \Drupal\jaraba_governance\Service\RetentionPolicyService
 */
class RetentionPolicyServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected RetentionPolicyService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock container for \Drupal static calls.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());

    // Mock config for retention policies.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function (string $key) {
        if ($key === 'retention_policies') {
          return [
            'session_logs' => [
              'entity_type' => 'session_log',
              'retention_days' => 90,
              'action' => 'delete',
            ],
            'inactive_users' => [
              'entity_type' => 'user',
              'retention_days' => 730,
              'action' => 'anonymize',
              'grace_days' => 30,
            ],
            'anonymized' => [
              'retention_days' => 0,
              'action' => 'keep',
            ],
          ];
        }
        return NULL;
      });

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_governance.settings')
      ->willReturn($config);
    $container->set('config.factory', $configFactory);

    // Mock time service.
    $time = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $time->method('getRequestTime')->willReturn(time());
    $container->set('datetime.time', $time);

    \Drupal::setContainer($container);

    $this->service = new RetentionPolicyService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests that previewRetention returns expired entity counts.
   *
   * @covers ::previewRetention
   */
  public function testPreviewRetentionReturnsExpiredCounts(): void {
    // Mock session_log storage and query.
    $sessionQuery = $this->createMock(QueryInterface::class);
    $sessionQuery->method('accessCheck')->willReturnSelf();
    $sessionQuery->method('condition')->willReturnSelf();
    $sessionQuery->method('execute')->willReturn([1, 2, 3]);

    $sessionStorage = $this->createMock(EntityStorageInterface::class);
    $sessionStorage->method('getQuery')->willReturn($sessionQuery);

    // Mock user storage and query.
    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('execute')->willReturn([10, 20]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($sessionStorage, $userStorage) {
        return match ($entityType) {
          'session_log' => $sessionStorage,
          'user' => $userStorage,
          default => throw new \Exception("Unknown entity type: $entityType"),
        };
      });

    $preview = $this->service->previewRetention();

    $this->assertArrayHasKey('session_logs', $preview);
    $this->assertArrayHasKey('inactive_users', $preview);
    $this->assertArrayHasKey('anonymized', $preview);

    $this->assertEquals('delete', $preview['session_logs']['action']);
    $this->assertEquals(3, $preview['session_logs']['would_affect']);

    $this->assertEquals('anonymize', $preview['inactive_users']['action']);
    $this->assertEquals(2, $preview['inactive_users']['would_affect']);

    $this->assertEquals('keep', $preview['anonymized']['action']);
    $this->assertEquals(0, $preview['anonymized']['would_affect']);
  }

  /**
   * Tests that anonymizeEntity replaces PII fields.
   *
   * @covers ::anonymizeEntity
   */
  public function testAnonymizeEntityReplacesPiiFields(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->willReturnCallback(function (string $field) {
      return in_array($field, ['mail', 'name', 'field_phone'], TRUE);
    });

    $entity->expects($this->exactly(3))
      ->method('set')
      ->willReturnCallback(function (string $field, $value) use ($entity) {
        // Verify that PII fields get anonymized values.
        $this->assertNotEmpty($value);
        return $entity;
      });

    $entity->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('user')
      ->willReturn($storage);

    $result = $this->service->anonymizeEntity('user', 42);
    $this->assertTrue($result);
  }

  /**
   * Tests that executeRetention processes all configured policies.
   *
   * @covers ::executeRetention
   */
  public function testExecuteRetentionProcessesAllPolicies(): void {
    // Mock session_log: 2 entities to delete.
    $sessionQuery = $this->createMock(QueryInterface::class);
    $sessionQuery->method('accessCheck')->willReturnSelf();
    $sessionQuery->method('condition')->willReturnSelf();
    $sessionQuery->method('execute')->willReturn([1, 2]);

    $sessionEntity = $this->createMock(ContentEntityInterface::class);
    $sessionEntity->expects($this->exactly(2))->method('delete');

    $sessionStorage = $this->createMock(EntityStorageInterface::class);
    $sessionStorage->method('getQuery')->willReturn($sessionQuery);
    $sessionStorage->method('load')->willReturn($sessionEntity);

    // Mock user: 1 entity to anonymize.
    $userQuery = $this->createMock(QueryInterface::class);
    $userQuery->method('accessCheck')->willReturnSelf();
    $userQuery->method('condition')->willReturnSelf();
    $userQuery->method('execute')->willReturn([10]);

    $userEntity = $this->createMock(ContentEntityInterface::class);
    $userEntity->method('hasField')->willReturn(TRUE);
    $userEntity->method('set')->willReturnSelf();
    $userEntity->expects($this->once())->method('save');

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($userQuery);
    $userStorage->method('load')->willReturn($userEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($sessionStorage, $userStorage) {
        return match ($entityType) {
          'session_log' => $sessionStorage,
          'user' => $userStorage,
          default => throw new \Exception("Unknown: $entityType"),
        };
      });

    $stats = $this->service->executeRetention();

    $this->assertArrayHasKey('session_logs', $stats);
    $this->assertEquals('delete', $stats['session_logs']['action']);
    $this->assertEquals(2, $stats['session_logs']['affected']);

    $this->assertArrayHasKey('inactive_users', $stats);
    $this->assertEquals('anonymize', $stats['inactive_users']['action']);
    $this->assertEquals(1, $stats['inactive_users']['affected']);

    $this->assertArrayHasKey('anonymized', $stats);
    $this->assertEquals('keep', $stats['anonymized']['action']);
    $this->assertEquals(0, $stats['anonymized']['affected']);
  }

}
