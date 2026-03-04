<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_candidate\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for CandidateProfile entity.
 *
 * Verifies owner-based access (user_id), public/private profile logic,
 * and employer view permissions.
 *
 * @group jaraba_candidate
 * @group access_control
 */
class CandidateProfileAccessTest extends KernelTestBase {

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
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_candidate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['candidate_profile'])) {
      $this->markTestSkipped('candidate_profile entity type not available.');
    }

    $this->installEntitySchema('candidate_profile');
    $this->installConfig(['jaraba_candidate']);

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
   * Tests owner can always view their own profile.
   */
  public function testOwnerCanView(): void {
    $owner = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
      'is_public' => FALSE,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $owner));
  }

  /**
   * Tests owner can always update their own profile.
   */
  public function testOwnerCanUpdate(): void {
    $owner = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $owner));
  }

  /**
   * Tests public profile visible with view permission.
   */
  public function testPublicProfileViewWithPermission(): void {
    $owner = $this->createUserWithPermissions([]);
    $viewer = $this->createUserWithPermissions(['view candidate profiles']);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
      'is_public' => TRUE,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests private profile requires specific permission.
   */
  public function testPrivateProfileRequiresPermission(): void {
    $owner = $this->createUserWithPermissions([]);
    $basicViewer = $this->createUserWithPermissions(['view candidate profiles']);
    $privateViewer = $this->createUserWithPermissions(['view private candidate profiles']);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
      'is_public' => FALSE,
    ]);
    $entity->save();

    $this->assertFalse($entity->access('view', $basicViewer));
    $this->assertTrue($entity->access('view', $privateViewer));
  }

  /**
   * Tests non-owner with edit any permission can update.
   */
  public function testEditAnyPermission(): void {
    $owner = $this->createUserWithPermissions([]);
    $editor = $this->createUserWithPermissions(['edit any candidate profiles']);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $editor));
  }

  /**
   * Tests non-owner without edit any cannot update.
   */
  public function testNonOwnerCannotUpdate(): void {
    $owner = $this->createUserWithPermissions([]);
    $other = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('update', $other));
  }

  /**
   * Tests delete requires specific permission.
   */
  public function testDeleteRequiresPermission(): void {
    $owner = $this->createUserWithPermissions([]);
    $deleter = $this->createUserWithPermissions(['delete candidate profiles']);
    $storage = \Drupal::entityTypeManager()->getStorage('candidate_profile');
    $entity = $storage->create([
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertFalse($entity->access('delete', $owner));
    $this->assertTrue($entity->access('delete', $deleter));
  }

  /**
   * Tests create access.
   */
  public function testCreateAccess(): void {
    $creator = $this->createUserWithPermissions(['create candidate profile']);
    $basic = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('candidate_profile');

    $this->assertTrue($handler->createAccess(NULL, $creator, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
