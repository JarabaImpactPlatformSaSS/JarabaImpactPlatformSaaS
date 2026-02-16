<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests AEAT connection status and certificate management endpoints.
 *
 * Verifies that connection test, certificate upload, and status
 * endpoints respond correctly based on permissions and configuration.
 *
 * @group jaraba_verifactu
 */
class VeriFactuAeatConnectionTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests connection test endpoint requires admin permission.
   */
  public function testConnectionTestRequiresAdmin(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config/connection-test');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests connection test endpoint accessible with admin permission.
   */
  public function testConnectionTestAccessibleForAdmin(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config/connection-test');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
  }

  /**
   * Tests certificate status endpoint returns expected structure.
   */
  public function testCertificateStatusReturnsStructure(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config/certificate/status');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests environment toggle endpoint requires admin.
   */
  public function testEnvironmentToggleRequiresAdmin(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config/environment');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests config endpoint returns environment info.
   */
  public function testConfigEndpointReturnsEnvironment(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/config');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
  }

  /**
   * Tests queue status endpoint returns expected fields.
   */
  public function testQueueStatusReturnsFields(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/remisions/queue-status');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertArrayHasKey('data', $response);
  }

}
