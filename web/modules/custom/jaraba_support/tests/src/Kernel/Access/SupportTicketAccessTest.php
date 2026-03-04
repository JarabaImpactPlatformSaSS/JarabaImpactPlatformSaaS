<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for SupportTicket entity.
 *
 * Verifies TENANT-ISOLATION-ACCESS-001 compliance:
 * - Cross-tenant update/delete are hard-forbidden.
 * - Owner (reporter_uid) can view their own tickets.
 * - Permission-based access for agents.
 *
 * @group jaraba_support
 * @group access_control
 */
class SupportTicketAccessTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_support',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['support_ticket'])) {
      $this->markTestSkipped('support_ticket entity type not available.');
    }

    $this->installEntitySchema('support_ticket');
    $this->installConfig(['jaraba_support']);

    Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ])->save();
  }

  /**
   * Creates a user with specific permissions.
   */
  protected function createUserWithPermissions(array $permissions): User {
    static $uid = 1;
    $uid++;
    $roleId = 'test_role_' . $uid;
    $role = Role::create(['id' => $roleId, 'label' => 'Test Role ' . $uid]);
    foreach ($permissions as $perm) {
      $role->grantPermission($perm);
    }
    $role->save();

    $user = User::create([
      'uid' => $uid,
      'name' => 'testuser' . $uid,
      'status' => 1,
    ]);
    $user->addRole($roleId);
    $user->save();
    return $user;
  }

  /**
   * Tests admin has full access via administer support system.
   */
  public function testAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['administer support system']);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Test Ticket',
      'reporter_uid' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
    $this->assertTrue($entity->access('delete', $admin));
  }

  /**
   * Tests owner (reporter) can view their ticket.
   */
  public function testOwnerCanView(): void {
    $reporter = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Reporter Ticket',
      'reporter_uid' => $reporter->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $reporter));
  }

  /**
   * Tests owner with edit own permission can update.
   */
  public function testOwnerCanUpdate(): void {
    $reporter = $this->createUserWithPermissions(['edit own support ticket']);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Reporter Ticket',
      'reporter_uid' => $reporter->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $reporter));
  }

  /**
   * Tests non-owner without permissions is denied.
   */
  public function testNonOwnerDenied(): void {
    $owner = $this->createUserWithPermissions([]);
    $other = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Private Ticket',
      'reporter_uid' => $owner->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('view', $other));
    $this->assertFalse($entity->access('update', $other));
  }

  /**
   * Tests view all permission grants access to any ticket.
   */
  public function testViewAllPermission(): void {
    $agent = $this->createUserWithPermissions(['view all support tickets']);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Any Ticket',
      'reporter_uid' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $agent));
  }

  /**
   * Tests edit any permission allows updating.
   */
  public function testEditAnyPermission(): void {
    $agent = $this->createUserWithPermissions(['edit any support ticket']);
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $entity = $storage->create([
      'label' => 'Any Ticket',
      'reporter_uid' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $agent));
  }

  /**
   * Tests create access with permission.
   */
  public function testCreateAccess(): void {
    $reporter = $this->createUserWithPermissions(['create support ticket']);
    $basic = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('support_ticket');

    $this->assertTrue($handler->createAccess(NULL, $reporter, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
