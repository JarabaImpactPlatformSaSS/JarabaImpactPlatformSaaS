<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_legal_intelligence\Agent\SmartLegalCopilotAgent;

/**
 * Prompt regression tests for SmartLegalCopilotAgent.
 *
 * GAP-AUD-015: Validates that legal copilot prompts remain stable.
 * This agent has 8 operating modes with dedicated legal prompts.
 * Critical: legal prompts MUST include disclaimer and source citation rules.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class SmartLegalCopilotPromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(SmartLegalCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('smart_legal_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required legal domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(SmartLegalCopilotAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'fuentes verificables',
      'disclaimer',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "Legal brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests all 8 mode prompts remain stable via golden fixtures.
   */
  public function testModePromptsStability(): void {
    $modePrompts = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'MODE_PROMPTS'
    );

    $this->assertNotFalse($modePrompts, 'MODE_PROMPTS constant must exist.');
    $this->assertIsArray($modePrompts);

    $expectedModes = [
      'legal_search',
      'legal_analysis',
      'legal_alerts',
      'legal_citations',
      'legal_eu',
      'case_assistant',
      'document_drafter',
      'faq',
    ];

    foreach ($expectedModes as $mode) {
      $this->assertArrayHasKey($mode, $modePrompts, "Mode '{$mode}' must exist in MODE_PROMPTS.");
      $this->assertPromptMatchesGolden(
        "smart_legal_mode_{$mode}.txt",
        $modePrompts[$mode]
      );
    }
  }

  /**
   * Tests that all 8 modes are defined in MODES constant.
   */
  public function testModesConstantCompleteness(): void {
    $modes = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'MODES'
    );

    $this->assertNotFalse($modes, 'MODES constant must exist.');
    $this->assertIsArray($modes);
    $this->assertCount(8, $modes, 'Exactly 8 modes must be defined.');
  }

  /**
   * Tests fast and premium mode classification.
   */
  public function testTierClassification(): void {
    $fastModes = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'FAST_MODES'
    );
    $premiumModes = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'PREMIUM_MODES'
    );

    $this->assertContains('faq', $fastModes);
    $this->assertContains('legal_alerts', $fastModes);
    $this->assertContains('legal_citations', $fastModes);

    $this->assertContains('legal_analysis', $premiumModes);
    $this->assertContains('legal_eu', $premiumModes);
    $this->assertContains('document_drafter', $premiumModes);
  }

  /**
   * Tests document drafter mode includes disclaimer requirement.
   */
  public function testDocumentDrafterIncludesDisclaimer(): void {
    $modePrompts = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'MODE_PROMPTS'
    );

    $this->assertStringContainsString(
      'disclaimer',
      $modePrompts['document_drafter'],
      'Document drafter mode MUST require a disclaimer in drafts.'
    );
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(SmartLegalCopilotAgent::class);
    $this->assertSame('smart_legal_copilot', $agent->getAgentId());
  }

  /**
   * Tests no PII patterns in any mode prompt.
   */
  public function testModePromptsNoPii(): void {
    $modePrompts = $this->getClassConstant(
      SmartLegalCopilotAgent::class,
      'MODE_PROMPTS'
    );

    foreach ($modePrompts as $mode => $prompt) {
      $this->assertNoPiiPatterns($prompt);
    }
  }

}
