<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_vault\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for SecureDocument entity.
 *
 * Verifies owner-based access (owner_id field), permission checks,
 * and that owners cannot delete documents.
 *
 * @group jaraba_legal_vault
 * @group access_control
 */
class SecureDocumentAccessTest extends KernelTestBase {

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
    'taxonomy',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_legal_cases',
    'jaraba_legal_vault',
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
    if (!isset($definitions['secure_document'])) {
      $this->markTestSkipped('secure_document entity type not available.');
    }

    $this->installEntitySchema('secure_document');
    $this->installConfig(['jaraba_legal_vault']);

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
   * Tests admin has full access via administer vault permission.
   */
  public function testAdminFullAccess(): void {
    $admin = $this->createUserWithPermissions(['administer vault']);
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $entity = $storage->create([
      'label' => 'Test Document',
      'owner_id' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
    $this->assertTrue($entity->access('delete', $admin));
  }

  /**
   * Tests manager permission grants view and update.
   */
  public function testManagePermissionAccess(): void {
    $manager = $this->createUserWithPermissions(['manage vault documents']);
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $entity = $storage->create([
      'label' => 'Test Document',
      'owner_id' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $manager));
    $this->assertTrue($entity->access('update', $manager));
    $this->assertTrue($entity->access('delete', $manager));
  }

  /**
   * Tests owner can view and update their own document.
   */
  public function testOwnerAccess(): void {
    $owner = $this->createUserWithPermissions(['access vault']);
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $entity = $storage->create([
      'label' => 'Owner Document',
      'owner_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $owner));
    $this->assertTrue($entity->access('update', $owner));
  }

  /**
   * Tests owner cannot delete their own documents.
   */
  public function testOwnerCannotDelete(): void {
    $owner = $this->createUserWithPermissions(['access vault']);
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $entity = $storage->create([
      'label' => 'Owner Document',
      'owner_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('delete', $owner));
  }

  /**
   * Tests non-owner without permissions cannot access.
   */
  public function testNonOwnerDenied(): void {
    $owner = $this->createUserWithPermissions([]);
    $nonOwner = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('secure_document');
    $entity = $storage->create([
      'label' => 'Private Document',
      'owner_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('view', $nonOwner));
    $this->assertFalse($entity->access('update', $nonOwner));
    $this->assertFalse($entity->access('delete', $nonOwner));
  }

  /**
   * Tests create access requires manage or admin permission.
   */
  public function testCreateAccess(): void {
    $manager = $this->createUserWithPermissions(['manage vault documents']);
    $basic = $this->createUserWithPermissions(['access vault']);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('secure_document');

    $this->assertTrue($handler->createAccess(NULL, $manager, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
