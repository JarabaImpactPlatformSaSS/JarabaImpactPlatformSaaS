<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_content_hub\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Tests the slug generation function from jaraba_content_hub.module.
 *
 * The _jaraba_content_hub_slugify() function is a pure string manipulation
 * function that transliterates accented characters and generates URL-safe
 * slugs. Since the module file may have Drupal dependencies at the top level,
 * we replicate the function body here for unit testing isolation.
 *
 * The real implementation lives in jaraba_content_hub.module line ~547.
 *
 * @group jaraba_content_hub
 * @covers ::_jaraba_content_hub_slugify
 */
class ContentArticleSlugTest extends TestCase {

  /**
   * Replicates _jaraba_content_hub_slugify for unit testing.
   *
   * This is an exact copy of the implementation in jaraba_content_hub.module.
   * If the source implementation changes, this test helper must be updated.
   *
   * @param string $string
   *   The string to slugify.
   *
   * @return string
   *   URL-safe slug.
   */
  protected function slugify(string $string): string {
    $transliterations = [
      'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
      'ñ' => 'n', 'ü' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i',
      'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', 'Ü' => 'u',
    ];
    $string = strtr($string, $transliterations);
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($string));
    return trim($slug, '-');
  }

  /**
   * Tests slug generation with various inputs.
   *
   * @dataProvider slugifyProvider
   */
  public function testSlugify(string $input, string $expected): void {
    $this->assertSame($expected, $this->slugify($input));
  }

  /**
   * Data provider for testSlugify.
   *
   * @return array<string, array{string, string}>
   *   Test cases with input string and expected slug.
   */
  public static function slugifyProvider(): array {
    return [
      'simple lowercase title' => [
        'simple title',
        'simple-title',
      ],
      'title with accents' => [
        'Título con Acentos',
        'titulo-con-acentos',
      ],
      'title with extra spaces' => [
        'Title with  SPACES',
        'title-with-spaces',
      ],
      'spanish characters' => [
        'Señor José García',
        'senor-jose-garcia',
      ],
      'triple dashes reduced' => [
        'Title---dashes',
        'title-dashes',
      ],
      'special characters removed' => [
        '¡Special! chars?',
        'special-chars',
      ],
      'empty string' => [
        '',
        '',
      ],
      'single ñ' => [
        'ñ',
        'n',
      ],
      'all uppercase' => [
        'ALL UPPERCASE TITLE',
        'all-uppercase-title',
      ],
      'numbers preserved' => [
        'Article 2026 Edition 3',
        'article-2026-edition-3',
      ],
      'leading and trailing dashes trimmed' => [
        '---leading-trailing---',
        'leading-trailing',
      ],
      'mixed accents' => [
        'Información Útil Económica',
        'informacion-util-economica',
      ],
      'uppercase ñ' => [
        'Ñoño',
        'nono',
      ],
      'ü dieresis' => [
        'Bilingüe',
        'bilingue',
      ],
      'only special chars' => [
        '¿¡!@#$%^&*()',
        '',
      ],
      'multiple consecutive spaces' => [
        'word     word',
        'word-word',
      ],
      'tabs and newlines' => [
        "tab\there\nnewline",
        'tab-here-newline',
      ],
      'already a slug' => [
        'already-a-slug',
        'already-a-slug',
      ],
      'mixed case with numbers' => [
        'Guía de Migración v2.0',
        'guia-de-migracion-v2-0',
      ],
    ];
  }

  /**
   * Tests that slugify always returns a string.
   */
  public function testSlugifyReturnType(): void {
    $result = $this->slugify('any input');
    $this->assertIsString($result);
  }

  /**
   * Tests that slugify output contains only valid slug characters.
   *
   * @dataProvider slugifyProvider
   */
  public function testSlugifyOutputContainsOnlyValidChars(string $input, string $expected): void {
    $slug = $this->slugify($input);
    if ($slug === '') {
      $this->assertSame('', $slug, 'Empty input produces empty slug.');
    }
    else {
      $this->assertMatchesRegularExpression(
        '/^[a-z0-9]+(-[a-z0-9]+)*$/',
        $slug,
        "Slug '{$slug}' from input '{$input}' contains invalid characters."
      );
    }
  }

  /**
   * Tests that slugify never produces double dashes.
   */
  public function testSlugifyNoDoubleDashes(): void {
    $inputs = [
      'Title -- Subtitle',
      'Hello   World',
      'Test___underscores',
      'Many!!!Exclamations!!!Here',
    ];

    foreach ($inputs as $input) {
      $slug = $this->slugify($input);
      $this->assertStringNotContainsString('--', $slug, "Input '{$input}' produced double dashes in slug '{$slug}'.");
    }
  }

}
