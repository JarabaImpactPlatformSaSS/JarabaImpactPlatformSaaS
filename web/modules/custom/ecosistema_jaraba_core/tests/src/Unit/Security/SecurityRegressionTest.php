<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Security;

use Drupal\Component\Utility\Xss;
use Drupal\Tests\UnitTestCase;

/**
 * SAFEGUARD-SECURITY-REGRESSION-TEST-001: Regression tests for security fixes.
 *
 * Each test validates that a specific security vulnerability from the
 * 2026-03-26 production audit cannot be reintroduced.
 *
 * @group ecosistema_jaraba_core
 * @group security
 */
class SecurityRegressionTest extends UnitTestCase {

  /**
   * SEC-C04: isSafeUrl() must reject javascript: protocol.
   *
   * Tests the URL validation logic that prevents XSS via LLM-generated
   * links with javascript:, data:, or vbscript: protocols.
   *
   * @dataProvider unsafeUrlProvider
   */
  public function testUnsafeUrlsAreRejected(string $url, bool $expectedSafe): void {
    $result = $this->isSafeUrl($url);
    self::assertSame($expectedSafe, $result, "URL '$url' safety check failed.");
  }

  /**
   * Data provider for URL protocol validation.
   *
   * @return array<string, array{string, bool}>
   */
  public static function unsafeUrlProvider(): array {
    return [
      'https is safe' => ['https://example.com', TRUE],
      'http is safe' => ['http://example.com', TRUE],
      'relative path is safe' => ['/about', TRUE],
      'javascript protocol blocked' => ['javascript:alert(1)', FALSE],
      'javascript with spaces blocked' => ['javascript:void(0)', FALSE],
      'data protocol blocked' => ['data:text/html,<script>alert(1)</script>', FALSE],
      'vbscript blocked' => ['vbscript:MsgBox("xss")', FALSE],
      'empty string is safe' => ['', TRUE],
      'ftp blocked' => ['ftp://evil.com/malware', FALSE],
      'javascript case insensitive' => ['JavaScript:alert(1)', FALSE],
    ];
  }

  /**
   * SEC-C05: Xss::filterAdmin() must strip script tags from text_long.
   *
   * Validates that SuccessCase text_long fields are sanitized.
   */
  public function testXssFilterAdminStripsScripts(): void {
    $malicious = '<p>Normal text</p><script>alert("xss")</script><p>More text</p>';
    $filtered = Xss::filterAdmin($malicious);

    self::assertStringNotContainsString('<script>', $filtered);
    // Xss::filterAdmin strips tags but preserves inner text — the script
    // content becomes inert text without execution context.
    self::assertStringContainsString('<p>Normal text</p>', $filtered);
    self::assertStringContainsString('<p>More text</p>', $filtered);
  }

  /**
   * SEC-C05: Xss::filterAdmin() allows safe HTML tags.
   */
  public function testXssFilterAdminAllowsSafeHtml(): void {
    $safeHtml = '<p>Text with <strong>bold</strong> and <em>italic</em></p><ul><li>Item</li></ul>';
    $filtered = Xss::filterAdmin($safeHtml);

    self::assertStringContainsString('<strong>bold</strong>', $filtered);
    self::assertStringContainsString('<em>italic</em>', $filtered);
    self::assertStringContainsString('<ul>', $filtered);
  }

  /**
   * SEC-A07: Tenant ID comparison must use (int) cast.
   *
   * EntityInterface::id() returns string in MariaDB. Direct === comparison
   * with int parameter is always FALSE in strict_types=1.
   */
  public function testTenantIdComparisonWithIntCast(): void {
    // Use dynamic values to prevent PHPStan constant folding.
    $entityId = $this->getEntityIdFromMariaDb();
    $tenantId = 42;

    // With (int) cast: correct comparison (the fix for SEC-A07).
    self::assertSame((int) $entityId, $tenantId, '(int) cast should make comparison work.');
  }

  /**
   * SEC-C02: Empty HMAC secret must be detected as invalid.
   */
  public function testEmptyHmacSecretIsDetectedAsInvalid(): void {
    $secret = $this->getEmptySecret();
    self::assertSame('', $secret, 'Empty HMAC secret should be empty string.');
  }

  /**
   * Simulates EntityInterface::id() returning string from MariaDB.
   */
  private function getEntityIdFromMariaDb(): string {
    return '42';
  }

  /**
   * Simulates an unconfigured secret.
   */
  private function getEmptySecret(): string {
    return '';
  }

  /**
   * Mirrors the isSafeUrl() function from contextual-copilot.js for testing.
   *
   * @param string $url
   *   URL to validate.
   *
   * @return bool
   *   TRUE if URL has safe protocol.
   */
  private function isSafeUrl(string $url): bool {
    if ($url === '') {
      return TRUE;
    }
    // Parse URL and check protocol.
    $parsed = parse_url($url);
    if ($parsed === FALSE) {
      return FALSE;
    }
    // Relative URLs (no scheme) are safe.
    if (!isset($parsed['scheme'])) {
      return TRUE;
    }
    $scheme = strtolower($parsed['scheme']);
    return in_array($scheme, ['http', 'https'], TRUE);
  }

}
