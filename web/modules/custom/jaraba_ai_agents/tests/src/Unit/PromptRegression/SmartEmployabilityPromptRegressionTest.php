<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_candidate\Agent\SmartEmployabilityCopilotAgent;

/**
 * Prompt regression tests for SmartEmployabilityCopilotAgent.
 *
 * GAP-AUD-015: Validates that employability copilot prompts remain stable.
 * This agent has 6 operating modes with dedicated prompts per mode.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SmartEmployabilityPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SmartEmployabilityCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('smart_employability_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required employability domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SmartEmployabilityCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'empleabilidad',
      'empleo',
      'carreras',
      'accionables',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Employability brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests all 6 mode prompts remain stable via golden fixtures.
   */
  public function testModePromptsStability(): void {
    $modePrompts = $this->getClassConstant(
      SmartEmployabilityCopilotAgent::class,
      'MODE_PROMPTS'
    );

    $this->assertNotFalse($modePrompts, 'MODE_PROMPTS constant must exist.');
    $this->assertIsArray($modePrompts);

    $expectedModes = [
      'profile_coach',
      'job_advisor',
      'interview_prep',
      'learning_guide',
      'application_helper',
      'faq',
    ];

    foreach ($expectedModes as $mode) {
      $this->assertArrayHasKey($mode, $modePrompts, "Mode '{$mode}' must exist in MODE_PROMPTS.");
      $this->assertPromptMatchesGolden(
        "smart_employability_mode_{$mode}.txt",
        $modePrompts[$mode]
      );
    }
  }

  /**
   * Tests that all 6 modes are defined in MODES constant.
   */
  public function testModesConstantCompleteness(): void {
    $modes = $this->getClassConstant(
      SmartEmployabilityCopilotAgent::class,
      'MODES'
    );

    $this->assertNotFalse($modes, 'MODES constant must exist.');
    $this->assertIsArray($modes);
    $this->assertCount(6, $modes, 'Exactly 6 modes must be defined.');

    $expectedKeys = [
      'profile_coach',
      'job_advisor',
      'interview_prep',
      'learning_guide',
      'application_helper',
      'faq',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $modes, "Mode '{$key}' must exist in MODES.");
    }
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SmartEmployabilityCopilotAgent::class);
    $this->assertSame('smart_employability_copilot', $agent->getAgentId());
  }

  /**
   * Tests no PII patterns in any mode prompt.
   */
  public function testModePromptsNoPii(): void {
    $modePrompts = $this->getClassConstant(
      SmartEmployabilityCopilotAgent::class,
      'MODE_PROMPTS'
    );

    foreach ($modePrompts as $mode => $prompt) {
      $this->assertNoPiiPatterns($prompt);
    }
  }

}
