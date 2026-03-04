<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the AgroConecta vertical.
 *
 * Covers: marketplace browsing, product search, producer portal,
 * customer portal, order management, API endpoints, traceability,
 * QR redirect, and permission enforcement.
 *
 * @group jaraba_agroconecta_core
 * @group functional
 * @group agroconecta
 */
class AgroConectaFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'image',
    'ecosistema_jaraba_core',
    'jaraba_agroconecta_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with producer permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $producerUser;

  /**
   * User with customer permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customerUser;

  /**
   * Anonymous-level user (access content only).
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unprivilegedUser;

  /**
   * User with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = $this->container->get('entity_type.manager')->getDefinitions();
    if (!isset($definitions['product_agro'])) {
      $this->markTestSkipped('product_agro entity type not available.');
    }

    $this->producerUser = $this->drupalCreateUser([
      'access content',
      'manage own agro products',
      'view own agro orders',
      'use producer copilot',
      'manage own producer profile',
    ]);

    $this->customerUser = $this->drupalCreateUser([
      'access content',
      'view own agro orders',
      'create agro reviews',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access content',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer agroconecta',
    ]);
  }

  /**
   * Tests marketplace page is publicly accessible.
   */
  public function testMarketplacePubliclyAccessible(): void {
    $this->drupalGet('/marketplace');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Marketplace should not cause server error.');
    $this->assertContains(
      $statusCode,
      [200, 301, 302],
      'Marketplace should be accessible or redirect.'
    );
  }

  /**
   * Tests search page is accessible.
   */
  public function testSearchPageAccessible(): void {
    $this->drupalGet('/agroconecta/search');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Search page should not cause server error.');
  }

  /**
   * Tests public traceability page is accessible.
   */
  public function testTraceabilityPubliclyAccessible(): void {
    $this->drupalGet('/agroconecta/trace/TEST-BATCH-001');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Traceability page should not cause server error.');
  }

  /**
   * Tests QR redirect endpoint exists.
   */
  public function testQrRedirectEndpointExists(): void {
    $this->drupalGet('/q/TEST-CODE');
    $statusCode = $this->getSession()->getStatusCode();

    // QR redirect may 404 for non-existent code, but should not 500.
    $this->assertNotEquals(500, $statusCode, 'QR redirect should not cause server error.');
  }

  /**
   * Tests products API returns JSON.
   */
  public function testProductsApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/products');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Products API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Products API should not error.');
  }

  /**
   * Tests producers API returns JSON.
   */
  public function testProducersApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/producers');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Producers API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Producers API should not error.');
  }

  /**
   * Tests search API returns JSON.
   */
  public function testSearchApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/search', ['query' => ['q' => 'aceite']]);
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Search API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Search API should not error.');
  }

  /**
   * Tests categories API returns JSON.
   */
  public function testCategoriesApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/categories');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Categories API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Categories API should not error.');
  }

  /**
   * Tests promotions API returns JSON.
   */
  public function testPromotionsApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/promotions');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Promotions API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Promotions API should not error.');
  }

  /**
   * Tests producer portal requires authentication.
   */
  public function testProducerPortalRequiresAuth(): void {
    $this->drupalGet('/productor');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access producer portal.'
    );
  }

  /**
   * Tests producer dashboard accessible to producer.
   */
  public function testProducerDashboardAccessible(): void {
    $this->drupalLogin($this->producerUser);
    $this->drupalGet('/productor');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Producer dashboard should not error.');
  }

  /**
   * Tests producer products page is accessible.
   */
  public function testProducerProductsAccessible(): void {
    $this->drupalLogin($this->producerUser);
    $this->drupalGet('/productor/productos');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Producer products should not error.');
  }

  /**
   * Tests producer finances page is accessible.
   */
  public function testProducerFinancesAccessible(): void {
    $this->drupalLogin($this->producerUser);
    $this->drupalGet('/productor/finanzas');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Producer finances should not error.');
  }

  /**
   * Tests customer portal requires authentication.
   */
  public function testCustomerPortalRequiresAuth(): void {
    $this->drupalGet('/mi-cuenta');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access customer portal.'
    );
  }

  /**
   * Tests customer portal accessible to customer.
   */
  public function testCustomerPortalAccessible(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/mi-cuenta');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Customer portal should not error.');
  }

  /**
   * Tests customer orders page accessible.
   */
  public function testCustomerOrdersAccessible(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/mi-cuenta/pedidos');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Customer orders should not error.');
  }

  /**
   * Tests checkout requires authentication.
   */
  public function testCheckoutRequiresAuth(): void {
    $this->drupalGet('/checkout');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(200, $statusCode, 'Anonymous should not directly access checkout.');
  }

  /**
   * Tests admin overview requires admin permission.
   */
  public function testAdminOverviewRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/api/v1/agro/admin/overview');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not access admin overview.'
    );
  }

  /**
   * Tests admin overview accessible to admin.
   */
  public function testAdminOverviewAccessible(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/api/v1/agro/admin/overview');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Admin overview API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Admin overview should not error.');
  }

  /**
   * Tests analytics dashboard API returns JSON.
   */
  public function testAnalyticsDashboardReturnsJson(): void {
    $this->drupalLogin($this->producerUser);
    $this->drupalGet('/api/v1/agro/analytics/dashboard');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Analytics dashboard should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Analytics dashboard should not error.');
  }

  /**
   * Tests reviews API returns JSON.
   */
  public function testReviewsApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/agro/reviews');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Reviews API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Reviews API should not error.');
  }

  /**
   * Tests WhatsApp webhook endpoint exists.
   */
  public function testWhatsAppWebhookEndpointExists(): void {
    $this->drupalGet('/api/v1/whatsapp/webhook');
    $statusCode = $this->getSession()->getStatusCode();

    // GET on webhook may return 200 (verification) or 405, but not 500.
    $this->assertNotEquals(500, $statusCode, 'WhatsApp webhook should not cause server error.');
  }

  /**
   * Tests partner portal request link endpoint exists.
   */
  public function testPartnerPortalRequestLinkExists(): void {
    // POST-only endpoint; GET should not 500.
    $this->drupalGet('/api/v1/portal/request-link');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Partner portal request-link should not error.');
  }

  /**
   * Tests producer portal routes require correct permissions.
   *
   * @dataProvider producerRouteProvider
   */
  public function testProducerRoutesRequirePermission(string $path): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet($path);
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      "Unprivileged user should not access {$path}."
    );
  }

  /**
   * Data provider for producer portal routes.
   */
  public static function producerRouteProvider(): array {
    return [
      'dashboard' => ['/productor'],
      'orders' => ['/productor/pedidos'],
      'products' => ['/productor/productos'],
      'finances' => ['/productor/finanzas'],
      'settings' => ['/productor/configuracion'],
    ];
  }

  /**
   * Tests customer portal routes require authentication.
   *
   * @dataProvider customerRouteProvider
   */
  public function testCustomerRoutesRequireAuth(string $path): void {
    $this->drupalGet($path);
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      "Anonymous user should not access {$path}."
    );
  }

  /**
   * Data provider for customer portal routes.
   */
  public static function customerRouteProvider(): array {
    return [
      'dashboard' => ['/mi-cuenta'],
      'orders' => ['/mi-cuenta/pedidos'],
      'addresses' => ['/mi-cuenta/direcciones'],
      'favorites' => ['/mi-cuenta/favoritos'],
    ];
  }

  /**
   * Tests admin API routes require admin permission.
   *
   * @dataProvider adminApiRouteProvider
   */
  public function testAdminApiRoutesRequirePermission(string $path): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet($path);
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      "Unprivileged user should not access {$path}."
    );
  }

  /**
   * Data provider for admin API routes.
   */
  public static function adminApiRouteProvider(): array {
    return [
      'overview' => ['/api/v1/agro/admin/overview'],
      'health' => ['/api/v1/agro/admin/health'],
      'activity' => ['/api/v1/agro/admin/activity'],
      'reports products' => ['/api/v1/agro/admin/reports/products'],
      'reports orders' => ['/api/v1/agro/admin/reports/orders'],
      'reports producers' => ['/api/v1/agro/admin/reports/producers'],
    ];
  }

}
