<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceKnowledgeBase;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LegalCoherenceKnowledgeBase (LCIS Layer 1).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceKnowledgeBase
 * @group jaraba_legal_intelligence
 */
class LegalCoherenceKnowledgeBaseTest extends TestCase {

  /**
   * @covers ::isHigherRank
   */
  public function testIsHigherRankConstitutionOverLey(): void {
    $this->assertTrue(LegalCoherenceKnowledgeBase::isHigherRank('constitucion', 'ley_ordinaria'));
  }

  /**
   * @covers ::isHigherRank
   */
  public function testIsHigherRankLeyOrganicaOverReglamento(): void {
    $this->assertTrue(LegalCoherenceKnowledgeBase::isHigherRank('ley_organica', 'reglamento_estatal'));
  }

  /**
   * @covers ::isHigherRank
   */
  public function testReglamentoNotHigherThanLey(): void {
    $this->assertFalse(LegalCoherenceKnowledgeBase::isHigherRank('reglamento_estatal', 'ley_ordinaria'));
  }

  /**
   * @covers ::isHigherRank
   */
  public function testDueOverConstitution(): void {
    $this->assertTrue(LegalCoherenceKnowledgeBase::isHigherRank('derecho_ue_primario', 'constitucion'));
  }

  /**
   * @covers ::isHigherRank
   */
  public function testSameRankNotHigher(): void {
    $this->assertFalse(LegalCoherenceKnowledgeBase::isHigherRank('ley_ordinaria', 'ley_ordinaria'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectLeyOrganica(): void {
    $this->assertSame('ley_organica', LegalCoherenceKnowledgeBase::detectNormRank('Ley Organica 3/2018'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectReglamentoUe(): void {
    $this->assertSame('derecho_ue_derivado', LegalCoherenceKnowledgeBase::detectNormRank('Reglamento (UE) 2016/679'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectRealDecreto(): void {
    $this->assertSame('reglamento_estatal', LegalCoherenceKnowledgeBase::detectNormRank('Real Decreto 123/2024'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectLeyOrdinaria(): void {
    $this->assertSame('ley_ordinaria', LegalCoherenceKnowledgeBase::detectNormRank('Ley 39/2015'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectConstitution(): void {
    $this->assertSame('constitucion', LegalCoherenceKnowledgeBase::detectNormRank('Constitucion Espanola'));
  }

  /**
   * @covers ::detectNormRank
   */
  public function testDetectUnknownNorm(): void {
    $this->assertNull(LegalCoherenceKnowledgeBase::detectNormRank('Un texto sin norma'));
  }

  /**
   * @covers ::isStateExclusiveCompetence
   */
  public function testDetectPenalCompetence(): void {
    $result = LegalCoherenceKnowledgeBase::isStateExclusiveCompetence('legislacion penal');
    $this->assertNotNull($result);
    $this->assertSame('149.1.6', $result['article']);
  }

  /**
   * @covers ::isStateExclusiveCompetence
   */
  public function testDetectLaboralCompetence(): void {
    $result = LegalCoherenceKnowledgeBase::isStateExclusiveCompetence('Estatuto de los Trabajadores y legislacion laboral');
    $this->assertNotNull($result);
  }

  /**
   * @covers ::isStateExclusiveCompetence
   */
  public function testNonExclusiveCompetence(): void {
    $result = LegalCoherenceKnowledgeBase::isStateExclusiveCompetence('urbanismo y ordenacion del territorio');
    $this->assertNull($result);
  }

  /**
   * @covers ::requiresOrganicLaw
   */
  public function testDerechosFundamentalesRequiresLO(): void {
    $result = LegalCoherenceKnowledgeBase::requiresOrganicLaw('regulacion de derechos fundamentales');
    $this->assertNotNull($result);
    $this->assertStringContainsString('derechos fundamentales', $result);
  }

  /**
   * @covers ::requiresOrganicLaw
   */
  public function testTributarioDoesNotRequireLO(): void {
    $result = LegalCoherenceKnowledgeBase::requiresOrganicLaw('regulacion de impuestos y tributos');
    $this->assertNull($result);
  }

  /**
   * @covers ::getHierarchyWeight
   */
  public function testHierarchyWeightConstitution(): void {
    $weight = LegalCoherenceKnowledgeBase::getHierarchyWeight('constitucion');
    $this->assertSame(0.98, $weight);
  }

  /**
   * @covers ::getHierarchyWeight
   */
  public function testHierarchyWeightUnknown(): void {
    $weight = LegalCoherenceKnowledgeBase::getHierarchyWeight('unknown_type');
    $this->assertSame(0.50, $weight);
  }

  /**
   * @covers ::getForalRegime
   */
  public function testForalRegimeCataluna(): void {
    $regime = LegalCoherenceKnowledgeBase::getForalRegime('sucesiones', 'Cataluna');
    $this->assertNotNull($regime);
    $this->assertStringContainsString('Codi Civil de Catalunya', $regime['corpus']);
  }

  /**
   * @covers ::getForalRegime
   */
  public function testNoForalRegimeAndalucia(): void {
    $regime = LegalCoherenceKnowledgeBase::getForalRegime('sucesiones', 'Andalucia');
    $this->assertNull($regime);
  }

  /**
   * Tests that NORMATIVE_HIERARCHY has 9 levels.
   */
  public function testHierarchyHasNineLevels(): void {
    $this->assertCount(9, LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY);
  }

  /**
   * Tests that ranks are sequential 1-9.
   */
  public function testHierarchyRanksSequential(): void {
    $ranks = array_column(LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY, 'rank');
    sort($ranks);
    $this->assertSame(range(1, 9), $ranks);
  }

}
