<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_theming\Unit\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_theming\Controller\ThemePreviewController;
use Drupal\jaraba_theming\Entity\TenantThemeConfig;
use Drupal\jaraba_theming\Service\ThemeTokenService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit tests for ThemePreviewController.
 *
 * Tests the theme preview rendering including override extraction,
 * CSS generation with query parameter overrides, config-based
 * rendering, and HTML output structure.
 *
 * @coversDefaultClass \Drupal\jaraba_theming\Controller\ThemePreviewController
 * @group jaraba_theming
 */
class ThemePreviewControllerTest extends TestCase {

  /**
   * Mock theme token service.
   */
  protected ThemeTokenService $themeTokenService;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage for tenant_theme_config.
   */
  protected EntityStorageInterface $themeConfigStorage;

  /**
   * The controller under test.
   */
  protected ThemePreviewController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeTokenService = $this->createMock(ThemeTokenService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->themeConfigStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('tenant_theme_config')
      ->willReturn($this->themeConfigStorage);

    $this->controller = new ThemePreviewController(
      $this->themeTokenService,
      $this->entityTypeManager,
    );
  }

  /**
   * Tests preview returns a 200 Response with HTML content type.
   *
   * @covers ::preview
   */
  public function testPreviewReturnsHtmlResponse(): void {
    $this->themeTokenService->method('generateCss')->willReturn(':root { --ej-color-primary: #FF8C42; }');

    $request = Request::create('/admin/appearance/theme-preview', 'GET');
    $response = $this->controller->preview($request);

    $this->assertInstanceOf(Response::class, $response);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
  }

  /**
   * Tests preview returns no-cache headers.
   *
   * @covers ::preview
   */
  public function testPreviewReturnsNoCacheHeaders(): void {
    $this->themeTokenService->method('generateCss')->willReturn(':root {}');

    $request = Request::create('/admin/appearance/theme-preview', 'GET');
    $response = $this->controller->preview($request);

    $cacheControl = $response->headers->get('Cache-Control');
    $this->assertStringContainsString('no-cache', $cacheControl);
    $this->assertStringContainsString('no-store', $cacheControl);
    $this->assertStringContainsString('must-revalidate', $cacheControl);
  }

  /**
   * Tests preview renders complete HTML document structure.
   *
   * @covers ::preview
   */
  public function testPreviewRendersCompleteHtmlDocument(): void {
    $this->themeTokenService->method('generateCss')->willReturn(':root {}');

    $request = Request::create('/admin/appearance/theme-preview', 'GET');
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('<!DOCTYPE html>', $html);
    $this->assertStringContainsString('<html lang="es">', $html);
    $this->assertStringContainsString('Vista Previa del Tema', $html);
    $this->assertStringContainsString('Paleta de Colores', $html);
    $this->assertStringContainsString('Tipografia', $html);
    $this->assertStringContainsString('Botones', $html);
    $this->assertStringContainsString('Tarjetas', $html);
  }

  /**
   * Tests preview applies color_primary query parameter override.
   *
   * @covers ::preview
   */
  public function testPreviewAppliesColorPrimaryOverride(): void {
    $this->themeTokenService->method('generateCss')->willReturn('');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'color_primary' => '#FF0000',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-color-primary: #FF0000;', $html);
  }

  /**
   * Tests preview applies multiple query parameter overrides.
   *
   * @covers ::preview
   */
  public function testPreviewAppliesMultipleOverrides(): void {
    $this->themeTokenService->method('generateCss')->willReturn('');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'color_primary' => '#FF0000',
      'color_secondary' => '#00FF00',
      'color_accent' => '#0000FF',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-color-primary: #FF0000;', $html);
    $this->assertStringContainsString('--ej-color-secondary: #00FF00;', $html);
    $this->assertStringContainsString('--ej-color-accent: #0000FF;', $html);
  }

