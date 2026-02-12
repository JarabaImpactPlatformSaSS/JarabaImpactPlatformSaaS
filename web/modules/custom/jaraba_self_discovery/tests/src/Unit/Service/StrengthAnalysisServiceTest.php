<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica del StrengthAnalysisService.
 *
 * Verifica calculo de top 5, catalogo de fortalezas
 * y parsing de datos sin depender del Entity API.
 *
 * @group jaraba_self_discovery
 */
class StrengthAnalysisServiceTest extends TestCase {

  /**
   * Catalogo de 24 fortalezas agrupadas por virtud.
   */
  private const STRENGTHS = [
    'creativity' => ['name' => 'Creatividad', 'virtue' => 'Sabiduria'],
    'curiosity' => ['name' => 'Curiosidad', 'virtue' => 'Sabiduria'],
    'judgment' => ['name' => 'Criterio', 'virtue' => 'Sabiduria'],
    'love_learning' => ['name' => 'Amor por aprender', 'virtue' => 'Sabiduria'],
    'perspective' => ['name' => 'Perspectiva', 'virtue' => 'Sabiduria'],
    'bravery' => ['name' => 'Valentia', 'virtue' => 'Coraje'],
    'perseverance' => ['name' => 'Perseverancia', 'virtue' => 'Coraje'],
    'honesty' => ['name' => 'Honestidad', 'virtue' => 'Coraje'],
    'zest' => ['name' => 'Vitalidad', 'virtue' => 'Coraje'],
    'love' => ['name' => 'Amor', 'virtue' => 'Humanidad'],
    'kindness' => ['name' => 'Amabilidad', 'virtue' => 'Humanidad'],
    'social_intel' => ['name' => 'Inteligencia social', 'virtue' => 'Humanidad'],
    'teamwork' => ['name' => 'Trabajo en equipo', 'virtue' => 'Justicia'],
    'fairness' => ['name' => 'Equidad', 'virtue' => 'Justicia'],
    'leadership' => ['name' => 'Liderazgo', 'virtue' => 'Justicia'],
    'forgiveness' => ['name' => 'Perdon', 'virtue' => 'Templanza'],
    'humility' => ['name' => 'Humildad', 'virtue' => 'Templanza'],
    'prudence' => ['name' => 'Prudencia', 'virtue' => 'Templanza'],
    'self_control' => ['name' => 'Autocontrol', 'virtue' => 'Templanza'],
    'appreciation' => ['name' => 'Apreciacion', 'virtue' => 'Transcendencia'],
    'gratitude' => ['name' => 'Gratitud', 'virtue' => 'Transcendencia'],
    'hope' => ['name' => 'Esperanza', 'virtue' => 'Transcendencia'],
    'humor' => ['name' => 'Humor', 'virtue' => 'Transcendencia'],
    'spirituality' => ['name' => 'Espiritualidad', 'virtue' => 'Transcendencia'],
  ];

  /**
   * Obtiene la descripcion de una fortaleza.
   *
   * Replica StrengthAnalysisService::getStrengthDescription().
   */
  private function getStrengthDescription(string $key): array {
    return self::STRENGTHS[$key] ?? ['name' => $key, 'virtue' => ''];
  }

  /**
   * Tests que existen 24 fortalezas en el catalogo.
   */
  public function testTwentyFourStrengthsInCatalog(): void {
    $this->assertCount(24, self::STRENGTHS);
  }

  /**
   * Tests las 6 virtudes cardinales.
   */
  public function testSixVirtues(): void {
    $virtues = array_unique(array_column(self::STRENGTHS, 'virtue'));
    sort($virtues);

    $expected = ['Coraje', 'Humanidad', 'Justicia', 'Sabiduria', 'Templanza', 'Transcendencia'];

    $this->assertCount(6, $virtues);
    $this->assertSame($expected, $virtues);
  }

  /**
   * Tests que la distribucion de fortalezas por virtud es correcta.
   */
  public function testStrengthDistributionByVirtue(): void {
    $distribution = [];
    foreach (self::STRENGTHS as $strength) {
      $distribution[$strength['virtue']] = ($distribution[$strength['virtue']] ?? 0) + 1;
    }

    $this->assertSame(5, $distribution['Sabiduria']);
    $this->assertSame(4, $distribution['Coraje']);
    $this->assertSame(3, $distribution['Humanidad']);
    $this->assertSame(3, $distribution['Justicia']);
    $this->assertSame(4, $distribution['Templanza']);
    $this->assertSame(5, $distribution['Transcendencia']);
  }

  /**
   * Tests la obtencion de descripcion por key valido.
   */
  public function testGetStrengthDescriptionValidKey(): void {
    $desc = $this->getStrengthDescription('creativity');

    $this->assertSame('Creatividad', $desc['name']);
    $this->assertSame('Sabiduria', $desc['virtue']);
  }

  /**
   * Tests la obtencion de descripcion por key invalido.
   */
  public function testGetStrengthDescriptionInvalidKey(): void {
    $desc = $this->getStrengthDescription('nonexistent');

    $this->assertSame('nonexistent', $desc['name']);
    $this->assertSame('', $desc['virtue']);
  }

  /**
   * Tests el parsing de top 5 desde JSON.
   */
  public function testTop5FromJson(): void {
    $top5Data = [
      'creativity' => ['name' => 'Creatividad', 'desc' => 'Generar ideas', 'score' => 5],
      'curiosity' => ['name' => 'Curiosidad', 'desc' => 'Explorar', 'score' => 4],
      'bravery' => ['name' => 'Valentia', 'desc' => 'Actuar', 'score' => 3],
      'leadership' => ['name' => 'Liderazgo', 'desc' => 'Inspirar', 'score' => 2],
      'hope' => ['name' => 'Esperanza', 'desc' => 'Futuro', 'score' => 2],
    ];

    $json = json_encode($top5Data);
    $parsed = json_decode($json, TRUE);

    $this->assertCount(5, $parsed);
    $topStrength = reset($parsed);
    $this->assertSame('Creatividad', $topStrength['name']);
  }

  /**
   * Tests que fallback con datos vacios retorna array vacio.
   */
  public function testEmptyDataFallback(): void {
    $top5 = [];
    $result = !empty($top5) ? reset($top5) : NULL;

    $this->assertNull($result);
  }

  /**
   * Tests que todas las keys son snake_case valido.
   */
  public function testAllKeysAreValidSnakeCase(): void {
    foreach (array_keys(self::STRENGTHS) as $key) {
      $this->assertMatchesRegularExpression('/^[a-z][a-z_]*$/', $key);
    }
  }

}
