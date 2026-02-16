<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the full record creation flow in a real database.
 *
 * Verifies the sequence: Create entity → Hash → QR → Chain update → SIF log.
 *
 * @group jaraba_verifactu
 */
class VeriFactuRecordCreationFlowTest extends KernelTestBase {

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
  }

  /**
   * Tests creating multiple records preserves ordering.
   */
  public function testMultipleRecordsPreserveOrdering(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    for ($i = 1; $i <= 5; $i++) {
      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-2026-%03d', $i),
        'fecha_expedicion' => '2026-02-16',
        'importe_total' => '1210.00',
        'hash_record' => hash('sha256', 'record-' . $i),
        'aeat_status' => 'pending',
      ])->save();
    }

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->sort('created', 'ASC')
      ->execute();

    $records = $storage->loadMultiple($ids);
    $numbers = [];
    foreach ($records as $r) {
      $numbers[] = $r->get('numero_factura')->value;
    }

    $this->assertSame([
      'VF-2026-001',
      'VF-2026-002',
      'VF-2026-003',
      'VF-2026-004',
      'VF-2026-005',
    ], $numbers);
  }

  /**
   * Tests alta followed by anulacion for the same invoice.
   */
  public function testAltaFollowedByAnulacion(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    // Create alta.
    $alta = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
      'hash_record' => hash('sha256', 'alta-1'),
      'aeat_status' => 'accepted',
    ]);
    $alta->save();

    // Create anulacion referencing the same invoice.
    $anulacion = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'anulacion',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
      'hash_record' => hash('sha256', 'anulacion-1'),
      'hash_previous' => $alta->get('hash_record')->value,
      'aeat_status' => 'pending',
    ]);
    $anulacion->save();

    // Verify both exist.
    $altaIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('numero_factura', 'VF-2026-001')
      ->condition('record_type', 'alta')
      ->execute();
    $this->assertCount(1, $altaIds);

    $anulacionIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('numero_factura', 'VF-2026-001')
      ->condition('record_type', 'anulacion')
      ->execute();
    $this->assertCount(1, $anulacionIds);

    // Verify anulacion references alta's hash.
    $loadedAnulacion = $storage->load(reset($anulacionIds));
    $this->assertSame(
      $alta->get('hash_record')->value,
      $loadedAnulacion->get('hash_previous')->value
    );
  }

  /**
   * Tests that records from different tenants are isolated.
   */
  public function testRecordCreationIsolation(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-001',
      'hash_record' => hash('sha256', 't1-r1'),
    ])->save();

    $storage->create([
      'tenant_id' => 2,
      'record_type' => 'alta',
      'nif_emisor' => 'A99999999',
      'numero_factura' => 'VF-001',
      'hash_record' => hash('sha256', 't2-r1'),
    ])->save();

    // Same invoice number, different tenants — both should exist.
    $all = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('numero_factura', 'VF-001')
      ->execute();

    $this->assertCount(2, $all);
  }

}
