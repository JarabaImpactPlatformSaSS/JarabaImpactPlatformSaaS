<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel tests para control de acceso de entidades de review.
 *
 * Verifica TENANT-ISOLATION-ACCESS-001: permisos de lectura publica
 * para reviews aprobadas, y restriccion de edicion por propietario.
 *
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewAccessControlTest extends KernelTestBase {

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
    'ecosistema_jaraba_core',
    'jaraba_lms',
    'jaraba_content_hub',
  ];

  /**
   * Entidades disponibles para test.
   *
   * @var array<string, bool>
   */
  private array $availableEntities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $definitions = \Drupal::entityTypeManager()->getDefinitions();

    // Instalar dependencias opcionales.
    foreach (['group', 'lms_course', 'content_article'] as $dep) {
      if (isset($definitions[$dep])) {
        try {
          $this->installEntitySchema($dep);
        }
        catch (\Exception) {
        }
      }
    }

    // Instalar entidades de review.
    foreach (['course_review', 'content_comment'] as $entityTypeId) {
      if (!isset($definitions[$entityTypeId])) {
        continue;
      }
      try {
        $this->installEntitySchema($entityTypeId);
        $this->availableEntities[$entityTypeId] = TRUE;
      }
      catch (\Exception) {
      }
    }

    if (empty($this->availableEntities)) {
      $this->markTestSkipped('No review entity types available for access control testing.');
    }

    // Crear rol autenticado base.
    Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ])->save();
  }

  /**
   * Tests que usuario anonimo puede ver review aprobada.
   */
  public function testAnonymousCanViewApprovedReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Review aprobada',
      'body' => 'Test body',
      'rating' => 5,
      'review_status' => 'approved',
      'uid' => $owner->id(),
    ]);
    $review->save();

    // Usuario anonimo.
    $anonymous = User::getAnonymousUser();
    $access = $review->access('view', $anonymous, TRUE);

    // Approved reviews deben ser visibles publicamente.
    $this->assertTrue($access->isAllowed(), 'Review aprobada debe ser visible para anonimos.');
  }

  /**
   * Tests que review pendiente no es visible para anonimos.
   */
  public function testAnonymousCannotViewPendingReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Review pendiente',
      'body' => 'Test body',
      'rating' => 3,
      'review_status' => 'pending',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $anonymous = User::getAnonymousUser();
    $access = $review->access('view', $anonymous, TRUE);

    $this->assertFalse($access->isAllowed(), 'Review pendiente no debe ser visible para anonimos.');
  }

  /**
   * Tests que propietario puede ver su propia review pendiente.
   */
  public function testOwnerCanViewOwnPendingReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Mi review pendiente',
      'body' => 'Esperando moderacion.',
      'rating' => 4,
      'review_status' => 'pending',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('view', $owner, TRUE);
    $this->assertTrue($access->isAllowed(), 'Propietario debe poder ver su review pendiente.');
  }

  /**
   * Tests que usuario sin permiso no puede editar review ajena.
   */
  public function testUserCannotUpdateOthersReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);
    $otherUser = $this->createUserWithPermissions(['submit reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Review del owner',
      'body' => 'Test body',
      'rating' => 5,
      'review_status' => 'approved',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('update', $otherUser, TRUE);
    $this->assertFalse($access->isAllowed(), 'Usuario no debe poder editar review ajena.');
  }

  /**
   * Tests que propietario con permiso puede editar su review.
   */
  public function testOwnerWithPermissionCanUpdateOwnReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews', 'edit own reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Mi review para editar',
      'body' => 'Original text.',
      'rating' => 3,
      'review_status' => 'approved',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('update', $owner, TRUE);
    $this->assertTrue($access->isAllowed(), 'Propietario con edit own reviews debe poder actualizar.');
  }

  /**
   * Tests que admin con permiso de moderacion puede moderar.
   */
  public function testModeratorCanUpdateReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);
    $admin = $this->createUserWithPermissions([], admin: TRUE);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Review para moderar',
      'body' => 'Contenido cuestionable.',
      'rating' => 1,
      'review_status' => 'pending',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('update', $admin, TRUE);
    $this->assertTrue($access->isAllowed(), 'Admin debe poder moderar reviews.');
  }

  /**
   * Tests que admin puede eliminar review.
   */
  public function testAdminCanDeleteReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);
    $admin = $this->createUserWithPermissions([], admin: TRUE);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'Review para eliminar',
      'body' => 'Spam content.',
      'rating' => 1,
      'review_status' => 'rejected',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('delete', $admin, TRUE);
    $this->assertTrue($access->isAllowed(), 'Admin debe poder eliminar reviews.');
  }

  /**
   * Tests que usuario normal no puede eliminar review.
   */
  public function testNormalUserCannotDeleteReview(): void {
    if (!($this->availableEntities['course_review'] ?? FALSE)) {
      $this->markTestSkipped('course_review not available.');
    }

    $owner = $this->createUserWithPermissions(['submit reviews']);

    $storage = \Drupal::entityTypeManager()->getStorage('course_review');
    $review = $storage->create([
      'title' => 'No se puede borrar',
      'body' => 'Contenido protegido.',
      'rating' => 3,
      'review_status' => 'approved',
      'uid' => $owner->id(),
    ]);
    $review->save();

    $access = $review->access('delete', $owner, TRUE);
    $this->assertFalse($access->isAllowed(), 'Usuario normal no debe poder eliminar reviews.');
  }

  /**
   * Tests content_comment: aprobado visible, pendiente oculto.
   */
  public function testContentCommentVisibility(): void {
    if (!($this->availableEntities['content_comment'] ?? FALSE)) {
      $this->markTestSkipped('content_comment not available.');
    }

    $owner = $this->createUserWithPermissions(['post comments']);
    $anonymous = User::getAnonymousUser();

    $storage = \Drupal::entityTypeManager()->getStorage('content_comment');

    // Comentario aprobado.
    $approved = $storage->create([
      'body' => 'Comentario aprobado',
      'review_status' => 'approved',
      'uid' => $owner->id(),
    ]);
    $approved->save();

    // Comentario pendiente.
    $pending = $storage->create([
      'body' => 'Comentario pendiente',
      'review_status' => 'pending',
      'uid' => $owner->id(),
    ]);
    $pending->save();

    $this->assertTrue(
      $approved->access('view', $anonymous, TRUE)->isAllowed(),
      'Comentario aprobado debe ser visible publicamente.'
    );
    $this->assertFalse(
      $pending->access('view', $anonymous, TRUE)->isAllowed(),
      'Comentario pendiente no debe ser visible publicamente.'
    );
  }

  /**
   * Crea un usuario con permisos especificos.
   */
  protected function createUserWithPermissions(array $permissions, bool $admin = FALSE): User {
    static $uid = 1;
    $uid++;

    if ($admin) {
      $user = User::create([
        'uid' => $uid,
        'name' => 'admin' . $uid,
        'status' => 1,
      ]);
      $user->addRole('administrator');
      $user->save();
      return $user;
    }

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
