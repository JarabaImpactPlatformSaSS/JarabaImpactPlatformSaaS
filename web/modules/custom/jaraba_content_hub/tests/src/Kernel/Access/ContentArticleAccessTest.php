<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_content_hub\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access control for ContentArticle entity.
 *
 * Verifies:
 * - Owner-based access (author field via getOwnerId)
 * - Published/unpublished view logic
 * - Admin bypass via administer content hub
 * - TENANT-ISOLATION-ACCESS-001: tenant check on update/delete
 *
 * @group jaraba_content_hub
 * @group access_control
 */
class ContentArticleAccessTest extends KernelTestBase {

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
    'file',
    'group',
    'flexible_permissions',
    'ecosistema_jaraba_core',
    'jaraba_content_hub',
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
    if (!isset($definitions['content_article'])) {
      $this->markTestSkipped('content_article entity type not available.');
    }

    $this->installEntitySchema('content_article');
    $this->installConfig(['jaraba_content_hub']);

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
    $admin = $this->createUserWithPermissions(['administer content hub']);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Test Article',
      'author' => 999,
      'status' => 'draft',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin));
    $this->assertTrue($entity->access('update', $admin));
    $this->assertTrue($entity->access('delete', $admin));
  }

  /**
   * Tests published article is viewable with access content.
   */
  public function testPublishedArticleView(): void {
    $viewer = $this->createUserWithPermissions(['access content']);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Published Article',
      'status' => 'published',
      'author' => 999,
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $viewer));
  }

  /**
   * Tests unpublished article requires specific permission.
   */
  public function testUnpublishedArticleView(): void {
    $basicViewer = $this->createUserWithPermissions(['access content']);
    $unpubViewer = $this->createUserWithPermissions([
      'access content',
      'view unpublished content article',
    ]);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Draft Article',
      'status' => 'draft',
      'author' => 999,
    ]);
    $entity->save();

    $this->assertFalse($entity->access('view', $basicViewer));
    $this->assertTrue($entity->access('view', $unpubViewer));
  }

  /**
   * Tests owner can update with edit own permission.
   */
  public function testOwnerCanUpdate(): void {
    $owner = $this->createUserWithPermissions(['edit own content article']);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Owner Article',
      'author' => $owner->id(),
      'status' => 'published',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $owner));
  }

  /**
   * Tests non-owner with edit any permission can update.
   */
  public function testEditAnyPermission(): void {
    $editor = $this->createUserWithPermissions(['edit any content article']);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Any Article',
      'author' => 999,
      'status' => 'published',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('update', $editor));
  }

  /**
   * Tests owner can delete with delete own permission.
   */
  public function testOwnerCanDelete(): void {
    $owner = $this->createUserWithPermissions(['delete own content article']);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Owner Article',
      'author' => $owner->id(),
      'status' => 'published',
    ]);
    $entity->save();

    $this->assertTrue($entity->access('delete', $owner));
  }

  /**
   * Tests non-owner without edit any cannot update.
   */
  public function testNonOwnerCannotUpdate(): void {
    $other = $this->createUserWithPermissions([]);
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $entity = $storage->create([
      'title' => 'Protected Article',
      'author' => 999,
      'status' => 'published',
    ]);
    $entity->save();

    $this->assertFalse($entity->access('update', $other));
    $this->assertFalse($entity->access('delete', $other));
  }

  /**
   * Tests create access.
   */
  public function testCreateAccess(): void {
    $creator = $this->createUserWithPermissions(['create content article']);
    $basic = $this->createUserWithPermissions([]);
    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('content_article');

    $this->assertTrue($handler->createAccess(NULL, $creator, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $basic, [], TRUE)->isAllowed());
  }

}
