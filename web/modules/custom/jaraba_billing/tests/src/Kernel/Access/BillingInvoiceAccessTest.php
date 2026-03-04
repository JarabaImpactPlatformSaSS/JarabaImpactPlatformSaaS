<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for BillingInvoice entity.
 *
 * Verifies permission-based access: administer billing,
 * view all invoices, view own invoices. No tenant isolation.
 *
 * @group jaraba_billing
 * @group access_control
 */
class BillingInvoiceAccessTest extends KernelTestBase {

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
    'jaraba_billing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['billing_invoice'])) {
      $this->markTestSkipped('billing_invoice entity type not available.');
    }

    $this->installEntitySchema('billing_invoice');
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
   * Tests admin has full access.
   */
  public function testAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['administer billing']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $entity = $storage->create(['label' => 'INV-001']);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
  }

  /**
   * Tests view all invoices permission.
   */
  public function testViewAllInvoices(): void {
    $viewer = $this->createUserWithPermissions(['view all invoices']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $entity = $storage->create(['label' => 'INV-002']);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests view own invoices permission.
   */
  public function testViewOwnInvoices(): void {
    $viewer = $this->createUserWithPermissions(['view own invoices']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $entity = $storage->create(['label' => 'INV-003']);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests user without permissions cannot view invoices.
   */
  public function testNoPermissionDenied(): void {
    $user = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $entity = $storage->create(['label' => 'INV-004']);
    $entity->save();

    $this->assertFalse($entity->access('view', $user));
  }

  /**
   * Tests update and delete are restricted.
   */
  public function testUpdateDeleteRestricted(): void {
    $viewer = $this->createUserWithPermissions(['view all invoices']);
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $entity = $storage->create(['label' => 'INV-005']);
    $entity->save();

    $this->assertFalse($entity->access('update', $viewer));
    $this->assertFalse($entity->access('delete', $viewer));
  }

  /**
   * Tests create access requires admin permission.
   */
  public function testCreateAccessRequiresAdmin(): void {
    $admin = $this->createUserWithPermissions(['administer billing']);
    $viewer = $this->createUserWithPermissions(['view all invoices']);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('billing_invoice');

    $this->assertTrue($handler->createAccess(NULL, $admin, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $viewer, [], TRUE)->isAllowed());
  }

}
