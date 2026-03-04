<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for BillingCustomer entity.
 *
 * Verifies permission-based access: administer billing,
 * manage billing customers. No tenant isolation in this handler.
 *
 * @group jaraba_billing
 * @group access_control
 */
class BillingCustomerAccessTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001: All required modules listed explicitly.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_billing',
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
    if (!isset($definitions['billing_customer'])) {
      $this->markTestSkipped('billing_customer entity type not available.');
    }

    $this->installEntitySchema('billing_customer');
    $this->installConfig(['jaraba_billing']);

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
   * Tests admin has full access to billing_customer.
   */
  public function testAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['administer billing']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_customer');
    $entity = $storage->create(['label' => 'Test Customer']);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
  }

  /**
   * Tests manage permission grants view and update.
   */
  public function testManagePermissionAccess(): void {
    $manager = $this->createUserWithPermissions(['manage billing customers']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_customer');
    $entity = $storage->create(['label' => 'Test Customer']);
    $entity->save();

    $this->assertTrue($entity->access('view', $manager));
    $this->assertTrue($entity->access('update', $manager));
  }

  /**
   * Tests authenticated user without permissions cannot access.
   */
  public function testNoPermissionDenied(): void {
    $user = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_customer');
    $entity = $storage->create(['label' => 'Test Customer']);
    $entity->save();

    $this->assertFalse($entity->access('view', $user));
    $this->assertFalse($entity->access('update', $user));
  }

  /**
   * Tests delete always returns neutral (not allowed).
   */
  public function testDeleteNeutral(): void {
    $manager = $this->createUserWithPermissions(['manage billing customers']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_customer');
    $entity = $storage->create(['label' => 'Test Customer']);
    $entity->save();

    $this->assertFalse($entity->access('delete', $manager));
  }

  /**
   * Tests create access with manage permission.
   */
  public function testCreateAccess(): void {
    $manager = $this->createUserWithPermissions(['manage billing customers']);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('billing_customer');
    $this->assertTrue($handler->createAccess(NULL, $manager, [], TRUE)->isAllowed());
  }

  /**
   * Tests create access denied without permission.
   */
  public function testCreateAccessDenied(): void {
    $user = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('billing_customer');
    $this->assertFalse($handler->createAccess(NULL, $user, [], TRUE)->isAllowed());
  }

}
