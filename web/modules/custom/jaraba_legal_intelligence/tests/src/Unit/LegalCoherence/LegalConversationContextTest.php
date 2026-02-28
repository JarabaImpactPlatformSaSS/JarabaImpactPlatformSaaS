<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalConversationContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for LegalConversationContext (LCIS multi-turn).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalConversationContext
 * @group jaraba_legal_intelligence
 */
class LegalConversationContextTest extends TestCase {

  protected LegalConversationContext $context;

  protected function setUp(): void {
    parent::setUp();
    $this->context = new LegalConversationContext(new NullLogger());
  }

  // ---------------------------------------------------------------
  // addTurn().
  // ---------------------------------------------------------------

  /**
   * @covers ::addTurn
   * @covers ::getTurnCount
   */
  public function testAddTurnIncrementsTurnCount(): void {
    $this->assertSame(0, $this->context->getTurnCount());

    $this->context->addTurn('query 1', 'output 1');
    $this->assertSame(1, $this->context->getTurnCount());

    $this->context->addTurn('query 2', 'output 2');
    $this->assertSame(2, $this->context->getTurnCount());
  }

  /**
   * @covers ::addTurn
   * @covers ::getAssertions
   */
  public function testAddTurnExtractsAssertions(): void {
    $output = 'La materia penal es competencia exclusiva del Estado segun Art. 149.1 CE.';
    $this->context->addTurn('que dice la ley', $output);

    $assertions = $this->context->getAssertions();
    $this->assertNotEmpty($assertions);
    $this->assertSame(1, $assertions[0]['turn']);
    $this->assertSame('competencia', $assertions[0]['position']);
  }

  /**
   * @covers ::addTurn
   */
  public function testAddTurnExtractsPrimacyAssertion(): void {
    $output = 'En este caso prevalece el Derecho UE sobre la legislacion nacional.';
    $this->context->addTurn('primacia', $output);

    $assertions = $this->context->getAssertions();
    $this->assertNotEmpty($assertions);
    $positions = array_column($assertions, 'position');
    $this->assertContains('primacia', $positions);
  }

  /**
   * @covers ::addTurn
   */
  public function testAddTurnExtractsVigenciaAssertion(): void {
    $output = 'La Ley 30/1992 ha sido derogada por la Ley 39/2015.';
    $this->context->addTurn('vigencia', $output);

    $assertions = $this->context->getAssertions();
    $this->assertNotEmpty($assertions);
    $positions = array_column($assertions, 'position');
    $this->assertContains('vigencia', $positions);
  }

  /**
   * @covers ::addTurn
   */
  public function testAddTurnExtractsReservaLoAssertion(): void {
    $output = 'Esta materia requiere Ley Organica segun el Art. 81 CE.';
    $this->context->addTurn('reserva', $output);

    $assertions = $this->context->getAssertions();
    $positions = array_column($assertions, 'position');
    $this->assertContains('reserva_lo', $positions);
  }

  /**
   * @covers ::addTurn
   */
  public function testAddTurnDoesNotExtractFromNonLegalOutput(): void {
    $output = 'El tiempo en Sevilla manana sera soleado con 28 grados.';
    $this->context->addTurn('que tiempo hace', $output);

    $assertions = $this->context->getAssertions();
    $this->assertEmpty($assertions);
  }

  /**
   * @covers ::addTurn
   */
  public function testTurnsLimitedToMax(): void {
    // MAX_TURNS = 20. Add 25 turns.
    for ($i = 1; $i <= 25; $i++) {
      $this->context->addTurn("query {$i}", "output {$i}");
    }

    $this->assertSame(25, $this->context->getTurnCount());
    // Internal turns array should be trimmed to 20.
  }

  // ---------------------------------------------------------------
  // checkCrossTurnCoherence().
  // ---------------------------------------------------------------

  /**
   * @covers ::checkCrossTurnCoherence
   */
  public function testCoherenceWithNoAssertions(): void {
    $result = $this->context->checkCrossTurnCoherence('Some output');

    $this->assertTrue($result['is_coherent']);
    $this->assertEmpty($result['contradictions']);
  }

  /**
   * @covers ::checkCrossTurnCoherence
   */
  public function testDetectsCompetenceContradiction(): void {
    // Turn 1: assert exclusive state competence.
    $output1 = 'La legislacion penal es competencia exclusiva del Estado segun Art. 149.1.6 CE.';
    $this->context->addTurn('competencia penal', $output1);

    // Turn 2: contradicts.
    $output2 = 'Las CCAA pueden legislar en materia penal cuando lo consideren oportuno.';
    $result = $this->context->checkCrossTurnCoherence($output2);

    $this->assertFalse($result['is_coherent']);
    $this->assertNotEmpty($result['contradictions']);
    $this->assertSame('competencia', $result['contradictions'][0]['contradiction_type']);
  }

  /**
   * @covers ::checkCrossTurnCoherence
   */
  public function testDetectsRetroactivityContradiction(): void {
    $output1 = 'Las sanciones administrativas no tiene efecto retroactivo segun Art. 9.3 CE.';
    $this->context->addTurn('retroactividad', $output1);

    $output2 = 'En este caso, la sancion se aplica retroactivamente al periodo anterior.';
    $result = $this->context->checkCrossTurnCoherence($output2);

    $this->assertFalse($result['is_coherent']);
    $contradictionTypes = array_column($result['contradictions'], 'contradiction_type');
    $this->assertContains('retroactividad', $contradictionTypes);
  }

