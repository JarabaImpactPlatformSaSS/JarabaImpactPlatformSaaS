<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LegalCoherencePromptRule (LCIS Layer 4).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherencePromptRule
 * @group jaraba_legal_intelligence
 */
class LegalCoherencePromptRuleTest extends TestCase {

  // ---------------------------------------------------------------
  // apply() — full prompt.
  // ---------------------------------------------------------------

  /**
   * @covers ::apply
   */
  public function testApplyPrependsFullPrompt(): void {
    $original = 'You are a legal assistant.';
    $result = LegalCoherencePromptRule::apply($original);

    $this->assertStringStartsWith('## REGLAS DE COHERENCIA JURIDICA', $result);
    $this->assertStringEndsWith($original, $result);
    $this->assertStringContainsString('JERARQUIA NORMATIVA', $result);
  }

  /**
   * @covers ::apply
   */
  public function testApplyContainsAllEightRules(): void {
    $result = LegalCoherencePromptRule::apply('test');

    $this->assertStringContainsString('### R1. JERARQUIA NORMATIVA', $result);
    $this->assertStringContainsString('### R2. PRIMACIA DEL DERECHO UE', $result);
    $this->assertStringContainsString('### R3. COMPETENCIAS ESTADO vs. CCAA', $result);
    $this->assertStringContainsString('### R4. RESERVA DE LEY ORGANICA', $result);
    $this->assertStringContainsString('### R5. IRRETROACTIVIDAD', $result);
    $this->assertStringContainsString('### R6. VIGENCIA Y DEROGACION', $result);
    $this->assertStringContainsString('### R7. CONSISTENCIA TRANSVERSAL', $result);
    $this->assertStringContainsString('### R8. HUMILDAD JURIDICA', $result);
  }

  // ---------------------------------------------------------------
  // apply() — short prompt.
  // ---------------------------------------------------------------

  /**
   * @covers ::apply
   */
  public function testApplyShortPrependsCompactPrompt(): void {
    $original = 'Original prompt.';
    $result = LegalCoherencePromptRule::apply($original, TRUE);

    $this->assertStringStartsWith('## COHERENCIA JURIDICA (OBLIGATORIA)', $result);
    $this->assertStringEndsWith($original, $result);
    $this->assertStringNotContainsString('### R1. JERARQUIA NORMATIVA', $result);
  }

  /**
   * @covers ::apply
   */
  public function testApplyShortContainsSevenRules(): void {
    $result = LegalCoherencePromptRule::apply('test', TRUE);

    $this->assertStringContainsString('1. JERARQUIA:', $result);
    $this->assertStringContainsString('2. PRIMACIA UE:', $result);
    $this->assertStringContainsString('3. COMPETENCIAS:', $result);
    $this->assertStringContainsString('4. LO:', $result);
    $this->assertStringContainsString('5. IRRETROACTIVIDAD:', $result);
    $this->assertStringContainsString('6. VIGENCIA:', $result);
    $this->assertStringContainsString('7. CONSISTENCIA:', $result);
  }

  /**
   * @covers ::apply
   */
  public function testApplyShortIsShorterThanFull(): void {
    $full = LegalCoherencePromptRule::apply('test', FALSE);
    $short = LegalCoherencePromptRule::apply('test', TRUE);

    $this->assertLessThan(strlen($full), strlen($short));
  }

  // ---------------------------------------------------------------
  // applyWithTerritory() — sin territorio.
  // ---------------------------------------------------------------

  /**
   * @covers ::applyWithTerritory
   */
  public function testApplyWithTerritoryNoTerritory(): void {
    $result = LegalCoherencePromptRule::applyWithTerritory('test prompt');

    $this->assertStringContainsString('REGLAS DE COHERENCIA JURIDICA', $result);
    $this->assertStringNotContainsString('CONTEXTO TERRITORIAL', $result);
    $this->assertStringEndsWith('test prompt', $result);
  }

  // ---------------------------------------------------------------
  // applyWithTerritory() — con territorio sin derecho foral.
  // ---------------------------------------------------------------

  /**
   * @covers ::applyWithTerritory
   */
  public function testApplyWithTerritoryAndalucia(): void {
    $result = LegalCoherencePromptRule::applyWithTerritory('test', 'Andalucia');

    $this->assertStringContainsString('### R9. CONTEXTO TERRITORIAL', $result);
    $this->assertStringContainsString('Andalucia', $result);
    $this->assertStringNotContainsString('NOTA FORAL', $result);
  }

  // ---------------------------------------------------------------
  // applyWithTerritory() — con territorio con derecho foral.
  // ---------------------------------------------------------------

  /**
   * @covers ::applyWithTerritory
   */
  public function testApplyWithTerritoryCataluna(): void {
    $result = LegalCoherencePromptRule::applyWithTerritory('test', 'Cataluna');

    $this->assertStringContainsString('### R9. CONTEXTO TERRITORIAL', $result);
    $this->assertStringContainsString('Cataluna', $result);
    $this->assertStringContainsString('NOTA FORAL', $result);
    $this->assertStringContainsString('Codi Civil de Catalunya', $result);
  }

