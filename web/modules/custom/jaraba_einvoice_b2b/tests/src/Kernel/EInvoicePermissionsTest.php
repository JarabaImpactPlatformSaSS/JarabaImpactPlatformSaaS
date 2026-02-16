<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for permission definitions.
 *
 * Verifies all 10 permissions are correctly defined and discoverable.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoicePermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests all 10 permissions are defined.
   */
  public function testAllPermissionsDefined(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();

    $expected = [
      'administer einvoice b2b',
      'view einvoice documents',
      'create einvoice documents',
      'delete einvoice documents',
      'send einvoice',
      'receive einvoice',
      'view einvoice delivery logs',
      'manage einvoice payment events',
      'manage einvoice config',
      'view einvoice reports',
    ];

    foreach ($expected as $permission) {
      $this->assertArrayHasKey($permission, $permissions, "Permission '{$permission}' must be defined.");
    }
  }

  /**
   * Tests admin permission has restrict access.
   */
  public function testAdminPermissionIsRestricted(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertTrue(
      $permissions['administer einvoice b2b']['restrict access'] ?? FALSE,
      'Admin permission must have restrict access.',
    );
  }

  /**
   * Tests permissions have titles.
   */
  public function testPermissionsHaveTitles(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $modulePerms = array_filter($permissions, fn($p) => ($p['provider'] ?? '') === 'jaraba_einvoice_b2b');

    $this->assertCount(10, $modulePerms, 'Module should define exactly 10 permissions.');

    foreach ($modulePerms as $name => $perm) {
      $this->assertNotEmpty($perm['title'], "Permission '{$name}' must have a title.");
    }
  }

}
