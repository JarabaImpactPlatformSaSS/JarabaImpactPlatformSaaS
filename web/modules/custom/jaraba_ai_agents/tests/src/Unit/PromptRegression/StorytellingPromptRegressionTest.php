<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\StorytellingAgent;

/**
 * Prompt regression tests for StorytellingAgent.
 *
 * GAP-AUD-015: Validates that storytelling agent prompts remain stable.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class StorytellingPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(StorytellingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('storytelling_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required storytelling domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(StorytellingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'storyteller',
      'narrativa',
      'marca',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Storytelling brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests brand voice includes storytelling principles.
   */
  public function testBrandVoiceIncludesPrinciples(): void {
    $agent = $this->createAgentWithoutConstructor(StorytellingAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('Mostrar', $brandVoice);
    $this->assertStringContainsString('memorabilidad', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(StorytellingAgent::class);
    $this->assertSame('storytelling', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(StorytellingAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
