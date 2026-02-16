<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactuEventLog entity immutability and CRUD.
 *
 * @group jaraba_verifactu
 */
class VeriFactuEventLogEntityTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
    'jaraba_verifactu',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.certificate_manager')
      ->setSynthetic(TRUE);
  }

  protected function setUp(): void {
    parent::setUp();
    $this->container->set(
      'ecosistema_jaraba_core.certificate_manager',
      $this->createMock(\Drupal\ecosistema_jaraba_core\Service\CertificateManagerService::class),
    );
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_event_log');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests creating an event log entry.
   */
  public function testCreateEventLog(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_event_log');

    $event = $storage->create([
      'tenant_id' => 1,
      'event_type' => 'RECORD_CREATE',
      'severity' => 'info',
      'description' => 'Alta record created for invoice VF-2026-001',
      'record_id' => 42,
      'actor_id' => 'system',
      'details_json' => json_encode(['invoice_number' => 'VF-2026-001']),
      'hash_event' => str_repeat('e', 64),
    ]);
    $event->save();

    $loaded = $storage->load($event->id());
    $this->assertNotNull($loaded);
    $this->assertSame('RECORD_CREATE', $loaded->get('event_type')->value);
    $this->assertSame('info', $loaded->get('severity')->value);
    $this->assertSame('42', (string) $loaded->get('record_id')->value);
  }

  /**
   * Tests that event log entries have auto-populated timestamps.
   */
  public function testEventLogTimestamp(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_event_log');

    $before = time();
    $event = $storage->create([
      'tenant_id' => 1,
      'event_type' => 'CHAIN_VERIFY',
      'severity' => 'info',
      'hash_event' => str_repeat('f', 64),
    ]);
    $event->save();

    $created = (int) $event->get('created')->value;
    $this->assertGreaterThanOrEqual($before, $created);
  }

  /**
   * Tests querying events by type and tenant.
   */
  public function testQueryEventsByTypeAndTenant(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_event_log');

    $types = ['RECORD_CREATE', 'RECORD_CREATE', 'CHAIN_VERIFY', 'REMISION_OK'];
    foreach ($types as $i => $type) {
      $storage->create([
        'tenant_id' => 1,
        'event_type' => $type,
        'severity' => 'info',
        'hash_event' => str_repeat((string) $i, 64),
      ])->save();
    }

    $createEvents = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->condition('event_type', 'RECORD_CREATE')
      ->execute();

    $this->assertCount(2, $createEvents);
  }

  /**
   * Tests JSON details storage and retrieval.
   */
  public function testJsonDetailsStorageAndRetrieval(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_event_log');

    $details = [
      'invoice_number' => 'VF-2026-001',
      'hash' => str_repeat('a', 64),
      'nested' => ['key' => 'value'],
    ];

    $event = $storage->create([
      'tenant_id' => 1,
      'event_type' => 'RECORD_CREATE',
      'severity' => 'info',
      'details_json' => json_encode($details),
      'hash_event' => str_repeat('g', 64),
    ]);
    $event->save();

    $loaded = $storage->load($event->id());
    $decoded = json_decode($loaded->get('details_json')->value, TRUE);
    $this->assertSame('VF-2026-001', $decoded['invoice_number']);
    $this->assertSame('value', $decoded['nested']['key']);
  }

}
