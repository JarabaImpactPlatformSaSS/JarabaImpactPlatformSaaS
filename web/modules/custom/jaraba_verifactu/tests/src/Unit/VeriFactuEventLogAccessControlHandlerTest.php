<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_verifactu\Access\VeriFactuEventLogAccessControlHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests del AccessControlHandler para VeriFactuEventLog.
 *
 * Verifica que el log de eventos SIF es inmutable (append-only):
 * - update y delete SIEMPRE denegados para todos los roles.
 * - view permitido con permisos adecuados.
 * - create solo para administradores (programatico).
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Access\VeriFactuEventLogAccessControlHandler
 */
class VeriFactuEventLogAccessControlHandlerTest extends UnitTestCase {

  /**
   * The access control handler under test.
   */
  protected VeriFactuEventLogAccessControlHandler $handler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('id')->willReturn('verifactu_event_log');
    $entity_type->method('getAdminPermission')->willReturn('administer verifactu');

    $this->handler = new VeriFactuEventLogAccessControlHandler($entity_type);

    // Set up container with cache contexts manager.
    $container = new ContainerBuilder();
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Invokes the protected checkAccess method.
   */
  protected function callCheckAccess($entity, $operation, $account) {
    $reflection = new \ReflectionMethod($this->handler, 'checkAccess');
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($this->handler, $entity, $operation, $account);
  }

  /**
   * Helper to create a mock entity with cache metadata.
   */
  protected function createMockEntity(): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);
    return $entity;
  }

  /**
   * Tests that update is always forbidden.
   */
  public function testUpdateAlwaysForbidden(): void {
    $entity = $this->createMockEntity();
    $admin = $this->createMock(AccountInterface::class);

    $result = $this->callCheckAccess($entity, 'update', $admin);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that delete is always forbidden.
   */
  public function testDeleteAlwaysForbidden(): void {
    $entity = $this->createMockEntity();
    $admin = $this->createMock(AccountInterface::class);

    $result = $this->callCheckAccess($entity, 'delete', $admin);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that view is allowed for users with event log permission.
   */
  public function testViewAllowedWithPermission(): void {
    $entity = $this->createMockEntity();
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturnMap([
      ['administer verifactu', FALSE],
      ['view verifactu event log', TRUE],
    ]);

    $result = $this->callCheckAccess($entity, 'view', $account);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests that view is denied without any relevant permission.
   */
  public function testViewDeniedWithoutPermission(): void {
    $entity = $this->createMockEntity();
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);

    $result = $this->callCheckAccess($entity, 'view', $account);
    $this->assertFalse($result->isAllowed());
  }

}
