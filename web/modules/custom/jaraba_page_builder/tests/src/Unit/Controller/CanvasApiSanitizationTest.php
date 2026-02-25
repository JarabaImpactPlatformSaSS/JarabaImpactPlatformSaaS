<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Tests sanitization methods of CanvasApiController.
 *
 * Verifies XSS prevention in HTML and CSS sanitization for the Page Builder.
 * Uses reflection to test protected methods directly.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\Controller\CanvasApiController
 * @group jaraba_page_builder
 */
class CanvasApiSanitizationTest extends TestCase {

  /**
   * Invokes a protected method on the controller via reflection.
   *
   * @param string $methodName
   *   The method name.
   * @param array $args
   *   Arguments to pass.
   *
   * @return mixed
   *   The return value.
   */
  protected function invokeMethod(string $methodName, array $args): mixed {
    $class = 'Drupal\jaraba_page_builder\Controller\CanvasApiController';
    $reflection = new \ReflectionClass($class);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    // Create instance without constructor (controller has DI dependencies).
    $instance = $reflection->newInstanceWithoutConstructor();
    return $method->invokeArgs($instance, $args);
  }

  // =========================================================================
  // TESTS: sanitizePageBuilderHtml — XSS script tags
  // =========================================================================

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlRemovesScriptTags(): void {
    $html = '<div>Hello</div><script>alert("xss")</script><p>World</p>';
    $result = $this->invokeMethod('sanitizePageBuilderHtml', [$html]);

    $this->assertStringNotContainsString('<script', $result);
    $this->assertStringNotContainsString('alert', $result);
    $this->assertStringContainsString('<div>Hello</div>', $result);
    $this->assertStringContainsString('<p>World</p>', $result);
  }

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlRemovesMultilineScripts(): void {
    $html = '<div>OK</div><script type="text/javascript">
      var x = 1;
      document.cookie = "stolen";
    </script>';
    $result = $this->invokeMethod('sanitizePageBuilderHtml', [$html]);

    $this->assertStringNotContainsString('<script', $result);
    $this->assertStringNotContainsString('document.cookie', $result);
  }

  // =========================================================================
  // TESTS: sanitizePageBuilderHtml — event handlers
  // =========================================================================

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlRemovesOnclickHandler(): void {
    $html = '<button onclick="alert(1)">Click</button>';
    $result = $this->invokeMethod('sanitizePageBuilderHtml', [$html]);

    $this->assertStringNotContainsString('onclick', $result);
    $this->assertStringContainsString('Click', $result);
  }

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlRemovesOnerrorHandler(): void {
    $html = '<img src="x" onerror="alert(1)">';
    $result = $this->invokeMethod('sanitizePageBuilderHtml', [$html]);

    $this->assertStringNotContainsString('onerror', $result);
  }

  // =========================================================================
  // TESTS: sanitizePageBuilderHtml — javascript: protocol
  // =========================================================================

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlRemovesJavascriptProtocol(): void {
    $html = '<a href="javascript:alert(1)">Link</a>';
    $result = $this->invokeMethod('sanitizePageBuilderHtml', [$html]);

    $this->assertStringNotContainsString('javascript:', $result);
    $this->assertStringContainsString('Link', $result);
  }

  // =========================================================================
  // TESTS: sanitizePageBuilderHtml — empty input
  // =========================================================================

  /**
   * @covers ::sanitizePageBuilderHtml
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeHtmlEmptyInput(): void {
    $result = $this->invokeMethod('sanitizePageBuilderHtml', ['']);
    $this->assertSame('', $result);
  }

  // =========================================================================
  // TESTS: sanitizeCss — expression()
  // =========================================================================

  /**
   * @covers ::sanitizeCss
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeCssRemovesExpression(): void {
    $css = 'body { width: expression(alert(1)); }';
    $result = $this->invokeMethod('sanitizeCss', [$css]);

    $this->assertStringNotContainsString('expression', $result);
  }

  // =========================================================================
  // TESTS: sanitizeCss — @import
  // =========================================================================

  /**
   * @covers ::sanitizeCss
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeCssRemovesImport(): void {
    $css = '@import url("https://evil.com/steal.css"); body { color: red; }';
    $result = $this->invokeMethod('sanitizeCss', [$css]);

    $this->assertStringNotContainsString('@import', $result);
    $this->assertStringContainsString('color: red', $result);
  }

  // =========================================================================
  // TESTS: sanitizeCss — behavior/moz-binding
  // =========================================================================

  /**
   * @covers ::sanitizeCss
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeCssRemovesBehavior(): void {
    $css = 'body { behavior: url(evil.htc); }';
    $result = $this->invokeMethod('sanitizeCss', [$css]);

    $this->assertStringNotContainsString('behavior', $result);
  }

  /**
   * @covers ::sanitizeCss
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeCssRemovesMozBinding(): void {
    $css = 'body { -moz-binding: url("evil.xml#xss"); }';
    $result = $this->invokeMethod('sanitizeCss', [$css]);

    $this->assertStringNotContainsString('-moz-binding', $result);
  }

  // =========================================================================
  // TESTS: sanitizeCss — clean CSS passes through
  // =========================================================================

  /**
   * @covers ::sanitizeCss
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSanitizeCssPreservesCleanCss(): void {
    $css = '.hero { background: #fff; padding: 20px; font-size: 1.2rem; }';
    $result = $this->invokeMethod('sanitizeCss', [$css]);

    $this->assertSame($css, $result);
  }

}
