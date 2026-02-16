<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests hash chain integrity verification in a real database.
 *
 * @group jaraba_verifactu
 */
class VeriFactuHashChainIntegrityTest extends KernelTestBase {

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
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests chain of 10 records where each hash depends on the previous.
   */
  public function testChainOf10Records(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $previousHash = '';
    $records = [];

    for ($i = 1; $i <= 10; $i++) {
      $fields = [
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-2026-%03d', $i),
        'fecha_expedicion' => '2026-02-16',
        'tipo_factura' => 'F1',
        'cuota_tributaria' => '210.00',
        'importe_total' => '1210.00',
      ];

      // Compute hash using the same algorithm as VeriFactuHashService.
      $hashInput = implode(',', [
        $fields['nif_emisor'],
        $fields['numero_factura'],
        $fields['fecha_expedicion'],
        $fields['tipo_factura'],
        $fields['cuota_tributaria'],
        $fields['importe_total'],
        'alta',
        $previousHash,
      ]);
      $hash = hash('sha256', $hashInput);

      $record = $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => $fields['nif_emisor'],
        'numero_factura' => $fields['numero_factura'],
        'fecha_expedicion' => $fields['fecha_expedicion'],
        'tipo_factura' => $fields['tipo_factura'],
        'cuota_tributaria' => $fields['cuota_tributaria'],
        'importe_total' => $fields['importe_total'],
        'hash_record' => $hash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'pending',
      ]);
      $record->save();
      $records[] = $record;

      $previousHash = $hash;
    }

    $this->assertCount(10, $records);

    // Verify chain: each record's hash_previous matches the previous hash_record.
    $loadedIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->sort('created', 'ASC')
      ->execute();

    $loaded = $storage->loadMultiple($loadedIds);
    $prevHash = '';
    foreach ($loaded as $r) {
      $this->assertSame($prevHash, $r->get('hash_previous')->value ?? '');
      $prevHash = $r->get('hash_record')->value;
    }
  }

  /**
   * Tests chain break detection when a hash is tampered.
   */
  public function testChainBreakDetection(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $previousHash = '';

    // Create 5 records.
    for ($i = 1; $i <= 5; $i++) {
      $hashInput = implode(',', [
        'B12345678',
        sprintf('VF-2026-%03d', $i),
        '2026-02-16',
        'F1',
        '210.00',
        '1210.00',
        'alta',
        $previousHash,
      ]);
      $hash = hash('sha256', $hashInput);

      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-2026-%03d', $i),
        'fecha_expedicion' => '2026-02-16',
        'tipo_factura' => 'F1',
        'cuota_tributaria' => '210.00',
        'importe_total' => '1210.00',
        'hash_record' => $hash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'pending',
      ])->save();

      $previousHash = $hash;
    }

    // Tamper with record #3's hash.
    $allIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'ASC')
      ->execute();

    $all = $storage->loadMultiple($allIds);
    $records = array_values($all);

    // Record at index 2 (third record): change hash_record.
    $tamperedRecord = $records[2];
    $tamperedRecord->set('hash_record', str_repeat('X', 64));
    $tamperedRecord->save();

    // Now verify: record #4 should have hash_previous that doesn't match
    // record #3's tampered hash_record.
    $reloaded = array_values($storage->loadMultiple(
      $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('created', 'ASC')
        ->execute()
    ));

    // The 4th record (index 3) should have hash_previous matching
    // the ORIGINAL hash of record 3, not the tampered one.
    $record4PrevHash = $reloaded[3]->get('hash_previous')->value;
    $record3CurrentHash = $reloaded[2]->get('hash_record')->value;

    // This should be a break — previous doesn't match current.
    $this->assertNotSame($record3CurrentHash, $record4PrevHash);
  }

  /**
   * Tests multi-tenant isolation of hash chains.
   */
  public function testMultiTenantIsolation(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    // Tenant 1: 3 records.
    for ($i = 1; $i <= 3; $i++) {
      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => 'T1-VF-' . $i,
        'hash_record' => hash('sha256', 'tenant1-' . $i),
        'aeat_status' => 'pending',
      ])->save();
    }

    // Tenant 2: 2 records.
    for ($i = 1; $i <= 2; $i++) {
      $storage->create([
        'tenant_id' => 2,
        'record_type' => 'alta',
        'nif_emisor' => 'A99999999',
        'numero_factura' => 'T2-VF-' . $i,
        'hash_record' => hash('sha256', 'tenant2-' . $i),
        'aeat_status' => 'pending',
      ])->save();
    }

    $tenant1Records = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->execute();

    $tenant2Records = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 2)
      ->execute();

    $this->assertCount(3, $tenant1Records);
    $this->assertCount(2, $tenant2Records);

    // No overlap.
    $this->assertEmpty(array_intersect($tenant1Records, $tenant2Records));
  }

}
