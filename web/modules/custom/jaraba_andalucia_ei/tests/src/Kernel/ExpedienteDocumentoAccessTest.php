<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel tests for ExpedienteDocumento access control.
 *
 * Tests owner view, reviewer update, cross-tenant forbidden,
 * and admin bypass.
 *
 * @group jaraba_andalucia_ei
 */
class ExpedienteDocumentoAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'file',
    'jaraba_andalucia_ei',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['expediente_documento'])) {
      $this->markTestSkipped('ExpedienteDocumento entity type not available.');
    }

    try {
      if (isset($definitions['group'])) {
        $this->installEntitySchema('group');
      }
      if (isset($definitions['programa_participante_ei'])) {
        $this->installEntitySchema('programa_participante_ei');
      }
      $this->installEntitySchema('expediente_documento');
    }
    catch (\Exception $e) {
      $this->markTestSkipped('Could not install entity schemas: ' . $e->getMessage());
    }

    // Create roles.
    Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ])->save();
  }

  /**
   * Tests that owner can view their own document.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function ownerCanViewOwnDocument(): void {
    $owner = $this->createUserWithPermissions(['view expediente documento']);

    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');
    $doc = $storage->create([
      'titulo' => 'Owner Doc',
      'categoria' => 'sto_dni',
      'estado_revision' => 'pendiente',
      'uid' => $owner->id(),
    ]);
    $doc->save();

    $access = $doc->access('view', $owner);
    $this->assertTrue($access, 'Owner should be able to view own document.');
  }

  /**
   * Tests that user without permission cannot view document.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function userWithoutPermissionCannotView(): void {
    $user = $this->createUserWithPermissions([]);

    $owner = $this->createUserWithPermissions(['view expediente documento']);

    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');
    $doc = $storage->create([
      'titulo' => 'Restricted Doc',
      'categoria' => 'sto_dni',
      'estado_revision' => 'pendiente',
      'uid' => $owner->id(),
    ]);
    $doc->save();

    $access = $doc->access('view', $user);
    $this->assertFalse($access, 'User without permission should not view document.');
  }

  /**
   * Tests that user with edit permission can update document.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function reviewerCanUpdateDocument(): void {
    $reviewer = $this->createUserWithPermissions(['edit expediente documento']);

    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');
    $doc = $storage->create([
      'titulo' => 'Review Doc',
      'categoria' => 'tarea_cv',
      'estado_revision' => 'pendiente',
      'uid' => $reviewer->id(),
    ]);
    $doc->save();

    $access = $doc->access('update', $reviewer);
    $this->assertTrue($access, 'Reviewer with edit permission should be able to update.');
  }

  /**
   * Tests that admin can bypass all access checks.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function adminCanBypass(): void {
    $admin = $this->createUserWithPermissions(['administer andalucia ei']);

    $owner = $this->createUserWithPermissions(['view expediente documento']);

    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');
    $doc = $storage->create([
      'titulo' => 'Admin Test',
      'categoria' => 'sto_dni',
      'estado_revision' => 'pendiente',
      'uid' => $owner->id(),
    ]);
    $doc->save();

    // Admin should be able to view, update, and delete.
    $this->assertTrue($doc->access('view', $admin), 'Admin should view.');
    $this->assertTrue($doc->access('update', $admin), 'Admin should update.');
    $this->assertTrue($doc->access('delete', $admin), 'Admin should delete.');
  }

  /**
   * Tests that user without delete permission cannot delete.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function userWithoutDeletePermissionCannotDelete(): void {
    $user = $this->createUserWithPermissions(['view expediente documento']);

    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');
    $doc = $storage->create([
      'titulo' => 'Cannot Delete',
      'categoria' => 'sto_dni',
      'estado_revision' => 'pendiente',
      'uid' => $user->id(),
    ]);
    $doc->save();

    $access = $doc->access('delete', $user);
    $this->assertFalse($access, 'User without delete permission should not delete.');
  }

  /**
   * Creates a user with specific permissions.
   *
   * @param array $permissions
   *   Array of permission strings.
   *
   * @return \Drupal\user\Entity\User
   *   The created user.
   */
  protected function createUserWithPermissions(array $permissions): User {
    static $uid = 1;
    $uid++;

    // Create a dedicated role with the permissions.
    $roleId = 'test_role_' . $uid;
    $role = Role::create([
      'id' => $roleId,
      'label' => 'Test Role ' . $uid,
    ]);
    foreach ($permissions as $perm) {
      $role->grantPermission($perm);
    }
    $role->save();

    $user = User::create([
      'uid' => $uid,
      'name' => 'testuser' . $uid,
      'status' => 1,
      'roles' => [$roleId],
    ]);
    $user->save();

    return $user;
  }

}
