<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_lms\Agent\LearningPathAgent;

/**
 * Prompt regression tests for LearningPathAgent.
 *
 * GAP-AUD-015: Validates that learning path agent prompts remain stable.
 * This agent guides student learning — prompt changes affect pedagogy.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class LearningPathPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(LearningPathAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('learning_path_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required pedagogy domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(LearningPathAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'tutor',
      'aprendizaje',
      'motivador',
      'ejemplos practicos',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Learning path brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(LearningPathAgent::class);
    $this->assertSame('learning_path', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(LearningPathAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
