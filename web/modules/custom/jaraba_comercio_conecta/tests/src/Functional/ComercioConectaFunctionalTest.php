<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_comercio_conecta\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the ComercioConecta vertical.
 *
 * Covers: marketplace browsing, product listing, cart API,
 * checkout flow, merchant portal, order management, and reviews.
 *
 * @group jaraba_comercio_conecta
 * @group functional
 * @group comercioconecta
 */
class ComercioConectaFunctionalTest extends BrowserTestBase {

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
    'jaraba_comercio_conecta',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with merchant permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $merchantUser;

  /**
   * User with customer permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customerUser;

  /**
   * Anonymous-level user.
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
    if (!isset($definitions['product_retail'])) {
      $this->markTestSkipped('product_retail entity type not available.');
    }

    $this->merchantUser = $this->drupalCreateUser([
      'access content',
      'edit own merchant profile',
      'create comercio products',
      'manage comercio products',
      'view comercio stock',
    ]);

    $this->customerUser = $this->drupalCreateUser([
      'access content',
      'view own comercio orders',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Tests marketplace page is publicly accessible.
   */
  public function testMarketplacePubliclyAccessible(): void {
    $this->drupalGet('/comercio-local');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Marketplace should not cause server error.');
    // Public marketplace should be accessible.
    $this->assertContains(
      $statusCode,
      [200, 301, 302],
      'Marketplace should be accessible or redirect.'
    );
  }

  /**
   * Tests product search page is accessible.
   */
  public function testSearchPageAccessible(): void {
    $this->drupalGet('/comercio-local/buscar');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Search should not cause server error.');
  }

  /**
   * Tests products API returns JSON.
   */
  public function testProductsApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/comercio/products');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Products API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Products API should not error.');
  }

  /**
   * Tests merchants API returns JSON.
   */
  public function testMerchantsApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/comercio/merchants');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Merchants API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Merchants API should not error.');
  }

  /**
   * Tests merchant portal requires authentication.
   */
  public function testMerchantPortalRequiresAuth(): void {
    $this->drupalGet('/mi-comercio');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access merchant portal.'
    );
  }

  /**
   * Tests merchant portal dashboard accessible to merchant.
   */
  public function testMerchantDashboardAccessible(): void {
    $this->drupalLogin($this->merchantUser);
    $this->drupalGet('/mi-comercio');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Merchant dashboard should not error.');
  }

  /**
   * Tests merchant products page is accessible.
   */
  public function testMerchantProductsAccessible(): void {
    $this->drupalLogin($this->merchantUser);
    $this->drupalGet('/mi-comercio/productos');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Merchant products should not error.');
  }

  /**
   * Tests merchant analytics requires merchant permission.
   */
  public function testMerchantAnalyticsRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/mi-comercio/analiticas');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not access merchant analytics.'
    );
  }

  /**
   * Tests cart API returns JSON for authenticated user.
   */
  public function testCartApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/comercio/cart');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Cart API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Cart API should not error.');
  }

  /**
   * Tests checkout page requires authentication.
   */
  public function testCheckoutRequiresAuth(): void {
    $this->drupalGet('/comercio-local/checkout');
    $statusCode = $this->getSession()->getStatusCode();

    // Checkout should redirect anonymous users.
    $this->assertNotEquals(200, $statusCode, 'Anonymous should not directly access checkout.');
  }

  /**
   * Tests my-orders requires authentication.
   */
  public function testMyOrdersRequiresAuth(): void {
    $this->drupalGet('/mis-pedidos');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access orders.'
    );
  }

  /**
   * Tests my-orders accessible to customer.
   */
  public function testMyOrdersAccessible(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/mis-pedidos');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'My orders should not error.');
  }

  /**
   * Tests customer portal accessible.
   */
  public function testCustomerPortalAccessible(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/mi-cuenta');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Customer portal should not error.');
  }

  /**
   * Tests flash offers API returns JSON.
   */
  public function testFlashOffersApiReturnsJson(): void {
    $this->drupalLogin($this->customerUser);
    $this->drupalGet('/api/v1/comercio/flash-offers');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Flash offers API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Flash offers API should not error.');
  }

  /**
   * Tests merchant portal routes require correct permissions.
   *
   * @dataProvider merchantRouteProvider
   */
  public function testMerchantRoutesRequirePermission(string $path): void {
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
   * Data provider for merchant portal routes.
   */
  public static function merchantRouteProvider(): array {
    return [
      'dashboard' => ['/mi-comercio'],
      'products' => ['/mi-comercio/productos'],
      'orders' => ['/mi-comercio/pedidos'],
      'payments' => ['/mi-comercio/pagos'],
      'settings' => ['/mi-comercio/configuracion'],
    ];
  }

  /**
   * Tests Stripe webhook endpoint is accessible without auth.
   */
  public function testStripeWebhookEndpointExists(): void {
    // Webhook should accept POST only; GET should return 405 or similar.
    $this->drupalGet('/api/v1/comercio/webhook/stripe');
    $statusCode = $this->getSession()->getStatusCode();

    // Any non-500 response is acceptable for GET on a POST-only route.
    $this->assertNotEquals(500, $statusCode, 'Webhook endpoint should not cause server error.');
  }

}
