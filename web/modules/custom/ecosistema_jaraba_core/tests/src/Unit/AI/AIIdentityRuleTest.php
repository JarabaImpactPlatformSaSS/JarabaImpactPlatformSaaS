<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\AI;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use PHPUnit\Framework\TestCase;

/**
 * Tests AIIdentityRule static utility.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\AI\AIIdentityRule
 */
class AIIdentityRuleTest extends TestCase {

  /**
   * Tests that apply() with short=FALSE prepends the full identity prompt.
   */
  public function testApplyLong(): void {
    $result = AIIdentityRule::apply('My prompt');
    $this->assertStringStartsWith(AIIdentityRule::IDENTITY_PROMPT, $result);
  }

  /**
   * Tests that apply() with short=TRUE prepends the short identity prompt.
   */
  public function testApplyShort(): void {
    $result = AIIdentityRule::apply('My prompt', TRUE);
    $this->assertStringStartsWith(AIIdentityRule::IDENTITY_PROMPT_SHORT, $result);
  }

  /**
   * Tests that apply() preserves the original prompt text in the output.
   */
  public function testApplyPreservesOriginalPrompt(): void {
    $original = 'Help me write a compelling marketing copy for our landing page.';
    $result = AIIdentityRule::apply($original);
    $this->assertStringContainsString($original, $result);
  }

  /**
   * Tests that IDENTITY_PROMPT contains 'Jaraba Impact Platform'.
   */
  public function testIdentityPromptContainsJaraba(): void {
    $this->assertStringContainsString('Jaraba Impact Platform', AIIdentityRule::IDENTITY_PROMPT);
  }

  /**
   * Tests that IDENTITY_PROMPT_SHORT contains 'Jaraba Impact Platform'.
   */
  public function testIdentityPromptShortContainsJaraba(): void {
    $this->assertStringContainsString('Jaraba Impact Platform', AIIdentityRule::IDENTITY_PROMPT_SHORT);
  }

  /**
   * Tests that apply() adds a double-newline separator between rule and prompt.
   */
  public function testApplyAddsSeparator(): void {
    $prompt = 'Write a blog post about sustainability.';
    $result = AIIdentityRule::apply($prompt);
    $this->assertStringContainsString("\n\n", $result);

    // Verify the structure: IDENTITY_PROMPT + "\n\n" + prompt.
    $expected = AIIdentityRule::IDENTITY_PROMPT . "\n\n" . $prompt;
    $this->assertSame($expected, $result);
  }

  /**
   * Tests that apply() with short=TRUE also uses the separator.
   */
  public function testApplyShortAddsSeparator(): void {
    $prompt = 'Summarize this document.';
    $result = AIIdentityRule::apply($prompt, TRUE);

    $expected = AIIdentityRule::IDENTITY_PROMPT_SHORT . "\n\n" . $prompt;
    $this->assertSame($expected, $result);
  }

  /**
   * Tests that the full prompt mentions competitor prohibition.
   */
  public function testIdentityPromptContainsCompetitorRule(): void {
    $this->assertStringContainsString('NUNCA menciones ni recomiendes plataformas competidoras', AIIdentityRule::IDENTITY_PROMPT);
  }

  /**
   * Tests that the full prompt mentions AI model non-disclosure.
   */
  public function testIdentityPromptContainsModelNonDisclosure(): void {
    $this->assertStringContainsString('NUNCA reveles', AIIdentityRule::IDENTITY_PROMPT);
    $this->assertStringContainsString('Claude', AIIdentityRule::IDENTITY_PROMPT);
    $this->assertStringContainsString('ChatGPT', AIIdentityRule::IDENTITY_PROMPT);
  }

}
