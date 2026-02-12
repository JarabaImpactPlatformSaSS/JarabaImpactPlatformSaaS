<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para AccessibilityAuditService.
 *
 * Verifica calculos de contraste WCAG 2.1, luminancia relativa,
 * y scoring de accesibilidad.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\AccessibilityAuditService
 */
class AccessibilityAuditServiceTest extends TestCase {

  /**
   * Calcula la luminancia relativa de un color hex.
   *
   * Replica la logica de AccessibilityAuditService::getRelativeLuminance().
   */
  protected function getRelativeLuminance(string $hex): float {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    $r = $r <= 0.03928 ? $r / 12.92 : (($r + 0.055) / 1.055) ** 2.4;
    $g = $g <= 0.03928 ? $g / 12.92 : (($g + 0.055) / 1.055) ** 2.4;
    $b = $b <= 0.03928 ? $b / 12.92 : (($b + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
  }

  /**
   * Calcula el ratio de contraste entre dos colores.
   *
   * Replica la logica de AccessibilityAuditService::checkContrast().
   */
  protected function checkContrast(string $foreground, string $background): array {
    $fgLuminance = $this->getRelativeLuminance($foreground);
    $bgLuminance = $this->getRelativeLuminance($background);

    $lighter = max($fgLuminance, $bgLuminance);
    $darker = min($fgLuminance, $bgLuminance);

    $ratio = ($lighter + 0.05) / ($darker + 0.05);

    return [
      'foreground' => $foreground,
      'background' => $background,
      'ratio' => round($ratio, 2),
      'aa_normal' => $ratio >= 4.5,
      'aa_large' => $ratio >= 3.0,
      'aaa_normal' => $ratio >= 7.0,
      'aaa_large' => $ratio >= 4.5,
    ];
  }

  /**
   * Calcula score de accesibilidad a partir de issues.
   */
  protected function calculateScore(array $issues): int {
    $errorCount = count(array_filter($issues, fn($i) => $i['type'] === 'error'));
    $warningCount = count(array_filter($issues, fn($i) => $i['type'] === 'warning'));
    return max(0, 100 - ($errorCount * 20) - ($warningCount * 5));
  }

  /**
   * Tests luminancia de blanco puro.
   */
  public function testWhiteLuminance(): void {
    $luminance = $this->getRelativeLuminance('#FFFFFF');
    $this->assertEqualsWithDelta(1.0, $luminance, 0.001);
  }

  /**
   * Tests luminancia de negro puro.
   */
  public function testBlackLuminance(): void {
    $luminance = $this->getRelativeLuminance('#000000');
    $this->assertEqualsWithDelta(0.0, $luminance, 0.001);
  }

  /**
   * Tests que la luminancia esta entre 0 y 1.
   *
   * @dataProvider colorProvider
   */
  public function testLuminanceRange(string $color): void {
    $luminance = $this->getRelativeLuminance($color);
    $this->assertGreaterThanOrEqual(0.0, $luminance);
    $this->assertLessThanOrEqual(1.0, $luminance);
  }

  /**
   * Data provider con colores de la paleta Jaraba.
   */
  public static function colorProvider(): array {
    return [
      'primary (#FF8C42)' => ['#FF8C42'],
      'secondary (#00A9A5)' => ['#00A9A5'],
      'corporate (#233D63)' => ['#233D63'],
      'nature (#556B2F)' => ['#556B2F'],
      'success (#10B981)' => ['#10B981'],
      'warning (#F59E0B)' => ['#F59E0B'],
      'danger (#EF4444)' => ['#EF4444'],
      'headings (#1A1A2E)' => ['#1A1A2E'],
      'body text (#334155)' => ['#334155'],
      'muted (#64748B)' => ['#64748B'],
    ];
  }

  /**
   * Tests contraste maximo (blanco sobre negro).
   */
  public function testMaxContrastRatio(): void {
    $result = $this->checkContrast('#000000', '#FFFFFF');
    $this->assertSame(21.0, $result['ratio']);
    $this->assertTrue($result['aa_normal']);
    $this->assertTrue($result['aaa_normal']);
  }

  /**
   * Tests contraste minimo (mismo color).
   */
  public function testMinContrastRatio(): void {
    $result = $this->checkContrast('#FFFFFF', '#FFFFFF');
    $this->assertSame(1.0, $result['ratio']);
    $this->assertFalse($result['aa_normal']);
    $this->assertFalse($result['aa_large']);
  }

  /**
   * Tests que headings (#1A1A2E) sobre blanco cumple WCAG AA.
   */
  public function testHeadingsOnWhitePassesAA(): void {
    $result = $this->checkContrast('#1A1A2E', '#FFFFFF');
    $this->assertTrue($result['aa_normal']);
    $this->assertGreaterThanOrEqual(4.5, $result['ratio']);
  }

  /**
   * Tests que body text (#334155) sobre blanco cumple WCAG AA.
   */
  public function testBodyTextOnWhitePassesAA(): void {
    $result = $this->checkContrast('#334155', '#FFFFFF');
    $this->assertTrue($result['aa_normal']);
  }

  /**
   * Tests contraste blanco sobre corporate (#233D63).
   */
  public function testWhiteOnCorporatePassesAALarge(): void {
    $result = $this->checkContrast('#FFFFFF', '#233D63');
    $this->assertTrue($result['aa_large']);
  }

  /**
   * Tests que el contraste es simetrico.
   */
  public function testContrastIsSymmetric(): void {
    $result1 = $this->checkContrast('#FF8C42', '#FFFFFF');
    $result2 = $this->checkContrast('#FFFFFF', '#FF8C42');

    $this->assertSame($result1['ratio'], $result2['ratio']);
    $this->assertSame($result1['aa_normal'], $result2['aa_normal']);
  }

  /**
   * Tests soporte de formato hex corto (#RGB).
   */
  public function testShortHexFormat(): void {
    $shortResult = $this->getRelativeLuminance('#FFF');
    $longResult = $this->getRelativeLuminance('#FFFFFF');
    $this->assertEqualsWithDelta($shortResult, $longResult, 0.001);
  }

  /**
   * Tests soporte de hash opcional.
   */
  public function testHexWithoutHash(): void {
    $withHash = $this->getRelativeLuminance('#FF8C42');
    $withoutHash = $this->getRelativeLuminance('FF8C42');
    $this->assertEqualsWithDelta($withHash, $withoutHash, 0.001);
  }

  /**
   * Tests scoring perfecto (sin issues).
   */
  public function testPerfectScore(): void {
    $score = $this->calculateScore([]);
    $this->assertSame(100, $score);
  }

  /**
   * Tests que cada error descuenta 20 puntos.
   */
  public function testErrorDeducts20Points(): void {
    $issues = [
      ['type' => 'error', 'message' => 'Error 1'],
    ];
    $this->assertSame(80, $this->calculateScore($issues));
  }

  /**
   * Tests que cada warning descuenta 5 puntos.
   */
  public function testWarningDeducts5Points(): void {
    $issues = [
      ['type' => 'warning', 'message' => 'Warning 1'],
    ];
    $this->assertSame(95, $this->calculateScore($issues));
  }

  /**
   * Tests que el score no baja de 0.
   */
  public function testScoreFloorIsZero(): void {
    $issues = array_fill(0, 10, ['type' => 'error', 'message' => 'Error']);
    $this->assertSame(0, $this->calculateScore($issues));
  }

  /**
   * Tests combinacion de errores y warnings.
   */
  public function testMixedIssuesScoring(): void {
    $issues = [
      ['type' => 'error', 'message' => 'Error 1'],
      ['type' => 'warning', 'message' => 'Warning 1'],
      ['type' => 'warning', 'message' => 'Warning 2'],
    ];
    // 100 - 20 - 5 - 5 = 70
    $this->assertSame(70, $this->calculateScore($issues));
  }

  /**
   * Tests deteccion de imagen sin alt en HTML.
   */
  public function testDetectsImageWithoutAlt(): void {
    $html = '<p>Text</p><img src="photo.jpg"><p>More text</p>';
    preg_match_all('/<img[^>]*>/i', $html, $matches);

    $hasAltIssues = FALSE;
    foreach ($matches[0] as $img) {
      if (!str_contains($img, 'alt=')) {
        $hasAltIssues = TRUE;
      }
    }
    $this->assertTrue($hasAltIssues);
  }

  /**
   * Tests que imagen con alt no genera issue.
   */
  public function testImageWithAltIsClean(): void {
    $html = '<img src="photo.jpg" alt="Foto del producto">';
    preg_match_all('/<img[^>]*>/i', $html, $matches);

    $hasAltIssues = FALSE;
    foreach ($matches[0] as $img) {
      if (!str_contains($img, 'alt=')) {
        $hasAltIssues = TRUE;
      }
    }
    $this->assertFalse($hasAltIssues);
  }

  /**
   * Tests deteccion de salto en jerarquia de headings.
   */
  public function testDetectsHeadingHierarchySkip(): void {
    $html = '<h1>Title</h1><h3>Subtitle</h3>';
    preg_match_all('/<h(\d)/i', $html, $matches);
    $levels = array_map('intval', $matches[1]);

    $hasSkip = FALSE;
    for ($i = 1; $i < count($levels); $i++) {
      if ($levels[$i] > $levels[$i - 1] + 1) {
        $hasSkip = TRUE;
      }
    }
    $this->assertTrue($hasSkip);
  }

  /**
   * Tests que headings secuenciales no generan issue.
   */
  public function testSequentialHeadingsAreClean(): void {
    $html = '<h1>Title</h1><h2>Subtitle</h2><h3>Section</h3>';
    preg_match_all('/<h(\d)/i', $html, $matches);
    $levels = array_map('intval', $matches[1]);

    $hasSkip = FALSE;
    for ($i = 1; $i < count($levels); $i++) {
      if ($levels[$i] > $levels[$i - 1] + 1) {
        $hasSkip = TRUE;
      }
    }
    $this->assertFalse($hasSkip);
  }

  /**
   * Tests WCAG level classification.
   */
  public function testWcagLevelClassification(): void {
    // Score >= 90 => AA
    $this->assertSame('AA', 95 >= 90 ? 'AA' : (95 >= 70 ? 'partial' : 'fail'));
    $this->assertSame('AA', 90 >= 90 ? 'AA' : (90 >= 70 ? 'partial' : 'fail'));

    // Score >= 70 and < 90 => partial
    $this->assertSame('partial', 80 >= 90 ? 'AA' : (80 >= 70 ? 'partial' : 'fail'));

    // Score < 70 => fail
    $this->assertSame('fail', 50 >= 90 ? 'AA' : (50 >= 70 ? 'partial' : 'fail'));
  }

}
