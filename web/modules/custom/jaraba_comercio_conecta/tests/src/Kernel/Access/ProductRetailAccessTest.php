<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_comercio_conecta\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for ProductRetail entity.
 *
 * Verifies:
 * - Admin bypass via manage comercio products
 * - Active products are publicly viewable
 * - Merchant ownership check for updates
 *
 * @group jaraba_comercio_conecta
 * @group access_control
 */
class ProductRetailAccessTest extends KernelTestBase {

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
    'image',
    'file',
    'taxonomy',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_comercio_conecta',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['product_retail'])) {
      $this->markTestSkipped('product_retail entity type not available.');
    }

    $this->installEntitySchema('product_retail');

    if (isset($definitions['merchant_profile'])) {
      $this->installEntitySchema('merchant_profile');
    }

    $this->installConfig(['jaraba_comercio_conecta']);

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
   * Tests admin has full access via manage comercio products.
   */
  public function testAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['manage comercio products']);
    $storage = \Drupal::entityTypeManager()->getStorage('product_retail');
    $entity = $storage->create([
      'label' => 'Test Product',
      'status' => 'inactive',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
  }

  /**
   * Tests active product is publicly viewable.
   */
  public function testActiveProductPublicView(): void {
    $viewer = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('product_retail');
    $entity = $storage->create([
      'label' => 'Active Product',
      'status' => 'active',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests inactive product not publicly viewable.
   */
  public function testInactiveProductNotPublic(): void {
    $viewer = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('product_retail');
    $entity = $storage->create([
      'label' => 'Inactive Product',
      'status' => 'inactive',
    ]);
    $entity->save();

    // Without manage permission and not merchant owner, view should be denied.
    $this->assertFalse($entity->access('view', $viewer));
  }

  /**
   * Tests non-admin cannot update without merchant ownership.
   */
  public function testNonMerchantCannotUpdate(): void {
    $user = $this->createUserWithPermissions(['edit own comercio products']);
    $storage = \Drupal::entityTypeManager()->getStorage('product_retail');
    $entity = $storage->create([
      'label' => 'Other Product',
      'status' => 'active',
    ]);
    $entity->save();

    // Without merchant_id linking to this user, update is denied.
    $this->assertFalse($entity->access('update', $user));
  }

  /**
   * Tests create access with permission.
   */
  public function testCreateAccess(): void {
    $creator = $this->createUserWithPermissions(['edit own comercio products']);
    $basic = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('product_retail');

    $this->assertTrue($handler->createAccess(NULL, $creator, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
