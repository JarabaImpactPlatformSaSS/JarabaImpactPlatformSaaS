<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactuRemisionBatch entity CRUD.
 *
 * @group jaraba_verifactu
 */
class VeriFactuRemisionBatchEntityTest extends KernelTestBase {

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
    $this->installEntitySchema('verifactu_remision_batch');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests creating a remision batch entity.
   */
  public function testCreateRemisionBatch(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_remision_batch');

    $batch = $storage->create([
      'tenant_id' => 1,
      'status' => 'queued',
      'total_records' => 50,
      'accepted_records' => 0,
      'rejected_records' => 0,
      'aeat_environment' => 'testing',
      'retry_count' => 0,
    ]);
    $batch->save();

    $loaded = $storage->load($batch->id());
    $this->assertNotNull($loaded);
    $this->assertSame('queued', $loaded->get('status')->value);
    $this->assertSame('50', (string) $loaded->get('total_records')->value);
    $this->assertSame('testing', $loaded->get('aeat_environment')->value);
  }

  /**
   * Tests batch status transitions.
   */
  public function testBatchStatusTransitions(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_remision_batch');

    $batch = $storage->create([
      'tenant_id' => 1,
      'status' => 'queued',
      'total_records' => 10,
      'aeat_environment' => 'testing',
    ]);
    $batch->save();

    // Transition to sending.
    $batch->set('status', 'sending');
    $batch->save();
    $loaded = $storage->load($batch->id());
    $this->assertSame('sending', $loaded->get('status')->value);

    // Transition to sent.
    $batch->set('status', 'sent');
    $batch->set('accepted_records', 8);
    $batch->set('rejected_records', 2);
    $batch->set('aeat_csv', 'CSV123456');
    $batch->save();

    $loaded = $storage->load($batch->id());
    $this->assertSame('sent', $loaded->get('status')->value);
    $this->assertSame('8', (string) $loaded->get('accepted_records')->value);
    $this->assertSame('CSV123456', $loaded->get('aeat_csv')->value);
  }

  /**
   * Tests querying batches by status.
   */
  public function testQueryBatchesByStatus(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_remision_batch');

    $statuses = ['queued', 'queued', 'sent', 'failed'];
    foreach ($statuses as $i => $status) {
      $storage->create([
        'tenant_id' => 1,
        'status' => $status,
        'total_records' => 10,
        'aeat_environment' => 'testing',
      ])->save();
    }

    $queued = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'queued')
      ->execute();

    $this->assertCount(2, $queued);
  }

}
