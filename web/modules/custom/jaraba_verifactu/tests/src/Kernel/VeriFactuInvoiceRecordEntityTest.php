<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactuInvoiceRecord entity CRUD in database.
 *
 * @group jaraba_verifactu
 */
class VeriFactuInvoiceRecordEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->set(
      'ecosistema_jaraba_core.certificate_manager',
      $this->createMock(\Drupal\ecosistema_jaraba_core\Service\CertificateManagerService::class),
    );
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_invoice_record');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests creating an invoice record entity and verifying all fields in DB.
   */
  public function testCreateAltaRecord(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'nombre_emisor' => 'Test Company SL',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'clave_regimen' => '01',
      'base_imponible' => '1000.00',
      'tipo_impositivo' => '21.00',
      'cuota_tributaria' => '210.00',
      'importe_total' => '1210.00',
      'hash_record' => str_repeat('a', 64),
      'aeat_status' => 'pending',
    ]);
    $record->save();

    $this->assertNotNull($record->id());

    // Reload from database.
    $loaded = $storage->load($record->id());
    $this->assertNotNull($loaded);
    $this->assertSame('alta', $loaded->get('record_type')->value);
    $this->assertSame('B12345678', $loaded->get('nif_emisor')->value);
    $this->assertSame('VF-2026-001', $loaded->get('numero_factura')->value);
    $this->assertSame('1210.00', $loaded->get('importe_total')->value);
    $this->assertSame(str_repeat('a', 64), $loaded->get('hash_record')->value);
    $this->assertSame('pending', $loaded->get('aeat_status')->value);
  }

  /**
   * Tests creating an anulacion record with different type.
   */
  public function testCreateAnulacionRecord(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'anulacion',
      'nif_emisor' => 'B12345678',
      'nombre_emisor' => 'Test Company SL',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'importe_total' => '1210.00',
      'hash_record' => str_repeat('b', 64),
      'aeat_status' => 'pending',
    ]);
    $record->save();

    $loaded = $storage->load($record->id());
    $this->assertSame('anulacion', $loaded->get('record_type')->value);
  }

  /**
   * Tests that created timestamp is auto-populated.
   */
  public function testCreatedTimestampAutoPopulated(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $before = time();
    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-002',
      'hash_record' => str_repeat('c', 64),
    ]);
    $record->save();
    $after = time();

    $created = (int) $record->get('created')->value;
    $this->assertGreaterThanOrEqual($before, $created);
    $this->assertLessThanOrEqual($after, $created);
  }

  /**
   * Tests querying records by tenant and status.
   */
  public function testQueryByTenantAndStatus(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    // Tenant 1: 2 pending, 1 accepted.
    foreach (['pending', 'pending', 'accepted'] as $i => $status) {
      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => 'VF-2026-00' . ($i + 1),
        'hash_record' => str_repeat((string) $i, 64),
        'aeat_status' => $status,
      ])->save();
    }

    // Tenant 2: 1 pending.
    $storage->create([
      'tenant_id' => 2,
      'record_type' => 'alta',
      'nif_emisor' => 'A99999999',
      'numero_factura' => 'VF-2026-100',
      'hash_record' => str_repeat('d', 64),
      'aeat_status' => 'pending',
    ])->save();

    // Query: Tenant 1 pending records.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->condition('aeat_status', 'pending')
      ->execute();

    $this->assertCount(2, $ids);

    // Query: All pending records.
    $allPending = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('aeat_status', 'pending')
      ->execute();

    $this->assertCount(3, $allPending);
  }

}
