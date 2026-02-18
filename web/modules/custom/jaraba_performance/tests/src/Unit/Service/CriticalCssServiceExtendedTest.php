<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_performance\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jaraba_performance\Service\CriticalCssService;
use Drupal\Tests\UnitTestCase;

/**
 * Extended unit tests for CriticalCssService.
 *
 * Covers route mapping logic, fallback behavior, admin route detection,
 * disabled states, and the getMappedRoutes() introspection method.
 *
 * @coversDefaultClass \Drupal\jaraba_performance\Service\CriticalCssService
 * @group jaraba_performance
 */
class CriticalCssServiceExtendedTest extends UnitTestCase {

  /**
   * Creates a CriticalCssService with given route name and config value.
   *
   * @param string|null $routeName
   *   The route name to simulate. NULL simulates no matched route.
   * @param bool|null $enabledConfig
   *   The critical_css_enabled config value, or NULL for not set.
   *
   * @return \Drupal\jaraba_performance\Service\CriticalCssService
   *   A configured service instance.
   */
  protected function createService(?string $routeName = '<front>', ?bool $enabledConfig = NULL): CriticalCssService {
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteName')->willReturn($routeName);

    $themeList = $this->createMock(ThemeExtensionList::class);
    $themeList->method('getPath')
      ->with('ecosistema_jaraba_theme')
      ->willReturn('/app/web/themes/custom/ecosistema_jaraba_theme');

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('critical_css_enabled')
      ->willReturn($enabledConfig);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_performance.settings')
      ->willReturn($config);

    return new CriticalCssService(
      $routeMatch,
      $themeList,
      $configFactory,
    );
  }

  /**
   * Tests isEnabled returns FALSE when explicitly disabled.
   *
   * @covers ::isEnabled
   */
  public function testIsEnabledReturnsFalseWhenExplicitlyDisabled(): void {
    $service = $this->createService('<front>', FALSE);

    $this->assertFalse($service->isEnabled());
  }

  /**
   * Tests isEnabled returns TRUE when explicitly enabled.
   *
   * @covers ::isEnabled
   */
  public function testIsEnabledReturnsTrueWhenExplicitlyEnabled(): void {
    $service = $this->createService('<front>', TRUE);

    $this->assertTrue($service->isEnabled());
  }

  /**
   * Tests isEnabled defaults to TRUE when config is not set (NULL).
   *
   * @covers ::isEnabled
   */
  public function testIsEnabledDefaultsTrueWhenConfigIsNull(): void {
    $service = $this->createService('<front>', NULL);

    $this->assertTrue($service->isEnabled());
  }

  /**
   * Tests getCriticalCssFile returns 'templates' for page builder routes.
   *
   * @covers ::getCriticalCssFile
   * @dataProvider pageBuilderRouteProvider
   */
  public function testGetCriticalCssFileForPageBuilderRoutes(string $routeName): void {
    $service = $this->createService($routeName);

    $this->assertSame('templates', $service->getCriticalCssFile());
  }

  /**
   * Data provider for page builder routes.
   *
   * @return array
   *   Route names that map to 'templates'.
   */
  public static function pageBuilderRouteProvider(): array {
    return [
      'templates route' => ['jaraba_page_builder.templates'],
      'template preview route' => ['jaraba_page_builder.template_preview'],
    ];
  }

  /**
   * Tests getCriticalCssFile returns 'admin-pages' for site builder routes.
   *
   * @covers ::getCriticalCssFile
   * @dataProvider siteBuilderRouteProvider
   */
  public function testGetCriticalCssFileForSiteBuilderRoutes(string $routeName): void {
    $service = $this->createService($routeName);

    $this->assertSame('admin-pages', $service->getCriticalCssFile());
  }

  /**
   * Data provider for site builder routes.
   *
   * @return array
   *   Route names that map to 'admin-pages'.
   */
  public static function siteBuilderRouteProvider(): array {
    return [
      'pages route' => ['jaraba_site_builder.pages'],
      'homepage route' => ['jaraba_site_builder.homepage'],
    ];
  }

  /**
   * Tests getCriticalCssFile returns 'landing-empleo' for vertical landings.
   *
   * @covers ::getCriticalCssFile
   * @dataProvider verticalLandingRouteProvider
   */
  public function testGetCriticalCssFileForVerticalLandings(string $routeName): void {
    $service = $this->createService($routeName);

    $this->assertSame('landing-empleo', $service->getCriticalCssFile());
  }

  /**
   * Data provider for vertical landing routes.
   *
   * @return array
   *   Route names that map to 'landing-empleo'.
   */
  public static function verticalLandingRouteProvider(): array {
    return [
      'empleo' => ['jaraba_landing.empleo'],
      'talento' => ['jaraba_landing.talento'],
      'emprender' => ['jaraba_landing.emprender'],
      'comercio' => ['jaraba_landing.comercio'],
      'instituciones' => ['jaraba_landing.instituciones'],
    ];
  }

