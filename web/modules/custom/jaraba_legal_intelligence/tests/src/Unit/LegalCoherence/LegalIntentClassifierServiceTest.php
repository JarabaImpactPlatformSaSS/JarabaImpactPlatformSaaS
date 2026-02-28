<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalIntentClassifierService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for LegalIntentClassifierService (LCIS Layer 2).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalIntentClassifierService
 * @group jaraba_legal_intelligence
 */
class LegalIntentClassifierServiceTest extends TestCase {

  protected LegalIntentClassifierService $classifier;

  protected function setUp(): void {
    parent::setUp();
    $this->classifier = new LegalIntentClassifierService(new NullLogger());
  }

  // ---------------------------------------------------------------
  // Shortcircuits.
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testShortcircuitLegalAction(): void {
    $result = $this->classifier->classify('cualquier texto', '', 'legal_search');
    $this->assertSame(LegalIntentClassifierService::INTENT_LEGAL_DIRECT, $result['intent']);
    $this->assertSame(1.0, $result['score']);
    $this->assertContains('legal_search', $result['areas']);
  }

  /**
   * @covers ::classify
   */
  public function testShortcircuitFiscalAction(): void {
    $result = $this->classifier->classify('cualquier texto', '', 'fiscal');
    $this->assertSame(LegalIntentClassifierService::INTENT_LEGAL_DIRECT, $result['intent']);
    $this->assertSame(1.0, $result['score']);
  }

  /**
   * @covers ::classify
   */
  public function testShortcircuitLaboralAction(): void {
    $result = $this->classifier->classify('cualquier texto', '', 'laboral');
    $this->assertSame(LegalIntentClassifierService::INTENT_LEGAL_DIRECT, $result['intent']);
  }

  /**
   * @covers ::classify
   */
  public function testShortcircuitJarabalexVertical(): void {
    $result = $this->classifier->classify('dame recetas de cocina', 'jarabalex', '');
    $this->assertSame(LegalIntentClassifierService::INTENT_LEGAL_DIRECT, $result['intent']);
    $this->assertSame(1.0, $result['score']);
    $this->assertContains('jarabalex', $result['areas']);
  }

  // ---------------------------------------------------------------
  // LEGAL_DIRECT (keyword scoring >= 0.85).
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testLegalDirectMultipleKeywords(): void {
    $result = $this->classifier->classify(
      'Quiero recurso contencioso ante el tribunal por la ley organica de derechos fundamentales y la sentencia del recurso',
    );
    $this->assertSame(LegalIntentClassifierService::INTENT_LEGAL_DIRECT, $result['intent']);
    $this->assertGreaterThanOrEqual(0.85, $result['score']);
  }

  // ---------------------------------------------------------------
  // NON_LEGAL (keyword scoring < 0.15, no compliance).
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testNonLegalPureQuery(): void {
    $result = $this->classifier->classify('dame la receta de cocina de un buen gazpacho', '', '');
    $this->assertSame(LegalIntentClassifierService::INTENT_NON_LEGAL, $result['intent']);
    $this->assertEmpty($result['areas']);
  }

  /**
   * @covers ::classify
   */
  public function testNonLegalPriceQuery(): void {
    $result = $this->classifier->classify('cual es el mejor precio para un telefono', '', '');
    $this->assertSame(LegalIntentClassifierService::INTENT_NON_LEGAL, $result['intent']);
  }

  // ---------------------------------------------------------------
  // COMPLIANCE_CHECK.
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testComplianceCheckRGPD(): void {
    $result = $this->classifier->classify(
      'Necesito verificar el cumplimiento RGPD conforme a la normativa obligatoria legal de compliance',
    );
    $this->assertContains($result['intent'], [
      LegalIntentClassifierService::INTENT_COMPLIANCE_CHECK,
      LegalIntentClassifierService::INTENT_LEGAL_DIRECT,
    ]);
  }

  // ---------------------------------------------------------------
  // LEGAL_IMPLICIT (2+ keywords but score < 0.85).
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testLegalImplicitTwoKeywords(): void {
    $result = $this->classifier->classify('el contrato tiene clausula abusiva', '', '');
    $this->assertContains($result['intent'], [
      LegalIntentClassifierService::INTENT_LEGAL_IMPLICIT,
      LegalIntentClassifierService::INTENT_LEGAL_DIRECT,
    ]);
  }

  // ---------------------------------------------------------------
  // LEGAL_REFERENCE (1 keyword or area detected).
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testLegalReferenceSingleKeyword(): void {
    $result = $this->classifier->classify('necesito informacion sobre mi contrato', '', '');
    $this->assertContains($result['intent'], [
      LegalIntentClassifierService::INTENT_LEGAL_REFERENCE,
      LegalIntentClassifierService::INTENT_LEGAL_IMPLICIT,
    ]);
  }

  // ---------------------------------------------------------------
  // Vertical bonus.
  // ---------------------------------------------------------------

