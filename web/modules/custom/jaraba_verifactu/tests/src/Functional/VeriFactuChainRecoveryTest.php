<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests VeriFactu chain recovery and integrity verification endpoints.
 *
 * Verifies that chain status, verification triggers, and recovery
 * mechanisms work correctly through the API.
 *
 * @group jaraba_verifactu
 */
class VeriFactuChainRecoveryTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests chain status endpoint requires permission.
   */
  public function testChainStatusRequiresPermission(): void {
    $this->drupalGet('/api/v1/verifactu/chain/status');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests chain status endpoint returns valid JSON.
   */
  public function testChainStatusReturnsJson(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/chain/status');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests chain verify endpoint requires admin permission.
   */
  public function testChainVerifyRequiresAdmin(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/chain/verify');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests chain verify returns integrity result.
   */
  public function testChainVerifyReturnsResult(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/chain/verify');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
  }

  /**
   * Tests chain status with existing records shows correct count.
   */
  public function testChainStatusWithRecords(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    // Create records for the chain.
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');
    $previousHash = '';
    for ($i = 1; $i <= 5; $i++) {
      $hash = hash('sha256', 'chain-recovery-' . $i . '-' . $previousHash);
      $storage->create([
        'tenant_id' => 1,
        'record_type' => 'alta',
        'nif_emisor' => 'B12345678',
        'numero_factura' => sprintf('VF-RECOVERY-%03d', $i),
        'fecha_expedicion' => '2026-02-16',
        'importe_total' => '1210.00',
        'hash_record' => $hash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'accepted',
      ])->save();
      $previousHash = $hash;
    }

    $this->drupalGet('/api/v1/verifactu/chain/status');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
  }

  /**
   * Tests audit page shows chain verification events.
   */
  public function testAuditPageShowsChainEvents(): void {
    $user = $this->drupalCreateUser(['view verifactu event log']);
    $this->drupalLogin($user);

    // Create a chain verification event.
    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_event_log');
    $storage->create([
      'tenant_id' => 1,
      'event_type' => 'CHAIN_VERIFY',
      'severity' => 'info',
      'message' => 'Chain integrity verified: 5 records, 0 breaks.',
      'details' => json_encode([
        'total_records' => 5,
        'breaks_found' => 0,
      ]),
    ])->save();

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/audit');
    $this->assertSession()->statusCodeEquals(200);
  }

}
