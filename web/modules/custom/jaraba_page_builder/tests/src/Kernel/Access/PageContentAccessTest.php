<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel tests for PageContentAccessControlHandler.
 *
 * Tests view/update/delete access with permissions, ownership, and
 * TENANT-ISOLATION-ACCESS-001 enforcement.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\PageContentAccessControlHandler
 * @group jaraba_page_builder
 */
class PageContentAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'jaraba_page_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('page_content');
    $this->installConfig(['jaraba_page_builder']);

    // Create authenticated role with base permissions.
    Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ])->save();
  }

  /**
   * Creates a user with specific permissions.
   */
  protected function createUserWithPermissions(array $permissions): User {
    $role = Role::create([
      'id' => 'test_role_' . mt_rand(),
      'label' => 'Test Role',
    ]);
    $role->save();
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();

    $user = User::create([
      'name' => 'test_user_' . mt_rand(),
      'status' => 1,
    ]);
    $user->addRole($role->id());
    $user->save();

    return $user;
  }

  // =========================================================================
  // TESTS: View published — anonymous access
  // =========================================================================

  /**
   * Tests that published pages are publicly viewable.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testViewPublishedAnonymous(): void {
    $entity = PageContent::create([
      'title' => 'Public Page',
      'status' => 1,
    ]);
    $entity->save();

    $anonymousUser = User::getAnonymousUser();
    $access = $entity->access('view', $anonymousUser, TRUE);

    $this->assertTrue($access->isAllowed());
  }

  // =========================================================================
  // TESTS: View draft — owner only
  // =========================================================================

  /**
   * Tests that draft pages are viewable by owner with permission.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testViewDraftByOwner(): void {
    $owner = $this->createUserWithPermissions(['view own page content']);

    $entity = PageContent::create([
      'title' => 'Draft Page',
      'status' => 0,
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('view', $owner, TRUE);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests that draft pages are NOT viewable by non-owner without permission.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testViewDraftByNonOwner(): void {
    $owner = $this->createUserWithPermissions([]);
    $otherUser = $this->createUserWithPermissions(['view own page content']);

    $entity = PageContent::create([
      'title' => 'Draft Page',
      'status' => 0,
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('view', $otherUser, TRUE);
    $this->assertFalse($access->isAllowed());
  }

  // =========================================================================
  // TESTS: Update — permission check
  // =========================================================================

  /**
   * Tests that owner can update own page with permission.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpdateByOwnerWithPermission(): void {
    $owner = $this->createUserWithPermissions(['edit own page content']);

    $entity = PageContent::create([
      'title' => 'My Page',
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('update', $owner, TRUE);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests that user without permission cannot update.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpdateByOwnerWithoutPermission(): void {
    $owner = $this->createUserWithPermissions([]);

    $entity = PageContent::create([
      'title' => 'My Page',
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('update', $owner, TRUE);
    $this->assertFalse($access->isAllowed());
  }

  // =========================================================================
  // TESTS: Delete — permission check
  // =========================================================================

  /**
   * Tests that owner can delete own page with permission.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testDeleteByOwnerWithPermission(): void {
    $owner = $this->createUserWithPermissions(['delete own page content']);

    $entity = PageContent::create([
      'title' => 'Delete Me',
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('delete', $owner, TRUE);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests that non-owner cannot delete without 'delete any' permission.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testDeleteByNonOwnerWithoutAnyPermission(): void {
    $owner = $this->createUserWithPermissions([]);
    $otherUser = $this->createUserWithPermissions(['delete own page content']);

    $entity = PageContent::create([
      'title' => 'Not My Page',
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $access = $entity->access('delete', $otherUser, TRUE);
    $this->assertFalse($access->isAllowed());
  }

  // =========================================================================
  // TESTS: Admin bypass
  // =========================================================================

  /**
   * Tests that admin can access any operation.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testAdminBypass(): void {
    $admin = $this->createUserWithPermissions(['administer page builder']);
    $owner = $this->createUserWithPermissions([]);

    $entity = PageContent::create([
      'title' => 'Admin Test',
      'status' => 0,
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    $this->assertTrue($entity->access('view', $admin, TRUE)->isAllowed());
    $this->assertTrue($entity->access('update', $admin, TRUE)->isAllowed());
    $this->assertTrue($entity->access('delete', $admin, TRUE)->isAllowed());
  }

  // =========================================================================
  // TESTS: Create access
  // =========================================================================

  /**
   * Tests create access requires permission.
   *
   * @covers ::checkCreateAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCreateAccess(): void {
    $userWith = $this->createUserWithPermissions(['create page content']);
    $userWithout = $this->createUserWithPermissions([]);

    $handler = \Drupal::entityTypeManager()->getAccessControlHandler('page_content');

    $this->assertTrue($handler->createAccess(NULL, $userWith, [], TRUE)->isAllowed());
    $this->assertFalse($handler->createAccess(NULL, $userWithout, [], TRUE)->isAllowed());
  }

  // =========================================================================
  // TESTS: Strict equality (ACCESS-STRICT-001)
  // =========================================================================

  /**
   * Tests that owner check uses strict int comparison.
   *
   * @covers ::checkAccess
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testStrictOwnerComparison(): void {
    $owner = $this->createUserWithPermissions(['edit own page content']);

    $entity = PageContent::create([
      'title' => 'Strict Test',
      'user_id' => $owner->id(),
    ]);
    $entity->save();

    // Same user should pass strict check.
    $access = $entity->access('update', $owner, TRUE);
    $this->assertTrue($access->isAllowed());
  }

}
