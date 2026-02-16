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
    $entity_type->method('getHandlerClasses')->willReturn([]);

    $this->handler = new VeriFactuEventLogAccessControlHandler($entity_type);

    // Set up container with cache contexts manager.
    $container = new ContainerBuilder();
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests that update is always forbidden.
   */
  public function testUpdateAlwaysForbidden(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('verifactu_event_log');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $admin = $this->createMock(AccountInterface::class);
    $admin->method('hasPermission')->willReturn(TRUE);
    $admin->method('id')->willReturn(1);

    $result = $this->handler->access($entity, 'update', $admin, TRUE);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that delete is always forbidden.
   */
  public function testDeleteAlwaysForbidden(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('verifactu_event_log');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $admin = $this->createMock(AccountInterface::class);
    $admin->method('hasPermission')->willReturn(TRUE);
    $admin->method('id')->willReturn(1);

    $result = $this->handler->access($entity, 'delete', $admin, TRUE);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that view is allowed for users with event log permission.
   */
  public function testViewAllowedWithPermission(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('verifactu_event_log');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturnCallback(function (string $perm): bool {
      return $perm === 'view verifactu event log';
    });
    $account->method('id')->willReturn(2);

    $result = $this->handler->access($entity, 'view', $account, TRUE);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests that view is denied without any relevant permission.
   */
  public function testViewDeniedWithoutPermission(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('verifactu_event_log');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);
    $account->method('id')->willReturn(3);

    $result = $this->handler->access($entity, 'view', $account, TRUE);
    $this->assertFalse($result->isAllowed());
  }

}
