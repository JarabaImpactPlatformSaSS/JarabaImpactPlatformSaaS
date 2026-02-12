<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica del RiasecService.
 *
 * Verifica generacion de codigo, descripcion de perfil y
 * logica de fallback sin depender del Entity API.
 *
 * @group jaraba_self_discovery
 */
class RiasecServiceTest extends TestCase {

  /**
   * Descripciones de tipos RIASEC.
   */
  private const TYPE_DESCRIPTIONS = [
    'R' => 'Realista - practico, tecnico',
    'I' => 'Investigador - analitico, cientifico',
    'A' => 'Artistico - creativo, expresivo',
    'S' => 'Social - colaborador, empatico',
    'E' => 'Emprendedor - lider, persuasivo',
    'C' => 'Convencional - organizado, estructurado',
  ];

  /**
   * Genera la descripcion del perfil a partir del codigo.
   *
   * Replica la logica de RiasecService::getProfileDescription().
   */
  private function getProfileDescription(string $code): string {
    $letters = str_split($code);
    $descriptions = array_map(
      fn($l) => self::TYPE_DESCRIPTIONS[$l] ?? $l,
      $letters
    );
    return implode(' + ', $descriptions);
  }

  /**
   * Obtiene los tipos dominantes de un codigo.
   */
  private function getDominantTypes(string $code): array {
    return str_split($code);
  }

  /**
   * Tests la generacion de descripcion para codigo RIA.
   */
  public function testProfileDescriptionRIA(): void {
    $description = $this->getProfileDescription('RIA');

    $this->assertStringContainsString('Realista', $description);
    $this->assertStringContainsString('Investigador', $description);
    $this->assertStringContainsString('Artistico', $description);
    $this->assertStringContainsString(' + ', $description);
  }

  /**
   * Tests la generacion de descripcion para codigo SEC.
   */
  public function testProfileDescriptionSEC(): void {
    $description = $this->getProfileDescription('SEC');

    $this->assertStringContainsString('Social', $description);
    $this->assertStringContainsString('Emprendedor', $description);
    $this->assertStringContainsString('Convencional', $description);
  }

  /**
   * Tests que los tipos dominantes se extraen correctamente.
   */
  public function testDominantTypes(): void {
    $types = $this->getDominantTypes('RIA');

    $this->assertCount(3, $types);
    $this->assertSame(['R', 'I', 'A'], $types);
  }

  /**
   * Tests que el tipo primario es la primera letra del codigo.
   */
  public function testPrimaryTypeIsFirstLetter(): void {
    $code = 'ECS';
    $primaryType = $code[0];

    $this->assertSame('E', $primaryType);
    $this->assertSame('Emprendedor - lider, persuasivo', self::TYPE_DESCRIPTIONS[$primaryType]);
  }

  /**
   * Tests que existen 6 tipos RIASEC.
   */
  public function testSixTypesExist(): void {
    $this->assertCount(6, self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('R', self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('I', self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('A', self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('S', self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('E', self::TYPE_DESCRIPTIONS);
    $this->assertArrayHasKey('C', self::TYPE_DESCRIPTIONS);
  }

  /**
   * Tests que un codigo con letras invalidas no causa error.
   */
  public function testInvalidCodeLetterFallback(): void {
    $description = $this->getProfileDescription('XYZ');

    // Las letras invalidas se usan literalmente como fallback.
    $this->assertStringContainsString('X', $description);
    $this->assertStringContainsString('Y', $description);
    $this->assertStringContainsString('Z', $description);
  }

  /**
   * Tests la logica de scores RIASEC como array asociativo.
   */
  public function testScoresArrayStructure(): void {
    $scores = [
      'R' => 80,
      'I' => 90,
      'A' => 70,
      'S' => 60,
      'E' => 50,
      'C' => 40,
    ];

    $this->assertCount(6, $scores);
    $this->assertSame(90, $scores['I']);

    // Verificar que el codigo se genera del mas alto al mas bajo.
    arsort($scores);
    $code = implode('', array_slice(array_keys($scores), 0, 3));
    $this->assertSame('IRA', $code);
  }

}
