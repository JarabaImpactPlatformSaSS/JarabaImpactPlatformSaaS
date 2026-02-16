<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the TenantExportWorker queue worker.
 *
 * @group jaraba_tenant_export
 */
class TenantExportWorkerTest extends KernelTestBase {

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
   * Tests that queue worker plugin is discoverable.
   */
  public function testWorkerPluginExists(): void {
    $queueManager = $this->container->get('plugin.manager.queue_worker');
    $definitions = $queueManager->getDefinitions();

    $this->assertArrayHasKey('jaraba_tenant_export', $definitions);
    $this->assertArrayHasKey('jaraba_tenant_export_cleanup', $definitions);
  }

  /**
   * Tests queue item creation and retrieval.
   */
  public function testQueueItemCreation(): void {
    $queue = $this->container->get('queue')->get('jaraba_tenant_export');
    $queue->createQueue();

    $queue->createItem([
      'record_id' => 1,
      'group_id' => 42,
      'tenant_entity_id' => 10,
      'sections' => ['core', 'analytics'],
      'attempt' => 0,
    ]);

    $this->assertEquals(1, $queue->numberOfItems());

    $item = $queue->claimItem();
    $this->assertNotFalse($item);
    $this->assertEquals(1, $item->data['record_id']);
    $this->assertEquals(42, $item->data['group_id']);
    $this->assertEquals(0, $item->data['attempt']);
  }

}
