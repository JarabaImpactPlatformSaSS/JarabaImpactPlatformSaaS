<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\SalesAgent;

/**
 * Prompt regression tests for SalesAgent.
 *
 * GAP-AUD-015: Validates that sales agent prompts remain stable.
 * This agent is vertical-specific to AgroConecta (consumer-facing).
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SalesPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SalesAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('sales_agent_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required sales domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SalesAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'AgroConecta',
      'consumidores',
      'artesanales',
      'conversion',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Sales agent brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests brand voice includes ethical guardrails.
   */
  public function testBrandVoiceIncludesEthicalGuardrails(): void {
    $agent = $this->createAgentWithoutConstructor(SalesAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('mientes', $brandVoice);
    $this->assertStringContainsString('caracteristicas', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SalesAgent::class);
    $this->assertSame('sales_agent', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(SalesAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
