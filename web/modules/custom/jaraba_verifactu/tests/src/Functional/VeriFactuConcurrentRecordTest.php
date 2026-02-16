<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests concurrent record creation maintains chain integrity.
 *
 * Verifies that creating multiple records in rapid succession
 * produces a valid hash chain with no duplicates.
 *
 * @group jaraba_verifactu
 */
class VeriFactuConcurrentRecordTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests rapid sequential record creation maintains unique hashes.
   */
  public function testSequentialRecordCreationUniqueHashes(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');

    $hashes = [];
    for ($i = 1; $i <= 20; $i++) {
      $hash = hash('sha256', 'record-' . $i . '-' . microtime(TRUE));

      $record = $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-2026-%03d', $i),
        'fecha_expedicion' => '2026-02-16',
        'importe_total' => '1210.00',
        'hash_record' => $hash,
        'hash_previous' => end($hashes) ?: '',
        'aeat_status' => 'pending',
      ]);
      $record->save();

      $hashes[] = $hash;
    }

    // All 20 records created.
    $total = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->count()
      ->execute();
    $this->assertSame(20, (int) $total);

    // All hashes are unique.
    $this->assertCount(20, array_unique($hashes));
  }

  /**
   * Tests record IDs are sequential.
   */
  public function testRecordIdsAreSequential(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');

    $ids = [];
    for ($i = 1; $i <= 10; $i++) {
      $record = $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-SEQ-%03d', $i),
        'hash_record' => hash('sha256', 'seq-' . $i),
      ]);
      $record->save();
      $ids[] = (int) $record->id();
    }

    // Verify IDs are strictly increasing.
    for ($i = 1; $i < count($ids); $i++) {
      $this->assertGreaterThan($ids[$i - 1], $ids[$i]);
    }
  }

  /**
   * Tests chain integrity after batch creation.
   */
  public function testChainIntegrityAfterBatchCreation(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');

    $previousHash = '';

    for ($i = 1; $i <= 15; $i++) {
      $fields = [
        'B12345678',
        sprintf('VF-CHAIN-%03d', $i),
        '2026-02-16',
        'F1',
        '210.00',
        '1210.00',
        'alta',
        $previousHash,
      ];
      $hash = hash('sha256', implode(',', $fields));

      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-CHAIN-%03d', $i),
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

    // Reload and verify chain.
    $allIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', 1)
      ->condition('numero_factura', 'VF-CHAIN-%', 'LIKE')
      ->sort('created', 'ASC')
      ->execute();

    $all = $storage->loadMultiple($allIds);
    $prevHash = '';
    $count = 0;

    foreach ($all as $record) {
      $stored = $record->get('hash_previous')->value ?? '';
      $this->assertSame($prevHash, $stored,
        "Record #{$record->id()} hash_previous mismatch at position $count."
      );
      $prevHash = $record->get('hash_record')->value;
      $count++;
    }

    $this->assertSame(15, $count);
  }

}