  /**
   * @covers ::applyWithTerritory
   */
  public function testApplyWithTerritoryAragon(): void {
    $result = LegalCoherencePromptRule::applyWithTerritory('test', 'Aragon');

    $this->assertStringContainsString('NOTA FORAL', $result);
    $this->assertStringContainsString('Aragon', $result);
  }

  /**
   * @covers ::applyWithTerritory
   */
  public function testApplyWithTerritoryShortVersion(): void {
    $result = LegalCoherencePromptRule::applyWithTerritory('test', 'Cataluna', TRUE);

    $this->assertStringContainsString('COHERENCIA JURIDICA (OBLIGATORIA)', $result);
    $this->assertStringContainsString('### R9. CONTEXTO TERRITORIAL', $result);
    $this->assertStringNotContainsString('### R1. JERARQUIA NORMATIVA', $result);
  }

  // ---------------------------------------------------------------
  // requiresCoherence().
  // ---------------------------------------------------------------

  /**
   * @covers ::requiresCoherence
   */
  public function testRequiresCoherenceJarabalexVertical(): void {
    $this->assertTrue(LegalCoherencePromptRule::requiresCoherence('', 'jarabalex'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testRequiresCoherenceLegalSearchAction(): void {
    $this->assertTrue(LegalCoherencePromptRule::requiresCoherence('legal_search'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testRequiresCoherenceFiscalAction(): void {
    $this->assertTrue(LegalCoherencePromptRule::requiresCoherence('fiscal'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testRequiresCoherenceLaboralAction(): void {
    $this->assertTrue(LegalCoherencePromptRule::requiresCoherence('laboral'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testRequiresCoherenceDocumentDrafter(): void {
    $this->assertTrue(LegalCoherencePromptRule::requiresCoherence('document_drafter'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testDoesNotRequireCoherenceGenericAction(): void {
    $this->assertFalse(LegalCoherencePromptRule::requiresCoherence('marketing_copy'));
  }

  /**
   * @covers ::requiresCoherence
   */
  public function testDoesNotRequireCoherenceEmptyAction(): void {
    $this->assertFalse(LegalCoherencePromptRule::requiresCoherence('', 'empleabilidad'));
  }

  // ---------------------------------------------------------------
  // useShortVersion().
  // ---------------------------------------------------------------

  /**
   * @covers ::useShortVersion
   */
  public function testUseShortVersionFaq(): void {
    $this->assertTrue(LegalCoherencePromptRule::useShortVersion('faq'));
  }

  /**
   * @covers ::useShortVersion
   */
  public function testUseShortVersionLegalAlerts(): void {
    $this->assertTrue(LegalCoherencePromptRule::useShortVersion('legal_alerts'));
  }

  /**
   * @covers ::useShortVersion
   */
  public function testUseShortVersionLegalCitations(): void {
    $this->assertTrue(LegalCoherencePromptRule::useShortVersion('legal_citations'));
  }

  /**
   * @covers ::useShortVersion
   */
  public function testNotShortVersionLegalSearch(): void {
    $this->assertFalse(LegalCoherencePromptRule::useShortVersion('legal_search'));
  }

  /**
   * @covers ::useShortVersion
   */
  public function testNotShortVersionFiscal(): void {
    $this->assertFalse(LegalCoherencePromptRule::useShortVersion('fiscal'));
  }

  // ---------------------------------------------------------------
  // Constants.
  // ---------------------------------------------------------------

  /**
   * Tests COHERENCE_PROMPT constant is non-empty.
   */
  public function testCoherencePromptConstantExists(): void {
    $this->assertNotEmpty(LegalCoherencePromptRule::COHERENCE_PROMPT);
    $this->assertIsString(LegalCoherencePromptRule::COHERENCE_PROMPT);
  }

  /**
   * Tests COHERENCE_PROMPT_SHORT constant is non-empty.
   */
  public function testCoherencePromptShortConstantExists(): void {
    $this->assertNotEmpty(LegalCoherencePromptRule::COHERENCE_PROMPT_SHORT);
    $this->assertIsString(LegalCoherencePromptRule::COHERENCE_PROMPT_SHORT);
  }

  /**
   * Tests full prompt references key legal sources.
   */
  public function testFullPromptReferencesKeySources(): void {
    $prompt = LegalCoherencePromptRule::COHERENCE_PROMPT;
    $this->assertStringContainsString('Art. 9.3 CE', $prompt);
    $this->assertStringContainsString('Art. 81 CE', $prompt);
    $this->assertStringContainsString('Costa v. ENEL', $prompt);
    $this->assertStringContainsString('Simmenthal', $prompt);
    $this->assertStringContainsString('Van Gend en Loos', $prompt);
  }

}
