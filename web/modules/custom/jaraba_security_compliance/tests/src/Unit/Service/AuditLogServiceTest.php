<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_security_compliance\Service\AuditLogService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for AuditLogService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\AuditLogService
 */
class AuditLogServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected AuditLogService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked current user.
   */
  protected AccountProxyInterface&MockObject $currentUser;

  /**
   * Mocked request stack.
   */
  protected RequestStack&MockObject $requestStack;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity storage.
   */
  protected EntityStorageInterface&MockObject $storage;

  /**
   * Mocked request.
   */
  protected Request&MockObject $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Set up default mock behaviors.
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->with('security_audit_log')
      ->willReturn($this->storage);

    $this->currentUser
      ->method('id')
      ->willReturn(42);

    $this->request = $this->createMock(Request::class);
    $this->request
      ->method('getClientIp')
      ->willReturn('192.168.1.100');

    $this->requestStack
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $this->service = new AuditLogService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Tests that log() creates a security audit log entity with correct values.
   *
   * @covers ::log
   */
  public function testLogCreatesAuditLogEntity(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['event_type'] === 'user.login'
          && $values['actor_id'] === 42
          && $values['ip_address'] === '192.168.1.100'
          && $values['severity'] === 'info';
      }))
      ->willReturn($entity);

    $this->service->log('user.login', ['severity' => 'info']);
  }

  /**
   * Tests that log() passes tenant_id from context.
   *
   * @covers ::log
   */
  public function testLogWithTenantId(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['event_type'] === 'tenant.updated'
          && $values['tenant_id'] === 99;
      }))
      ->willReturn($entity);

    $this->service->log('tenant.updated', ['tenant_id' => 99]);
  }

  /**
   * Tests that log() sets target_type and target_id when provided.
   *
   * @covers ::log
   */
  public function testLogWithTargetEntity(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['target_type'] === 'user'
          && $values['target_id'] === 15
          && $values['event_type'] === 'permission.changed';
      }))
      ->willReturn($entity);

    $this->service->log('permission.changed', [
      'target_type' => 'user',
      'target_id' => 15,
      'severity' => 'warning',
    ]);
  }

  /**
   * Tests that severity defaults to 'info' when not provided in context.
   *
   * @covers ::log
   */
  public function testLogDefaultSeverityIsInfo(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['severity'] === 'info';
      }))
      ->willReturn($entity);

    // Call without severity in context.
    $this->service->log('some.event');
  }

  /**
   * Tests that log() catches exceptions and logs them without propagating.
   *
   * @covers ::log
   */
  public function testLogCatchesExceptions(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save')
      ->willThrowException(new \RuntimeException('Database unavailable'));

    $this->storage->expects($this->once())
      ->method('create')
      ->willReturn($entity);

    // The logger should receive the error message.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to write security audit log for event @event: @message',
        $this->callback(function (array $context): bool {
          return $context['@event'] === 'user.login'
            && $context['@message'] === 'Database unavailable';
        })
      );

    // Should not throw - the exception is caught internally.
    $this->service->log('user.login', ['severity' => 'info']);
  }

  /**
   * Tests that log() handles details array in context by JSON-encoding it.
   *
   * @covers ::log
   */
  public function testLogWithDetails(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $details = ['method' => 'password', 'ip_country' => 'ES'];

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($details): bool {
        return isset($values['details'])
          && $values['details'] === json_encode($details);
      }))
      ->willReturn($entity);

    $this->service->log('user.login', ['details' => $details]);
  }

  /**
   * Tests that log() handles null request gracefully.
   *
   * @covers ::log
   */
  public function testLogWithNullRequest(): void {
    // Override the request stack to return null.
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')
      ->willReturn(null);

    $service = new AuditLogService(
      $this->entityTypeManager,
      $this->currentUser,
      $requestStack,
      $this->logger,
    );

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['ip_address'] === '';
      }))
      ->willReturn($entity);

    $service->log('cron.run');
  }

  /**
   * Tests that log() accepts 'notice' severity.
   *
   * @covers ::log
   */
  public function testLogWithNoticeSeverity(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['severity'] === 'notice';
      }))
      ->willReturn($entity);

    $this->service->log('config.changed', ['severity' => 'notice']);
  }

}
