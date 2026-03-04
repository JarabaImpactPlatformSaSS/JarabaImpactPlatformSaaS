<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\SmartMarketingAgent;

/**
 * Prompt regression tests for SmartMarketingAgent.
 *
 * GAP-AUD-015: Validates that marketing agent prompts remain stable.
 * Tests brand voice, agent identity, and absence of PII in prompts.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SmartMarketingPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SmartMarketingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('smart_marketing_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required marketing domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SmartMarketingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('marketing digital', $brandVoice);
    $this->assertStringContainsString('resultados', $brandVoice);
    $this->assertStringContainsString('profesional', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SmartMarketingAgent::class);
    $this->assertSame('smart_marketing', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(SmartMarketingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertNoPiiPatterns($brandVoice);
  }

}
