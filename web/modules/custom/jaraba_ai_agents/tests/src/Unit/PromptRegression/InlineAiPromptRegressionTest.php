<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * Prompt regression tests for Inline AI suggestion prompts.
 *
 * GAP-AUD-015: Validates that Inline AI prompts remain stable.
 * These prompts drive the sparkle button suggestions in entity forms
 * (GAP-AUD-009). Changes could affect suggestion quality.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class InlineAiPromptRegressionTest extends PromptRegressionTestBase
{

    /**
     * Builds the suggestion prompt for a title field.
     *
     * Mirrors InlineAiService::buildSuggestionPrompt() logic.
     */
    private function buildTitlePrompt(string $currentValue): string
    {
        $basePrompt = 'You are an expert content writer for a SaaS platform. '
            . 'Generate 3 alternative suggestions for the "title" field of a "content_article" entity. '
            . 'Current value: "' . $currentValue . '". '
            . 'Return ONLY a JSON array of 3 strings. No explanation. '
            . 'Each suggestion should be compelling, SEO-friendly, and under 70 characters.';

        return AIIdentityRule::apply($basePrompt, TRUE);
    }

    /**
     * Builds the suggestion prompt for a body/description field.
     */
    private function buildBodyPrompt(string $currentValue): string
    {
        $basePrompt = 'You are an expert content writer for a SaaS platform. '
            . 'Generate 3 alternative suggestions for the "seo_description" field of a "content_article" entity. '
            . 'Current value: "' . $currentValue . '". '
            . 'Return ONLY a JSON array of 3 strings. No explanation. '
            . 'Each suggestion should be clear, engaging, and under 160 characters.';

        return AIIdentityRule::apply($basePrompt, TRUE);
    }

    /**
     * Tests inline AI title suggestion prompt.
     */
    public function testInlineAiTitlePrompt(): void
    {
        $prompt = $this->buildTitlePrompt('How to grow your business');
        $this->assertPromptMatchesGolden('inline_ai_title.txt', $prompt);
    }

    /**
     * Tests inline AI body/description suggestion prompt.
     */
    public function testInlineAiBodyPrompt(): void
    {
        $prompt = $this->buildBodyPrompt('Learn the best strategies for SaaS growth');
        $this->assertPromptMatchesGolden('inline_ai_body.txt', $prompt);
    }

    /**
     * Tests that inline AI prompts use the short identity rule.
     */
    public function testInlineAiUsesShortIdentity(): void
    {
        $prompt = $this->buildTitlePrompt('test');
        $this->assertStringContainsString(
            AIIdentityRule::IDENTITY_PROMPT_SHORT,
            $prompt,
            'Inline AI should use SHORT identity rule for token efficiency.'
        );
        $this->assertStringNotContainsString(
            'REGLA DE IDENTIDAD INQUEBRANTABLE',
            $prompt,
            'Inline AI should NOT use the LONG identity rule.'
        );
    }

    /**
     * Tests that inline AI prompts request JSON output.
     */
    public function testInlineAiRequestsJsonOutput(): void
    {
        $prompt = $this->buildTitlePrompt('test');
        $this->assertStringContainsString('JSON array', $prompt);
        $this->assertStringContainsString('3 strings', $prompt);
    }

}
