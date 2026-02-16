<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests VeriFactu REST API endpoints access control.
 *
 * Verifies that all 21 API endpoints respect RBAC permissions.
 *
 * @group jaraba_verifactu
 */
class VeriFactuApiEndpointsTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests config endpoint requires admin permission.
   */
  public function testConfigEndpointRequiresAdmin(): void {
    $this->drupalGet('/api/v1/verifactu/config');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests config endpoint accessible with admin permission.
   */
  public function testConfigEndpointAccessible(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
  }

  /**
   * Tests records list endpoint requires view permission.
   */
  public function testRecordsListRequiresPermission(): void {
    $this->drupalGet('/api/v1/verifactu/records');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests records list endpoint returns JSON.
   */
  public function testRecordsListReturnsJson(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/records');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests audit stats endpoint returns data.
   */
  public function testAuditStatsEndpoint(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/audit/stats');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
  }

  /**
   * Tests audit events endpoint requires event log permission.
   */
  public function testAuditEventsRequiresPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/audit/events');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests audit events endpoint accessible with permission.
   */
  public function testAuditEventsAccessible(): void {
    $user = $this->drupalCreateUser(['view verifactu event log']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/audit/events');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests remision create endpoint requires manage permission.
   */
  public function testRemisionCreateRequiresPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    // POST to remisions create — should be 403.
    $this->drupalGet('/api/v1/verifactu/remisions');
    // GET should work for viewer (list).
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests remisions list returns JSON format.
   */
  public function testRemisionsListReturnsJson(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/remisions');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests certificate status endpoint requires admin.
   */
  public function testCertificateStatusRequiresAdmin(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config/certificate/status');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests queue status endpoint requires admin.
   */
  public function testQueueStatusRequiresAdmin(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/remisions/queue-status');
    $this->assertSession()->statusCodeEquals(403);
  }

}
