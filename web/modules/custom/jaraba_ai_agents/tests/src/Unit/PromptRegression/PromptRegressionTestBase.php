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
abstract class PromptRegressionTestBase extends TestCase
{

    /**
     * Returns the path to the golden fixtures directory.
     */
    protected function getFixturesPath(): string
    {
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
    protected function assertPromptMatchesGolden(string $fixtureName, string $actualPrompt): void
    {
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
    protected function normalizePrompt(string $prompt): string
    {
        return preg_replace('/\s+/', ' ', trim($prompt));
    }

}
