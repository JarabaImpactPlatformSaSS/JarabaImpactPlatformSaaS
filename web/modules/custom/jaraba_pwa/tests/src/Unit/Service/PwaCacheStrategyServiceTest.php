<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pwa\Unit\Service;

use Drupal\jaraba_pwa\Service\PwaCacheStrategyService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PwaCacheStrategyService.
 *
 * Validates the Workbox-style caching strategy resolution for
 * different URL patterns including static assets, API endpoints,
 * dashboard pages, admin pages, and fallback behavior.
 *
 * @coversDefaultClass \Drupal\jaraba_pwa\Service\PwaCacheStrategyService
 * @group jaraba_pwa
 */
class PwaCacheStrategyServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected PwaCacheStrategyService $service;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new PwaCacheStrategyService($this->logger);
  }

  /**
   * Tests getStrategies returns a non-empty array.
   *
   * @covers ::getStrategies
   */
  public function testGetStrategiesReturnsNonEmptyArray(): void {
    $strategies = $this->service->getStrategies();

    $this->assertIsArray($strategies);
    $this->assertNotEmpty($strategies);
  }

  /**
   * Tests each strategy entry has the required keys.
   *
   * @covers ::getStrategies
   */
  public function testGetStrategiesHasRequiredKeys(): void {
    $strategies = $this->service->getStrategies();

    foreach ($strategies as $index => $strategy) {
      $this->assertArrayHasKey('pattern', $strategy, "Strategy at index $index missing 'pattern' key.");
      $this->assertArrayHasKey('strategy', $strategy, "Strategy at index $index missing 'strategy' key.");
      $this->assertArrayHasKey('maxEntries', $strategy, "Strategy at index $index missing 'maxEntries' key.");
      $this->assertArrayHasKey('maxAge', $strategy, "Strategy at index $index missing 'maxAge' key.");
      $this->assertArrayHasKey('description', $strategy, "Strategy at index $index missing 'description' key.");
    }
  }

  /**
   * Tests that all strategy patterns are valid regex.
   *
   * @covers ::getStrategies
   */
  public function testAllPatternsAreValidRegex(): void {
    $strategies = $this->service->getStrategies();

    foreach ($strategies as $index => $strategy) {
      // preg_match returns FALSE on regex error.
      $result = @preg_match($strategy['pattern'], '');
      $this->assertNotFalse($result, "Strategy at index $index has invalid regex pattern: {$strategy['pattern']}");
    }
  }

  /**
   * Tests CSS files resolve to 'cache-first' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testCssFilesUseCacheFirst(): void {
    $this->assertSame('cache-first', $this->service->getStrategyForRoute('/sites/default/files/css/style.css'));
  }

  /**
   * Tests JavaScript files resolve to 'cache-first' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testJsFilesUseCacheFirst(): void {
    $this->assertSame('cache-first', $this->service->getStrategyForRoute('/core/misc/drupal.js'));
  }

  /**
   * Tests font files resolve to 'cache-first' strategy.
   *
   * @covers ::getStrategyForRoute
   * @dataProvider fontExtensionProvider
   */
  public function testFontFilesUseCacheFirst(string $path): void {
    $this->assertSame('cache-first', $this->service->getStrategyForRoute($path));
  }

  /**
   * Data provider for font file extensions.
   *
   * @return array
   *   Font file paths.
   */
  public static function fontExtensionProvider(): array {
    return [
      'woff2 font' => ['/fonts/roboto.woff2'],
      'woff font' => ['/fonts/roboto.woff'],
      'ttf font' => ['/fonts/roboto.ttf'],
      'eot font' => ['/fonts/roboto.eot'],
    ];
  }

  /**
   * Tests image files resolve to 'cache-first' strategy.
   *
   * @covers ::getStrategyForRoute
   * @dataProvider imageExtensionProvider
   */
  public function testImageFilesUseCacheFirst(string $path): void {
    $this->assertSame('cache-first', $this->service->getStrategyForRoute($path));
  }

  /**
   * Data provider for image file extensions.
   *
   * @return array
   *   Image file paths.
   */
  public static function imageExtensionProvider(): array {
    return [
      'png' => ['/images/logo.png'],
      'jpg' => ['/images/photo.jpg'],
      'jpeg' => ['/images/photo.jpeg'],
      'gif' => ['/images/animation.gif'],
      'webp' => ['/images/hero.webp'],
      'avif' => ['/images/photo.avif'],
      'ico' => ['/favicon.ico'],
    ];
  }

  /**
   * Tests API endpoints resolve to 'network-first' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testApiEndpointsUseNetworkFirst(): void {
    $this->assertSame('network-first', $this->service->getStrategyForRoute('/api/v1/resources'));
  }

  /**
   * Tests nested API paths also resolve to 'network-first'.
   *
   * @covers ::getStrategyForRoute
   */
  public function testNestedApiPathsUseNetworkFirst(): void {
    $this->assertSame('network-first', $this->service->getStrategyForRoute('/api/v1/groups/42/members'));
  }

  /**
   * Tests dashboard pages resolve to 'stale-while-revalidate' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testDashboardPagesUseStaleWhileRevalidate(): void {
    $this->assertSame('stale-while-revalidate', $this->service->getStrategyForRoute('/dashboard'));
  }

  /**
   * Tests dashboard sub-pages also resolve to 'stale-while-revalidate'.
   *
   * @covers ::getStrategyForRoute
   */
  public function testDashboardSubPagesUseStaleWhileRevalidate(): void {
    $this->assertSame('stale-while-revalidate', $this->service->getStrategyForRoute('/dashboard/analytics'));
  }

  /**
   * Tests user pages resolve to 'network-first' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testUserPagesUseNetworkFirst(): void {
    $this->assertSame('network-first', $this->service->getStrategyForRoute('/user/42'));
  }

  /**
   * Tests admin pages resolve to 'network-only' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testAdminPagesUseNetworkOnly(): void {
    $this->assertSame('network-only', $this->service->getStrategyForRoute('/admin/content'));
  }

  /**
   * Tests admin sub-pages also resolve to 'network-only'.
   *
   * @covers ::getStrategyForRoute
   */
  public function testAdminSubPagesUseNetworkOnly(): void {
    $this->assertSame('network-only', $this->service->getStrategyForRoute('/admin/structure/taxonomy'));
  }

  /**
   * Tests home page resolves to 'stale-while-revalidate' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testHomePageUsesStaleWhileRevalidate(): void {
    $this->assertSame('stale-while-revalidate', $this->service->getStrategyForRoute('/'));
  }

  /**
   * Tests unknown routes fall back to 'network-first' strategy.
   *
   * @covers ::getStrategyForRoute
   */
  public function testUnknownRoutesFallBackToNetworkFirst(): void {
    $this->assertSame('network-first', $this->service->getStrategyForRoute('/some/random/page'));
  }

  /**
   * Tests that PWA manifest matches the API endpoint pattern.
   *
   * The manifest endpoint /api/v1/pwa/manifest matches the generic
   * /api/v1/ pattern first in strategy order, resolving to network-first.
   *
   * @covers ::getStrategyForRoute
   */
  public function testPwaManifestMatchesApiPattern(): void {
    $this->assertSame('network-first', $this->service->getStrategyForRoute('/api/v1/pwa/manifest'));
  }

  /**
   * Tests that all strategy names are valid Workbox strategy names.
   *
   * @covers ::getStrategies
   */
  public function testAllStrategyNamesAreValid(): void {
    $validStrategies = [
      'cache-first',
      'network-first',
      'stale-while-revalidate',
      'network-only',
      'cache-only',
    ];

    $strategies = $this->service->getStrategies();

    foreach ($strategies as $index => $config) {
      $this->assertContains(
        $config['strategy'],
        $validStrategies,
        "Strategy at index $index uses invalid strategy name: {$config['strategy']}",
      );
    }
  }

  /**
   * Tests that SVG files match the static assets pattern (CSS/JS group).
   *
   * @covers ::getStrategyForRoute
   */
  public function testSvgFilesUseCacheFirst(): void {
    $this->assertSame('cache-first', $this->service->getStrategyForRoute('/icons/logo.svg'));
  }

}
