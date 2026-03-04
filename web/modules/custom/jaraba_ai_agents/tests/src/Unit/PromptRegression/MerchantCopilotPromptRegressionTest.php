<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\MerchantCopilotAgent;

/**
 * Prompt regression tests for MerchantCopilotAgent.
 *
 * GAP-AUD-015: Validates that merchant copilot prompts remain stable.
 * This agent is vertical-specific to ComercioConecta.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class MerchantCopilotPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(MerchantCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('merchant_copilot_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required ComercioConecta domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(MerchantCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'ComercioConecta',
      'comercios locales',
      'proximidad',
      'ventas',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Merchant copilot brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests brand voice includes guardrails for product accuracy.
   */
  public function testBrandVoiceIncludesAccuracyGuardrails(): void {
    $agent = $this->createAgentWithoutConstructor(MerchantCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('catalogo', $brandVoice);
    $this->assertStringContainsString('call-to-action', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(MerchantCopilotAgent::class);
    $this->assertSame('merchant_copilot', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(MerchantCopilotAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
