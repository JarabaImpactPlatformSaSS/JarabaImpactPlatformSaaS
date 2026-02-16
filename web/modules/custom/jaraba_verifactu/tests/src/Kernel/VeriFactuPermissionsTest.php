<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactu permission enforcement.
 *
 * Verifies that the 7 VeriFactu permissions are correctly defined
 * and that access control handlers enforce them.
 *
 * @group jaraba_verifactu
 */
class VeriFactuPermissionsTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
    'jaraba_billing',
    'jaraba_verifactu',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.certificate_manager')
      ->setSynthetic(TRUE);
    $container->register('jaraba_foc.stripe_connect')
      ->setSynthetic(TRUE);
  }

  protected function setUp(): void {
    parent::setUp();
    $this->container->set(
      'ecosistema_jaraba_core.certificate_manager',
      $this->createMock(\Drupal\ecosistema_jaraba_core\Service\CertificateManagerService::class),
    );
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_invoice_record');
    $this->installEntitySchema('verifactu_event_log');
    $this->installEntitySchema('verifactu_tenant_config');
    $this->installConfig(['jaraba_verifactu']);
    // Create uid=1 (super admin in Drupal) so test users get uid>=2.
    User::create(['name' => 'admin', 'status' => 1])->save();
  }

  /**
   * Tests that all 7 permissions are defined in the module.
   */
  public function testPermissionsAreDefined(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();

    $expected = [
      'administer verifactu',
      'view verifactu records',
      'create verifactu records',
      'view verifactu event log',
      'manage verifactu remision',
      'manage verifactu config',
      'access verifactu api',
    ];

    foreach ($expected as $perm) {
      $this->assertArrayHasKey($perm, $permissions, "Permission '$perm' should be defined.");
    }
  }

  /**
   * Tests that 'administer verifactu' has restrict access flag.
   */
  public function testAdminPermissionRestricted(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertTrue(
      $permissions['administer verifactu']['restrict access'] ?? FALSE,
      'administer verifactu should have restrict access flag.'
    );
  }

  /**
   * Tests that user without permissions cannot view records.
   */
  public function testAccessDeniedWithoutPermission(): void {
    $user = User::create([
      'name' => 'test_user',
      'mail' => 'test@example.com',
      'status' => 1,
    ]);
    $user->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-001',
      'hash_record' => str_repeat('a', 64),
    ]);
    $record->save();

    $accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('verifactu_invoice_record');

    $result = $accessHandler->access($record, 'view', $user, TRUE);
    $this->assertFalse($result->isAllowed());
  }

  /**
   * Tests that user with 'view verifactu records' can view records.
   */
  public function testAccessAllowedWithPermission(): void {
    $role = Role::create([
      'id' => 'verifactu_viewer',
      'label' => 'VeriFactu Viewer',
    ]);
    $role->grantPermission('view verifactu records');
    $role->save();

    $user = User::create([
      'name' => 'viewer_user',
      'mail' => 'viewer@example.com',
      'status' => 1,
    ]);
    $user->addRole('verifactu_viewer');
    $user->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-002',
      'hash_record' => str_repeat('b', 64),
    ]);
    $record->save();

    $accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('verifactu_invoice_record');

    $result = $accessHandler->access($record, 'view', $user, TRUE);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests that invoice records are append-only (no update/delete).
   */
  public function testInvoiceRecordAppendOnly(): void {
    $role = Role::create([
      'id' => 'verifactu_admin',
      'label' => 'VeriFactu Admin',
    ]);
    $role->grantPermission('administer verifactu');
    $role->grantPermission('view verifactu records');
    $role->grantPermission('create verifactu records');
    $role->save();

    $user = User::create([
      'name' => 'admin_user',
      'mail' => 'admin@example.com',
      'status' => 1,
    ]);
    $user->addRole('verifactu_admin');
    $user->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-003',
      'hash_record' => str_repeat('c', 64),
    ]);
    $record->save();

    $accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('verifactu_invoice_record');

    // Update should be forbidden even for admin.
    $updateResult = $accessHandler->access($record, 'update', $user, TRUE);
    $this->assertTrue($updateResult->isForbidden());

    // Delete should be forbidden even for admin.
    $deleteResult = $accessHandler->access($record, 'delete', $user, TRUE);
    $this->assertTrue($deleteResult->isForbidden());
  }

  /**
   * Tests that event log entries are immutable (no update/delete).
   */
  public function testEventLogImmutability(): void {
    $role = Role::create([
      'id' => 'verifactu_auditor',
      'label' => 'VeriFactu Auditor',
    ]);
    $role->grantPermission('administer verifactu');
    $role->grantPermission('view verifactu event log');
    $role->save();

    $user = User::create([
      'name' => 'auditor_user',
      'mail' => 'auditor@example.com',
      'status' => 1,
    ]);
    $user->addRole('verifactu_auditor');
    $user->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_event_log');

    $event = $storage->create([
      'tenant_id' => 1,
      'event_type' => 'RECORD_CREATE',
      'severity' => 'info',
      'hash_event' => str_repeat('d', 64),
    ]);
    $event->save();

    $accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('verifactu_event_log');

    // Update always forbidden.
    $this->assertTrue($accessHandler->access($event, 'update', $user, TRUE)->isForbidden());

    // Delete always forbidden.
    $this->assertTrue($accessHandler->access($event, 'delete', $user, TRUE)->isForbidden());
  }

}