  /**
   * @covers ::checkCrossTurnCoherence
   */
  public function testDetectsVigenciaContradiction(): void {
    $output1 = 'La Ley 30/1992 fue derogada por la Ley 39/2015.';
    $this->context->addTurn('vigencia', $output1);

    $output2 = 'La Ley 30/1992 sigue vigente y se aplica a este procedimiento.';
    $result = $this->context->checkCrossTurnCoherence($output2);

    $this->assertFalse($result['is_coherent']);
    $contradictionTypes = array_column($result['contradictions'], 'contradiction_type');
    $this->assertContains('vigencia', $contradictionTypes);
  }

  /**
   * @covers ::checkCrossTurnCoherence
   */
  public function testNoContradictionWithConsistentTurns(): void {
    $output1 = 'La legislacion penal es competencia exclusiva del Estado segun Art. 149.1.6 CE.';
    $this->context->addTurn('competencia penal', $output1);

    $output2 = 'El Estado tiene competencia exclusiva en materia penal, como establece el Art. 149 CE.';
    $result = $this->context->checkCrossTurnCoherence($output2);

    $this->assertTrue($result['is_coherent']);
    $this->assertEmpty($result['contradictions']);
  }

  // ---------------------------------------------------------------
  // reset().
  // ---------------------------------------------------------------

  /**
   * @covers ::reset
   */
  public function testResetClearsEverything(): void {
    $this->context->addTurn('q1', 'La materia penal es competencia exclusiva del Estado.');
    $this->context->addTurn('q2', 'output 2');

    $this->assertSame(2, $this->context->getTurnCount());
    $this->assertNotEmpty($this->context->getAssertions());

    $this->context->reset();

    $this->assertSame(0, $this->context->getTurnCount());
    $this->assertEmpty($this->context->getAssertions());
  }

  /**
   * @covers ::reset
   */
  public function testResetAllowsFreshStart(): void {
    $output1 = 'La materia penal es competencia exclusiva del Estado segun Art. 149.1 CE.';
    $this->context->addTurn('q1', $output1);

    $this->context->reset();

    // After reset, no contradictions should be detected.
    $output2 = 'Las CCAA pueden legislar en materia penal.';
    $result = $this->context->checkCrossTurnCoherence($output2);

    $this->assertTrue($result['is_coherent']);
  }

  // ---------------------------------------------------------------
  // getAssertions().
  // ---------------------------------------------------------------

  /**
   * @covers ::getAssertions
   */
  public function testGetAssertionsReturnsArray(): void {
    $this->assertIsArray($this->context->getAssertions());
    $this->assertEmpty($this->context->getAssertions());
  }

  /**
   * @covers ::getAssertions
   */
  public function testAssertionStructure(): void {
    $output = 'Esta materia requiere Ley Organica (Art. 81 CE).';
    $this->context->addTurn('test', $output);

    $assertions = $this->context->getAssertions();
    $this->assertNotEmpty($assertions);

    $first = $assertions[0];
    $this->assertArrayHasKey('turn', $first);
    $this->assertArrayHasKey('assertion', $first);
    $this->assertArrayHasKey('norm', $first);
    $this->assertArrayHasKey('position', $first);
  }

  /**
   * @covers ::getAssertions
   */
  public function testAssertionTruncatedTo300Chars(): void {
    $longOutput = str_repeat('Es competencia exclusiva del Estado ', 20) . '.';
    $this->context->addTurn('test', $longOutput);

    $assertions = $this->context->getAssertions();
    foreach ($assertions as $a) {
      $this->assertLessThanOrEqual(300, mb_strlen($a['assertion']));
    }
  }

  // ---------------------------------------------------------------
  // Norm reference extraction.
  // ---------------------------------------------------------------

  /**
   * @covers ::addTurn
   */
  public function testExtractsNormReferenceInAssertion(): void {
    $output = 'Segun la Ley Organica 3/2018, es competencia exclusiva del Estado la proteccion de datos.';
    $this->context->addTurn('norma', $output);

    $assertions = $this->context->getAssertions();
    $this->assertNotEmpty($assertions);
    // norm field should contain a detected norm reference.
    $this->assertNotEmpty($assertions[0]['norm']);
  }

  // ---------------------------------------------------------------
  // MAX_ASSERTIONS limit.
  // ---------------------------------------------------------------

  /**
   * Tests that assertions are limited to MAX_ASSERTIONS (50).
   *
   * @covers ::addTurn
   */
  public function testAssertionsLimitedToFifty(): void {
    // Each turn with a competence assertion generates at least 1.
    for ($i = 0; $i < 60; $i++) {
      $this->context->addTurn(
        "query {$i}",
        "Es competencia exclusiva del Estado la materia numero {$i}.",
      );
    }

    $assertions = $this->context->getAssertions();
    $this->assertLessThanOrEqual(50, count($assertions));
  }

}
