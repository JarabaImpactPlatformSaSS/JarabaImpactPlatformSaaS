<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the TenantExportCleanupWorker.
 *
 * @group jaraba_tenant_export
 */
class TenantExportCleanupWorkerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
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
   * Tests cleanup queue worker plugin definition.
   */
  public function testCleanupWorkerPluginDefinition(): void {
    $queueManager = $this->container->get('plugin.manager.queue_worker');
    $definition = $queueManager->getDefinition('jaraba_tenant_export_cleanup');

    $this->assertEquals('jaraba_tenant_export_cleanup', $definition['id']);
    $this->assertArrayHasKey('cron', $definition);
    $this->assertEquals(30, $definition['cron']['time']);
  }

  /**
   * Tests that expired records are properly marked.
   */
  public function testExpiredRecordsMarking(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('tenant_export_record');

    // Create a completed but expired record.
    $record = $storage->create([
      'tenant_id' => 1,
      'requested_by' => 1,
      'export_type' => 'full',
      'status' => 'completed',
      'progress' => 100,
      'expires_at' => time() - 3600, // Expired 1 hour ago.
      'download_token' => 'expired-token',
    ]);
    $record->save();

    // Verify it's expired.
    $this->assertTrue($record->isExpired());
    $this->assertFalse($record->isDownloadable());
  }

}
