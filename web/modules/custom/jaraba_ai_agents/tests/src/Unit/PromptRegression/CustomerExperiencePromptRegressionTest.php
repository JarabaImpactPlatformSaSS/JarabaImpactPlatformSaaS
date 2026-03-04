<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\PromptRegression;

use Drupal\jaraba_ai_agents\Agent\CustomerExperienceAgent;

/**
 * Prompt regression tests for CustomerExperienceAgent.
 *
 * GAP-AUD-015: Validates that CX agent prompts remain stable.
 *
 * @group jaraba_ai_agents
 * @group prompt_regression
 */
class CustomerExperiencePromptRegressionTest extends PromptRegressionTestBase {

  /**
   * Tests the brand voice prompt remains stable.
   */
  public function testBrandVoicePrompt(): void {
    $agent = $this->createAgentWithoutConstructor(CustomerExperienceAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertPromptMatchesGolden('customer_experience_brand_voice.txt', $brandVoice);
  }

  /**
   * Tests brand voice contains required CX domain phrases.
   */
  public function testBrandVoiceContainsDomainPhrases(): void {
    $agent = $this->createAgentWithoutConstructor(CustomerExperienceAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $requiredPhrases = [
      'experiencia del cliente',
      'inteligencia emocional',
      'soluciones',
    ];
    foreach ($requiredPhrases as $phrase) {
      $this->assertStringContainsString(
        $phrase,
        $brandVoice,
        "CX brand voice must contain: '{$phrase}'"
      );
    }
  }

  /**
   * Tests brand voice includes empathy principles.
   */
  public function testBrandVoiceIncludesEmpathyPrinciples(): void {
    $agent = $this->createAgentWithoutConstructor(CustomerExperienceAgent::class);
    $brandVoice = $agent->getDefaultBrandVoice();
    $this->assertStringContainsString('Escuchar', $brandVoice);
    $this->assertStringContainsString('quejas en oportunidades', $brandVoice);
  }

  /**
   * Tests agent ID is correct.
   */
  public function testAgentId(): void {
    $agent = $this->createAgentWithoutConstructor(CustomerExperienceAgent::class);
    $this->assertSame('customer_experience', $agent->getAgentId());
  }

  /**
   * Tests brand voice does not contain PII patterns.
   */
  public function testBrandVoiceNoPii(): void {
    $agent = $this->createAgentWithoutConstructor(CustomerExperienceAgent::class);
    $this->assertNoPiiPatterns($agent->getDefaultBrandVoice());
  }

}
