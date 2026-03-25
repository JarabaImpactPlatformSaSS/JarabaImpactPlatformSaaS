<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use PHPUnit\Framework\TestCase;

/**
 * Base class for prompt regression tests with golden fixtures.
 *
 * GAP-AUD-015: Ensures AI prompts remain stable across code changes.
 *
 * Workflow:
 * 1. First run: golden fixture files are auto-created and test is skipped.
 * 2. Subsequent runs: actual prompt output is compared against the fixture.
 * 3. If a prompt intentionally changes, delete the fixture to regenerate.
 *
 * @see docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md
 */
abstract class PromptRegressionTestBase extends TestCase {

  /**
   * Returns the path to the golden fixtures directory.
   */
  protected function getFixturesPath(): string {
    return dirname(__DIR__, 3) . '/fixtures/prompts/';
  }

  /**
   * Asserts that a prompt matches its golden fixture.
   *
   * If the fixture file does not exist, it is created and the test
   * is skipped with a message indicating the fixture was generated.
   *
   * @param string $fixtureName
   *   The fixture filename (e.g., 'identity_long.txt').
   * @param string $actualPrompt
   *   The actual prompt string to compare.
   */
  protected function assertPromptMatchesGolden(string $fixtureName, string $actualPrompt): void {
    $fixturePath = $this->getFixturesPath() . $fixtureName;

    if (!file_exists($fixturePath)) {
      // Auto-create the golden fixture on first run.
      $dir = dirname($fixturePath);
      if (!is_dir($dir)) {
        mkdir($dir, 0755, TRUE);
      }
      file_put_contents($fixturePath, $actualPrompt);
      $this->markTestSkipped("Golden fixture created: {$fixtureName}. Re-run to validate.");
    }

    $expectedPrompt = file_get_contents($fixturePath);
    $this->assertEquals(
          $this->normalizePrompt($expectedPrompt),
          $this->normalizePrompt($actualPrompt),
          "Prompt regression detected for fixture '{$fixtureName}'. "
          . 'If this change is intentional, delete the fixture file and re-run.'
      );
  }

  /**
   * Normalizes a prompt for comparison.
   *
   * Trims whitespace and collapses multiple whitespace characters
   * into single spaces to avoid false failures from formatting changes.
   *
   * @param string $prompt
   *   The raw prompt string.
   *
   * @return string
   *   The normalized prompt.
   */
  protected function normalizePrompt(string $prompt): string {
    return preg_replace('/\s+/', ' ', trim($prompt));
  }

  /**
   * Asserts that a text does not contain Spanish PII patterns.
   *
   * AI-GUARDRAILS-PII-001: Prompts must never contain personal data.
   *
   * @param string $text
   *   The text to scan for PII patterns.
   */
  protected function assertNoPiiPatterns(string $text): void {
    $patterns = [
      '/\b\d{8}[A-Z]\b/' => 'DNI',
      '/\b[XYZ]\d{7}[A-Z]\b/' => 'NIE',
      '/\bES\d{22}\b/' => 'IBAN ES',
      '/\b[A-H]\d{8}\b/' => 'NIF/CIF',
      '/\+34\s?\d{9}\b/' => 'Telefono +34',
    ];

    foreach ($patterns as $pattern => $label) {
      $this->assertDoesNotMatchRegularExpression(
            $pattern,
            $text,
            "Prompt contains PII pattern ({$label}). AI-GUARDRAILS-PII-001 violation."
        );
    }
  }

  /**
   * Creates an agent instance without constructor for testing.
   *
   * Uses ReflectionClass::newInstanceWithoutConstructor() to bypass
   * the 6-10 arg constructor since prompt regression tests only need
   * access to getDefaultBrandVoice() and getAgentId().
   *
   * @param string $className
   *   Fully qualified class name.
   *
   * @return object
   *   Agent instance (uninitialized).
   */
  protected function createAgentWithoutConstructor(string $className): object {
    return (new \ReflectionClass($className))->newInstanceWithoutConstructor();
  }

  /**
   * Reads a protected/private constant from a class via reflection.
   *
   * @param string $className
   *   Fully qualified class name.
   * @param string $constantName
   *   The constant name.
   *
   * @return mixed
   *   The constant value.
   */
  protected function getClassConstant(string $className, string $constantName): mixed {
    return (new \ReflectionClass($className))->getConstant($constantName);
  }

}
