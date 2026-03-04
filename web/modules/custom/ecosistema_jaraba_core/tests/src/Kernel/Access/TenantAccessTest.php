<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for Tenant entity.
 *
 * Verifies:
 * - Admin bypass via administer tenants
 * - admin_user can view and update their tenant
 * - Non-admin_user cannot update
 * - Delete is always forbidden (except via admin permission bypass)
 *
 * @group ecosistema_jaraba_core
 * @group access_control
 */
class TenantAccessTest extends KernelTestBase {

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
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['tenant'])) {
      $this->markTestSkipped('tenant entity type not available.');
    }

    $this->installEntitySchema('tenant');
    $this->installConfig(['ecosistema_jaraba_core']);

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
   * Tests platform admin has full access.
   */
  public function testPlatformAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['administer tenants']);
    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $entity = $storage->create([
      'label' => 'Test Tenant',
      'admin_user' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
    $this->assertTrue($entity->access('delete', $admin));
  }

  /**
   * Tests admin_user of tenant can view and update.
   */
  public function testAdminUserCanViewAndUpdate(): void {
    $tenantAdmin = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $entity = $storage->create([
      'label' => 'Admin Tenant',
      'admin_user' => $tenantAdmin->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $tenantAdmin));
    $this->assertTrue($entity->access('update', $tenantAdmin));
  }

  /**
   * Tests admin_user cannot delete their tenant.
   */
  public function testAdminUserCannotDelete(): void {
    $tenantAdmin = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $entity = $storage->create([
      'label' => 'Admin Tenant',
      'admin_user' => $tenantAdmin->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('delete', $tenantAdmin));
  }

  /**
   * Tests non-admin user of different tenant is denied update.
   */
  public function testNonAdminUserDenied(): void {
    $tenantAdmin = $this->createUserWithPermissions([]);
    $otherUser = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $entity = $storage->create([
      'label' => 'Protected Tenant',
      'admin_user' => $tenantAdmin->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('update', $otherUser));
  }

  /**
   * Tests create access for authenticated users.
   */
  public function testCreateAccess(): void {
    $user = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('tenant');

    // Any authenticated user can create (self-registration).
    $this->assertTrue($handler->createAccess(NULL, $user, [], TRUE)->isAllowed());
  }

}
