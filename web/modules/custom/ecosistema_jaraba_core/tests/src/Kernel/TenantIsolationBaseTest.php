<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Abstract base for tenant isolation tests.
 *
 * Sets up 2 tenants with 2 users and provides helpers for
 * verifying cross-tenant isolation in entity access and queries.
 *
 * Directivas:
 * - TENANT-001: TODA query DEBE filtrar por tenant.
 * - TENANT-ISOLATION-ACCESS-001: AccessControlHandler verifica tenant match.
 * - ACCESS-STRICT-001: Comparaciones con (int)..===(int).
 *
 * @group tenant_isolation
 */
abstract class TenantIsolationBaseTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001: All required modules listed.
   *
   * Subclasses should merge additional modules:
   *   protected static $modules = [...parent::$modules, 'my_module'];
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
    'ecosistema_jaraba_core',
  ];

  /**
   * Tenant A entity (or NULL if entity type unavailable).
   */
  protected ?object $tenantA = NULL;

  /**
   * Tenant B entity (or NULL if entity type unavailable).
   */
  protected ?object $tenantB = NULL;

  /**
   * User belonging to Tenant A.
   */
  protected User $userA;

  /**
   * User belonging to Tenant B.
   */
  protected User $userB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    Role::create([
      'id' => 'authenticated',
      'label' => 'Authenticated',
    ])->save();

    // Create users first (needed as admin_user references).
    $this->userA = $this->createUser(2, 'user_tenant_a');
    $this->userB = $this->createUser(3, 'user_tenant_b');

    // Create tenants if entity type available.
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (isset($definitions['tenant'])) {
      $this->installEntitySchema('tenant');

      $storage = \Drupal::entityTypeManager()->getStorage('tenant');
      $this->tenantA = $storage->create([
        'label' => 'Tenant A',
        'admin_user' => $this->userA->id(),
      ]);
      $this->tenantA->save();

      $this->tenantB = $storage->create([
        'label' => 'Tenant B',
        'admin_user' => $this->userB->id(),
      ]);
      $this->tenantB->save();
    }
  }

  /**
   * Creates a user with a specific UID.
   */
  protected function createUser(int $uid, string $name): User {
    $user = User::create([
      'uid' => $uid,
      'name' => $name,
      'status' => 1,
    ]);
    $user->save();
    return $user;
  }

  /**
   * Creates a user with specific permissions.
   */
  protected function createUserWithPermissions(int $uid, string $name, array $permissions): User {
    $roleId = 'role_' . $uid;
    $role = Role::create(['id' => $roleId, 'label' => 'Role ' . $uid]);
    foreach ($permissions as $perm) {
      $role->grantPermission($perm);
    }
    $role->save();

    $user = User::create([
      'uid' => $uid,
      'name' => $name,
      'status' => 1,
    ]);
    $user->addRole($roleId);
    $user->save();
    return $user;
  }

  /**
   * Gets the tenant ID for a given tenant entity.
   *
   * @return int
   *   The tenant entity ID.
   */
  protected function getTenantId(object $tenant): int {
    return (int) $tenant->id();
  }

}