  /**
   * @covers ::classify
   */
  public function testVerticalBonusEmpleabilidad(): void {
    $result = $this->classifier->classify('despido improcedente convenio colectivo', 'empleabilidad', '');
    $this->assertContains($result['intent'], [
      LegalIntentClassifierService::INTENT_LEGAL_DIRECT,
      LegalIntentClassifierService::INTENT_LEGAL_IMPLICIT,
      LegalIntentClassifierService::INTENT_LEGAL_REFERENCE,
    ]);
    // Vertical bonus should push score above baseline.
    $this->assertGreaterThanOrEqual(0.2, $result['score']);
  }

  /**
   * @covers ::classify
   */
  public function testVerticalBonusAgroconecta(): void {
    $result = $this->classifier->classify('normativa de trazabilidad y etiquetado PAC', 'agroconecta', '');
    $this->assertContains($result['intent'], [
      LegalIntentClassifierService::INTENT_LEGAL_DIRECT,
      LegalIntentClassifierService::INTENT_LEGAL_IMPLICIT,
    ]);
  }

  // ---------------------------------------------------------------
  // Pipeline helpers.
  // ---------------------------------------------------------------

  /**
   * @covers ::requiresFullPipeline
   */
  public function testRequiresFullPipelineLegalDirect(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_DIRECT, 'score' => 1.0, 'areas' => []];
    $this->assertTrue(LegalIntentClassifierService::requiresFullPipeline($classification));
  }

  /**
   * @covers ::requiresFullPipeline
   */
  public function testRequiresFullPipelineComplianceCheck(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_COMPLIANCE_CHECK, 'score' => 0.8, 'areas' => []];
    $this->assertTrue(LegalIntentClassifierService::requiresFullPipeline($classification));
  }

  /**
   * @covers ::requiresFullPipeline
   */
  public function testDoesNotRequireFullPipelineNonLegal(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_NON_LEGAL, 'score' => 0.9, 'areas' => []];
    $this->assertFalse(LegalIntentClassifierService::requiresFullPipeline($classification));
  }

  /**
   * @covers ::requiresFullPipeline
   */
  public function testDoesNotRequireFullPipelineLegalReference(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_REFERENCE, 'score' => 0.3, 'areas' => []];
    $this->assertFalse(LegalIntentClassifierService::requiresFullPipeline($classification));
  }

  /**
   * @covers ::requiresDisclaimer
   */
  public function testRequiresDisclaimerLegalDirect(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_DIRECT];
    $this->assertTrue(LegalIntentClassifierService::requiresDisclaimer($classification));
  }

  /**
   * @covers ::requiresDisclaimer
   */
  public function testRequiresDisclaimerLegalReference(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_REFERENCE];
    $this->assertTrue(LegalIntentClassifierService::requiresDisclaimer($classification));
  }

  /**
   * @covers ::requiresDisclaimer
   */
  public function testNoDisclaimerNonLegal(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_NON_LEGAL];
    $this->assertFalse(LegalIntentClassifierService::requiresDisclaimer($classification));
  }

  /**
   * @covers ::isLightPipeline
   */
  public function testIsLightPipelineLegalReference(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_REFERENCE];
    $this->assertTrue(LegalIntentClassifierService::isLightPipeline($classification));
  }

  /**
   * @covers ::isLightPipeline
   */
  public function testNotLightPipelineLegalDirect(): void {
    $classification = ['intent' => LegalIntentClassifierService::INTENT_LEGAL_DIRECT];
    $this->assertFalse(LegalIntentClassifierService::isLightPipeline($classification));
  }

  // ---------------------------------------------------------------
  // Constants integrity.
  // ---------------------------------------------------------------

  /**
   * Tests that all 5 intent constants are defined.
   */
  public function testFiveIntentConstants(): void {
    $intents = [
      LegalIntentClassifierService::INTENT_LEGAL_DIRECT,
      LegalIntentClassifierService::INTENT_LEGAL_IMPLICIT,
      LegalIntentClassifierService::INTENT_LEGAL_REFERENCE,
      LegalIntentClassifierService::INTENT_COMPLIANCE_CHECK,
      LegalIntentClassifierService::INTENT_NON_LEGAL,
    ];
    $this->assertCount(5, array_unique($intents));
  }

  /**
   * Tests classify return structure.
   */
  public function testClassifyReturnStructure(): void {
    $result = $this->classifier->classify('test', '', '');
    $this->assertArrayHasKey('intent', $result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('areas', $result);
    $this->assertIsFloat($result['score']);
    $this->assertIsArray($result['areas']);
  }

  /**
   * Tests score is clamped to [0.0, 1.0].
   */
  public function testScoreClampedWithManyKeywords(): void {
    $query = 'ley decreto reglamento normativa legislacion recurso demanda sancion tribunal derecho obligacion contrato';
    $result = $this->classifier->classify($query, '', '');
    $this->assertLessThanOrEqual(1.0, $result['score']);
    $this->assertGreaterThanOrEqual(0.0, $result['score']);
  }

}
