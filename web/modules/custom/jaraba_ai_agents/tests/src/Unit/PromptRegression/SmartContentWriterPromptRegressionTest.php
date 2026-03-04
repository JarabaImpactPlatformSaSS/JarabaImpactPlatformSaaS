<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_content_hub\Agent\SmartContentWriterAgent;

/**
 * Prompt regression tests for SmartContentWriterAgent.
 *
 * GAP-AUD-015: Validates that content writer agent prompts remain stable.
 * This agent generates articles — prompt changes directly affect SEO.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SmartContentWriterPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SmartContentWriterAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('smart_content_writer_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required content writing domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SmartContentWriterAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'escritor de contenido',
      'SEO',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Content writer brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SmartContentWriterAgent::class);
    $this->assertSame('smart_content_writer', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(SmartContentWriterAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
