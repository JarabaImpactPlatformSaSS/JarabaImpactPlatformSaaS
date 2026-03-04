<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\SupportAgent;

/**
 * Prompt regression tests for SupportAgent.
 *
 * GAP-AUD-015: Validates that support agent prompts remain stable.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SupportPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SupportAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('support_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required support domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SupportAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'soporte',
      'paciente',
      'soluciones',
      'primer contacto',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Support brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SupportAgent::class);
    $this->assertSame('support', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(SupportAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
