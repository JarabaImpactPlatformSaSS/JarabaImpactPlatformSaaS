<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\ProducerCopilotAgent;

/**
 * Prompt regression tests for ProducerCopilotAgent.
 *
 * GAP-AUD-015: Validates that producer copilot prompts remain stable.
 * This agent is vertical-specific to AgroConecta (producer-facing).
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class ProducerCopilotPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(ProducerCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('producer_copilot_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required AgroConecta domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(ProducerCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'AgroConecta',
      'productores',
      'trazabilidad',
      'sostenibilidad',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Producer copilot brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests brand voice includes ethical guardrails.
   */
  public function testBrandVoiceIncludesEthicalGuardrails(): void {
    $agent = $this->createAgentWithoutConstructor(ProducerCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('deshonestas', $brandVoice);
    $this->assertStringContainsString('precios depredadores', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(ProducerCopilotAgent::class);
    $this->assertSame('producer_copilot', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(ProducerCopilotAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