  /**
   * Tests preview wraps font family overrides with quotes.
   *
   * When a font_headings parameter is provided without quotes, the
   * controller should wrap it with quotes and add sans-serif fallback.
   *
   * @covers ::preview
   */
  public function testPreviewWrapsFontOverrideWithQuotes(): void {
    $this->themeTokenService->method('generateCss')->willReturn('');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'font_headings' => 'Playfair Display',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString("--ej-font-family-headings: 'Playfair Display', sans-serif;", $html);
  }

  /**
   * Tests preview adds 'px' to numeric border_radius values.
   *
   * @covers ::preview
   */
  public function testPreviewAddsPxToBorderRadiusNumericValue(): void {
    $this->themeTokenService->method('generateCss')->willReturn('');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'border_radius' => '16',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-border-radius: 16px;', $html);
  }

  /**
   * Tests preview loads saved config when config_id is provided.
   *
   * @covers ::preview
   */
  public function testPreviewLoadsConfigWhenConfigIdProvided(): void {
    $config = $this->createMock(TenantThemeConfig::class);
    $config->method('generateCssVariables')
      ->willReturn(':root { --ej-color-primary: #8B5CF6; }');

    $this->themeConfigStorage->expects($this->once())
      ->method('load')
      ->with(42)
      ->willReturn($config);

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'config_id' => '42',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-color-primary: #8B5CF6;', $html);
  }

  /**
   * Tests preview falls back to service CSS when config_id is invalid.
   *
   * @covers ::preview
   */
  public function testPreviewFallsBackToServiceCssWhenConfigNotFound(): void {
    $this->themeConfigStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->themeTokenService->expects($this->once())
      ->method('generateCss')
      ->willReturn(':root { --ej-color-primary: #FF8C42; }');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'config_id' => '999',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-color-primary: #FF8C42;', $html);
  }

  /**
   * Tests preview uses default service CSS when no parameters are provided.
   *
   * @covers ::preview
   */
  public function testPreviewUsesDefaultCssWhenNoParams(): void {
    $this->themeTokenService->expects($this->once())
      ->method('generateCss')
      ->willReturn(':root { --ej-color-primary: #FF8C42; }');

    $request = Request::create('/admin/appearance/theme-preview', 'GET');
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    $this->assertStringContainsString('--ej-color-primary: #FF8C42;', $html);
  }

  /**
   * Tests preview escapes HTML special characters in overrides.
   *
   * This verifies XSS protection: query parameters with script injection
   * should be sanitized via htmlspecialchars().
   *
   * @covers ::preview
   */
  public function testPreviewEscapesHtmlInOverrides(): void {
    $this->themeTokenService->method('generateCss')->willReturn('');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'color_primary' => '<script>alert("xss")</script>',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    // The <script> tag should be escaped, not present as raw HTML.
    $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
    $this->assertStringContainsString('&lt;script&gt;', $html);
  }

  /**
   * Tests preview ignores unrecognized query parameters.
   *
   * Only the allowed override parameters should be extracted.
   *
   * @covers ::preview
   */
  public function testPreviewIgnoresUnrecognizedParams(): void {
    $this->themeTokenService->expects($this->once())
      ->method('generateCss')
      ->willReturn(':root { --ej-color-primary: #default; }');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'malicious_param' => 'evil-value',
      'some_random' => 'data',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    // The default CSS should be used, not override CSS.
    $this->assertStringNotContainsString('evil-value', $html);
    $this->assertStringContainsString('--ej-color-primary: #default;', $html);
  }

  /**
   * Tests preview ignores empty query parameter values.
   *
   * @covers ::preview
   */
  public function testPreviewIgnoresEmptyOverrideValues(): void {
    $this->themeTokenService->expects($this->once())
      ->method('generateCss')
      ->willReturn(':root { --ej-color-primary: #default; }');

    $request = Request::create('/admin/appearance/theme-preview', 'GET', [
      'color_primary' => '',
    ]);
    $response = $this->controller->preview($request);
    $html = $response->getContent();

    // Empty color_primary should not produce an override block.
    $this->assertStringContainsString('--ej-color-primary: #default;', $html);
  }

}
