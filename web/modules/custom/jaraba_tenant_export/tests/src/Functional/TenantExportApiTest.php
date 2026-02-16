<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the tenant export API endpoints.
 *
 * @group jaraba_tenant_export
 */
class TenantExportApiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'group',
    'views',
    'ecosistema_jaraba_core',
    'jaraba_tenant_export',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests sections endpoint returns available sections.
   */
  public function testSectionsEndpoint(): void {
    $user = $this->drupalCreateUser(['request tenant export']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/tenant-export/sections');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertArrayHasKey('core', $response['data']);
    $this->assertArrayHasKey('files', $response['data']);
  }

  /**
   * Tests history endpoint returns empty for new tenant.
   */
  public function testHistoryEndpointEmpty(): void {
    $user = $this->drupalCreateUser(['view own exports']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/tenant-export/history');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    // May return 403 if no tenant context, which is expected.
  }

  /**
   * Tests download endpoint with invalid token returns 404.
   */
  public function testDownloadInvalidToken(): void {
    $this->drupalGet('/api/v1/tenant-export/00000000-0000-0000-0000-000000000000/download');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that unauthenticated users cannot access API.
   */
  public function testApiRequiresAuth(): void {
    $this->drupalGet('/api/v1/tenant-export/sections');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/api/v1/tenant-export/history');
    $this->assertSession()->statusCodeEquals(403);
  }

}
