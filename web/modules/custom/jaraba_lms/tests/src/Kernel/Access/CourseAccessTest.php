<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_lms\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for Course (lms_course) entity.
 *
 * Verifies published/unpublished access and permission-based CRUD.
 *
 * @group jaraba_lms
 * @group access_control
 */
class CourseAccessTest extends KernelTestBase {

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
    'ecosistema_jaraba_core',
    'jaraba_lms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['lms_course'])) {
      $this->markTestSkipped('lms_course entity type not available.');
    }

    $this->installEntitySchema('lms_course');
    $this->installConfig(['jaraba_lms']);

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
   * Tests published course is viewable with permission.
   */
  public function testPublishedCourseView(): void {
    $viewer = $this->createUserWithPermissions(['view published courses']);
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $entity = $storage->create([
      'label' => 'Published Course',
      'is_published' => TRUE,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests unpublished course requires specific permission.
   */
  public function testUnpublishedCourseView(): void {
    $basicViewer = $this->createUserWithPermissions(['view published courses']);
    $unpubViewer = $this->createUserWithPermissions(['view unpublished courses']);
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $entity = $storage->create([
      'label' => 'Unpublished Course',
      'is_published' => FALSE,
    ]);
    $entity->save();

    $this->assertFalse($entity->access('view', $basicViewer));
    $this->assertTrue($entity->access('view', $unpubViewer));
  }

  /**
   * Tests edit courses permission.
   */
  public function testEditPermission(): void {
    $editor = $this->createUserWithPermissions(['edit courses']);
    $basic = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $entity = $storage->create(['label' => 'Test Course']);
    $entity->save();

    $this->assertTrue($entity->access('update', $editor));
    $this->assertFalse($entity->access('update', $basic));
  }

  /**
   * Tests delete courses permission.
   */
  public function testDeletePermission(): void {
    $deleter = $this->createUserWithPermissions(['delete courses']);
    $basic = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $entity = $storage->create(['label' => 'Test Course']);
    $entity->save();

    $this->assertTrue($entity->access('delete', $deleter));
    $this->assertFalse($entity->access('delete', $basic));
  }

  /**
   * Tests create access.
   */
  public function testCreateAccess(): void {
    $creator = $this->createUserWithPermissions(['create courses']);
    $basic = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('lms_course');

    $this->assertTrue($handler->createAccess(NULL, $creator, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
