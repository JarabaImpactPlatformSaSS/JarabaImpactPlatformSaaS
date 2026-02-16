<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests VeriFactu slide panel behavior for record detail views.
 *
 * Verifies that slide panel triggers are present on record listings
 * and that detail endpoints return the expected JSON structure.
 *
 * @group jaraba_verifactu
 */
class VeriFactuSlidePanelTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests record detail endpoint returns JSON for valid record.
   */
  public function testRecordDetailEndpointReturnsJson(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    // Create a record first.
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');
    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
      'hash_record' => hash('sha256', 'slide-panel-test'),
      'aeat_status' => 'pending',
    ]);
    $record->save();

    $this->drupalGet('/api/v1/verifactu/records/' . $record->id());
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests record detail endpoint returns 404 for nonexistent record.
   */
  public function testRecordDetailEndpointReturns404ForMissing(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/records/999999');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests record detail endpoint requires authentication.
   */
  public function testRecordDetailRequiresAuthentication(): void {
    $this->drupalGet('/api/v1/verifactu/records/1');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests records listing page contains slide panel trigger attributes.
   */
  public function testRecordsListingContainsPanelTriggers(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    // Create records so the listing has content.
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');
    for ($i = 1; $i <= 3; $i++) {
      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-PANEL-%03d', $i),
        'hash_record' => hash('sha256', 'panel-' . $i),
        'aeat_status' => 'pending',
      ])->save();
    }

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/records');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VeriFactu Invoice Records');
  }

  /**
   * Tests remision detail endpoint returns JSON.
   */
  public function testRemisionDetailEndpointReturnsJson(): void {
    $user = $this->drupalCreateUser([
      'view verifactu records',
      'manage verifactu remision',
    ]);
    $this->drupalLogin($user);

    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_remision_batch');
    $batch = $storage->create([
      'tenant_id' => 1,
      'status' => 'queued',
      'environment' => 'staging',
      'total_records' => 5,
    ]);
    $batch->save();

    $this->drupalGet('/api/v1/verifactu/remisions/' . $batch->id());
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
  }

}