  /**
   * Tests getCriticalCssFile falls back to 'admin-pages' for jaraba_ admin routes.
   *
   * Routes starting with 'jaraba_' and containing 'admin' should match
   * the generic admin pattern even if not explicitly mapped.
   *
   * @covers ::getCriticalCssFile
   */
  public function testGetCriticalCssFileFallsBackToAdminPagesForGenericAdminRoutes(): void {
    $service = $this->createService('jaraba_billing.admin.settings');

    $this->assertSame('admin-pages', $service->getCriticalCssFile());
  }

  /**
   * Tests getCriticalCssFile falls back to 'homepage' for unknown public routes.
   *
   * Routes that don't match any mapped pattern and are not admin routes
   * should fall back to the 'homepage' CSS.
   *
   * @covers ::getCriticalCssFile
   */
  public function testGetCriticalCssFileFallsBackToHomepageForUnknownRoutes(): void {
    $service = $this->createService('entity.node.canonical');

    $this->assertSame('homepage', $service->getCriticalCssFile());
  }

  /**
   * Tests getCriticalCssPath constructs correct absolute path.
   *
   * @covers ::getCriticalCssPath
   */
  public function testGetCriticalCssPathConstructsCorrectPath(): void {
    $service = $this->createService('<front>');

    $path = $service->getCriticalCssPath('homepage');

    $this->assertSame(
      '/app/web/themes/custom/ecosistema_jaraba_theme/css/critical/homepage.css',
      $path,
    );
  }

  /**
   * Tests getCriticalCssPath works for different filenames.
   *
   * @covers ::getCriticalCssPath
   */
  public function testGetCriticalCssPathWithVariousFilenames(): void {
    $service = $this->createService('<front>');

    $this->assertStringEndsWith('/admin-pages.css', $service->getCriticalCssPath('admin-pages'));
    $this->assertStringEndsWith('/templates.css', $service->getCriticalCssPath('templates'));
    $this->assertStringEndsWith('/landing-empleo.css', $service->getCriticalCssPath('landing-empleo'));
  }

  /**
   * Tests getCriticalCssContent returns NULL when service is disabled.
   *
   * @covers ::getCriticalCssContent
   */
  public function testGetCriticalCssContentReturnsNullWhenDisabled(): void {
    $service = $this->createService('<front>', FALSE);

    $result = $service->getCriticalCssContent();

    $this->assertNull($result, 'getCriticalCssContent should return NULL when CSS is disabled.');
  }

  /**
   * Tests getMappedRoutes returns the complete route-to-file mapping.
   *
   * @covers ::getMappedRoutes
   */
  public function testGetMappedRoutesReturnsCompleteMap(): void {
    $service = $this->createService('<front>');

    $routes = $service->getMappedRoutes();

    $this->assertIsArray($routes);
    $this->assertNotEmpty($routes);
    $this->assertArrayHasKey('<front>', $routes);
    $this->assertSame('homepage', $routes['<front>']);
    $this->assertArrayHasKey('jaraba_page_builder.templates', $routes);
    $this->assertSame('templates', $routes['jaraba_page_builder.templates']);
  }

  /**
   * Tests getMappedRoutes contains all expected route entries.
   *
   * @covers ::getMappedRoutes
   */
  public function testGetMappedRoutesContainsAllExpectedEntries(): void {
    $service = $this->createService('<front>');

    $routes = $service->getMappedRoutes();

    // Should contain exactly 10 mapped routes.
    $this->assertCount(10, $routes);

    // Verify all unique CSS files are present in the values.
    $uniqueFiles = array_unique(array_values($routes));
    sort($uniqueFiles);
    $this->assertSame(['admin-pages', 'homepage', 'landing-empleo', 'templates'], $uniqueFiles);
  }

  /**
   * Tests hasCriticalCss returns FALSE when file does not exist on disk.
   *
   * In a test environment, the CSS files won't exist at the mocked path.
   *
   * @covers ::hasCriticalCss
   */
  public function testHasCriticalCssReturnsFalseForNonExistentFile(): void {
    $service = $this->createService('<front>');

    $this->assertFalse($service->hasCriticalCss());
  }

  /**
   * Tests that a null route name falls back to homepage.
   *
   * @covers ::getCriticalCssFile
   */
  public function testNullRouteNameFallsBackToHomepage(): void {
    $service = $this->createService(NULL);

    $file = $service->getCriticalCssFile();

    // NULL route won't match any mapped route or the admin pattern.
    // str_starts_with(null, 'jaraba_') returns false in PHP 8.1+.
    $this->assertSame('homepage', $file);
  }

}
