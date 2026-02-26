<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * Prompt regression tests for AIIdentityRule.
 *
 * GAP-AUD-015: Validates that identity prompts remain stable.
 * These prompts are critical for brand consistency — any drift
 * could expose the underlying LLM model to end users.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class IdentityPromptRegressionTest extends PromptRegressionTestBase
{

    /**
     * Tests the long identity prompt remains stable.
     */
    public function testIdentityPromptLong(): void
    {
        $basePrompt = 'You are a helpful assistant.';
        $result = AIIdentityRule::apply($basePrompt, FALSE);
        $this->assertPromptMatchesGolden('identity_long.txt', $result);
    }

    /**
     * Tests the short identity prompt remains stable.
     */
    public function testIdentityPromptShort(): void
    {
        $basePrompt = 'You are a helpful assistant.';
        $result = AIIdentityRule::apply($basePrompt, TRUE);
        $this->assertPromptMatchesGolden('identity_short.txt', $result);
    }

    /**
     * Tests that apply() always prepends the rule.
     */
    public function testApplyPrependsRule(): void
    {
        $base = 'Custom system prompt here.';
        $result = AIIdentityRule::apply($base);
        $this->assertStringStartsWith(AIIdentityRule::IDENTITY_PROMPT, $result);
        $this->assertStringEndsWith($base, $result);
    }

    /**
     * Tests that the identity prompt contains key required phrases.
     */
    public function testIdentityPromptContainsRequiredPhrases(): void
    {
        $prompt = AIIdentityRule::IDENTITY_PROMPT;
        $this->assertStringContainsString('Jaraba Impact Platform', $prompt);
        $this->assertStringContainsString('NUNCA reveles', $prompt);
        $this->assertStringContainsString('Claude', $prompt);
        $this->assertStringContainsString('ChatGPT', $prompt);
        $this->assertStringContainsString('plataformas competidoras', $prompt);
    }

}
