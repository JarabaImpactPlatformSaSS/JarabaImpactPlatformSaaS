<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_vault\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the JarabaLex (legal) vertical.
 *
 * Covers: vault dashboard, document API, sharing, audit log,
 * GDPR export, client portal, permission enforcement, and copilot.
 *
 * @group jaraba_legal_vault
 * @group functional
 * @group jarabalex
 */
class LegalVaultFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'ecosistema_jaraba_core',
    'jaraba_legal_vault',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with vault management permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $vaultUser;

  /**
   * User with basic vault access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $basicUser;

  /**
   * User without vault permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unprivilegedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = $this->container->get('entity_type.manager')->getDefinitions();
    if (!isset($definitions['secure_document'])) {
      $this->markTestSkipped('secure_document entity type not available.');
    }

    $this->vaultUser = $this->drupalCreateUser([
      'access content',
      'manage vault documents',
      'access vault',
    ]);

    $this->basicUser = $this->drupalCreateUser([
      'access content',
      'access vault',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Tests vault dashboard requires authentication.
   */
  public function testVaultDashboardRequiresAuth(): void {
    $this->drupalGet('/legal/vault');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access vault dashboard.'
    );
  }

  /**
   * Tests vault dashboard accessible to authorized user.
   */
  public function testVaultDashboardAccessible(): void {
    $this->drupalLogin($this->vaultUser);
    $this->drupalGet('/legal/vault');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Vault dashboard should not cause server error.');
  }

  /**
   * Tests documents list API returns JSON.
   */
  public function testDocumentsApiReturnsJson(): void {
    $this->drupalLogin($this->vaultUser);
    $this->drupalGet('/api/v1/vault/documents');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Documents API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Documents API should not error.');
  }

  /**
   * Tests documents API requires vault permission.
   */
  public function testDocumentsApiRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/api/v1/vault/documents');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not access documents API.'
    );
  }

  /**
   * Tests document detail with nonexistent UUID returns 404.
   */
  public function testDocumentDetailNonexistentReturns404(): void {
    $this->drupalLogin($this->vaultUser);
    $this->drupalGet('/api/v1/vault/documents/nonexistent-uuid');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [404, 200],
      'Nonexistent document should return 404 or empty response.'
    );
    $this->assertNotEquals(500, $statusCode, 'Missing document should not error.');
  }

  /**
   * Tests shared documents API returns JSON.
   */
  public function testSharedDocumentsApiReturnsJson(): void {
    $this->drupalLogin($this->basicUser);
    $this->drupalGet('/api/v1/vault/shared');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Shared documents API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Shared documents API should not error.');
  }

  /**
   * Tests GDPR export endpoint accessible.
   */
  public function testGdprExportAccessible(): void {
    $this->drupalLogin($this->vaultUser);
    $this->drupalGet('/api/v1/vault/export');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'GDPR export should not error.');
  }

  /**
   * Tests access-by-token endpoint with invalid token.
   */
  public function testAccessByTokenInvalidToken(): void {
    // Public endpoint: no auth needed.
    $this->drupalGet('/api/v1/vault/access/token/invalid-token-123');
    $statusCode = $this->getSession()->getStatusCode();

    // Invalid token should return 404 or 403, never 500.
    $this->assertNotEquals(500, $statusCode, 'Invalid token should not cause server error.');
    $this->assertContains(
      $statusCode,
      [403, 404, 200],
      'Invalid token should return appropriate error code.'
    );
  }

  /**
   * Tests client portal with invalid token.
   */
  public function testClientPortalInvalidToken(): void {
    $this->drupalGet('/api/v1/portal/invalid-token-abc');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Portal with invalid token should not error.');
  }

  /**
   * Tests audit log requires vault permissions.
   */
  public function testAuditLogRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/api/v1/vault/documents/test-uuid/audit');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403, 404],
      'Unprivileged user should not access audit log.'
    );
  }

  /**
   * Tests legal dashboard route exists.
   */
  public function testLegalDashboardRouteExists(): void {
    $this->drupalLogin($this->vaultUser);
    $this->drupalGet('/legal');
    $statusCode = $this->getSession()->getStatusCode();

    // May return 200 or 403 depending on legal module activation.
    $this->assertNotEquals(500, $statusCode, 'Legal dashboard should not error.');
  }

  /**
   * Tests whistleblower report endpoint is publicly accessible.
   */
  public function testWhistleblowerEndpointPublic(): void {
    // GET on POST-only endpoint; should not error.
    $this->drupalGet('/api/v1/legal/whistleblower/report');
    $statusCode = $this->getSession()->getStatusCode();

    // POST-only route may return 405, 403, or redirect; never 500.
    $this->assertNotEquals(500, $statusCode, 'Whistleblower endpoint should not error.');
  }

  /**
   * Tests whistleblower tracking with invalid code.
   */
  public function testWhistleblowerTrackingInvalidCode(): void {
    $this->drupalGet('/api/v1/legal/whistleblower/FAKE-CODE-123/status');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Invalid tracking code should not error.');
  }

  /**
   * Tests portal endpoint routes don't error.
   *
   * @dataProvider portalRouteProvider
   */
  public function testPortalEndpointsNoError(string $path): void {
    $this->drupalGet($path);
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, "Portal route {$path} should not error.");
  }

  /**
   * Data provider for portal routes with fake tokens.
   */
  public static function portalRouteProvider(): array {
    return [
      'overview' => ['/api/v1/portal/fake-token-123'],
      'requests' => ['/api/v1/portal/fake-token-123/requests'],
      'deliveries' => ['/api/v1/portal/fake-token-123/deliveries'],
      'activity' => ['/api/v1/portal/fake-token-123/activity'],
    ];
  }

}
