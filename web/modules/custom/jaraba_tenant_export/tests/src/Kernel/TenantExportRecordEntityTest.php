<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the TenantExportRecord entity.
 *
 * @group jaraba_tenant_export
 */
class TenantExportRecordEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'flexible_permissions',
    'group',
    'views',
    'ecosistema_jaraba_core',
    'jaraba_tenant_export',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('tenant_export_record');
    $this->installConfig(['jaraba_tenant_export']);
  }

  /**
   * Tests entity creation and CRUD operations.
   */
  public function testEntityCrud(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('tenant_export_record');

    // Create.
    $entity = $storage->create([
      'tenant_id' => 1,
      'requested_by' => 1,
      'export_type' => 'full',
      'status' => 'queued',
      'progress' => 0,
      'download_token' => 'test-token-uuid',
      'expires_at' => time() + 86400,
    ]);
    $entity->save();

    $this->assertNotEmpty($entity->id());
    $this->assertEquals('queued', $entity->get('status')->value);
    $this->assertEquals(0, $entity->getProgress());
    $this->assertFalse($entity->isCompleted());
    $this->assertFalse($entity->isExpired());

    // Update.
    $entity->set('status', 'completed');
    $entity->set('progress', 100);
    $entity->set('file_path', 'private://test.zip');
    $entity->set('file_size', 1024);
    $entity->set('file_hash', 'abc123');
    $entity->save();

    $loaded = $storage->load($entity->id());
    $this->assertEquals('completed', $loaded->get('status')->value);
    $this->assertEquals(100, $loaded->getProgress());
    $this->assertTrue($loaded->isCompleted());
    $this->assertTrue($loaded->isDownloadable());

    // Delete.
    $loaded->delete();
    $this->assertNull($storage->load($entity->id()));
  }

  /**
   * Tests status label mapping.
   */
  public function testStatusLabels(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('tenant_export_record');

    $statuses = ['queued', 'collecting', 'packaging', 'completed', 'failed', 'expired', 'cancelled'];

    foreach ($statuses as $status) {
      $entity = $storage->create([
        'tenant_id' => 1,
        'requested_by' => 1,
        'export_type' => 'full',
        'status' => $status,
      ]);
      $entity->save();

      $label = $entity->getStatusLabel();
      $this->assertNotEmpty($label, "Status '$status' should have a label.");
    }
  }

  /**
   * Tests expiration check.
   */
  public function testExpirationCheck(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('tenant_export_record');

    // Not expired.
    $entity = $storage->create([
      'tenant_id' => 1,
      'requested_by' => 1,
      'export_type' => 'full',
      'status' => 'completed',
      'expires_at' => time() + 3600,
    ]);
    $entity->save();
    $this->assertFalse($entity->isExpired());

    // Expired.
    $entity2 = $storage->create([
      'tenant_id' => 1,
      'requested_by' => 1,
      'export_type' => 'full',
      'status' => 'completed',
      'expires_at' => time() - 3600,
    ]);
    $entity2->save();
    $this->assertTrue($entity2->isExpired());
    $this->assertFalse($entity2->isDownloadable());
  }

}
