<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_journey\Unit\JourneyDefinition;

use Drupal\jaraba_journey\JourneyDefinition\JarabaLexJourneyDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for JarabaLexJourneyDefinition.
 *
 * @coversDefaultClass \Drupal\jaraba_journey\JourneyDefinition\JarabaLexJourneyDefinition
 * @group jaraba_journey
 */
class JarabaLexJourneyDefinitionTest extends UnitTestCase {

  /**
   * Tests that the definition declares exactly 3 avatars.
   *
   * @covers ::getAvatars
   */
  public function testDefines3Avatars(): void {
    $avatars = JarabaLexJourneyDefinition::getAvatars();
    $this->assertCount(3, $avatars);
    $this->assertContains('profesional_juridico', $avatars);
    $this->assertContains('gestor_fiscal', $avatars);
    $this->assertContains('investigador_legal', $avatars);
  }

  /**
   * Tests that each avatar journey has exactly 4 states.
   *
   * @covers ::getProfesionalJuridicoJourney
   * @covers ::getGestorFiscalJourney
   * @covers ::getInvestigadorLegalJourney
   */
  public function testEachAvatarHas4States(): void {
    $expectedStates = ['discovery', 'activation', 'engagement', 'conversion'];

    $journeys = [
      'profesional_juridico' => JarabaLexJourneyDefinition::getProfesionalJuridicoJourney(),
      'gestor_fiscal' => JarabaLexJourneyDefinition::getGestorFiscalJourney(),
      'investigador_legal' => JarabaLexJourneyDefinition::getInvestigadorLegalJourney(),
    ];

    foreach ($journeys as $avatar => $journey) {
      $this->assertArrayHasKey('states', $journey, "Journey '$avatar' must have a 'states' key.");
      $states = $journey['states'];
      $this->assertCount(4, $states, "Journey '$avatar' must have exactly 4 states.");

      foreach ($expectedStates as $state) {
        $this->assertArrayHasKey($state, $states, "Journey '$avatar' must have state '$state'.");
        $this->assertArrayHasKey('steps', $states[$state], "State '$state' in '$avatar' must have 'steps'.");
        $this->assertArrayHasKey('triggers', $states[$state], "State '$state' in '$avatar' must have 'triggers'.");
        $this->assertArrayHasKey('transition_event', $states[$state], "State '$state' in '$avatar' must have 'transition_event'.");
      }
    }
  }

  /**
   * Tests getProfesionalJuridicoJourney() returns a structured array.
   *
   * @covers ::getProfesionalJuridicoJourney
   */
  public function testGetProfesionalJuridicoJourney(): void {
    $journey = JarabaLexJourneyDefinition::getProfesionalJuridicoJourney();

    $this->assertArrayHasKey('avatar', $journey);
    $this->assertSame('profesional_juridico', $journey['avatar']);

    $this->assertArrayHasKey('vertical', $journey);
    $this->assertSame('jarabalex', $journey['vertical']);

    $this->assertArrayHasKey('kpi_target', $journey);
    $this->assertNotEmpty($journey['kpi_target']);

    $this->assertArrayHasKey('states', $journey);

    // Verify discovery state has expected steps.
    $discovery = $journey['states']['discovery'];
    $this->assertNotEmpty($discovery['steps'], 'Discovery state must have at least one step.');
    $firstStep = reset($discovery['steps']);
    $this->assertArrayHasKey('action', $firstStep);
    $this->assertArrayHasKey('label', $firstStep);
    $this->assertArrayHasKey('ia_intervention', $firstStep);

    // Verify conversion state exists and has upgrade step.
    $conversion = $journey['states']['conversion'];
    $this->assertNotEmpty($conversion['steps'], 'Conversion state must have at least one step.');
  }

  /**
   * Tests getJourneyDefinition() returns correct journey for each avatar.
   *
   * @covers ::getJourneyDefinition
   */
  public function testGetJourneyDefinitionByAvatar(): void {
    foreach (JarabaLexJourneyDefinition::getAvatars() as $avatar) {
      $journey = JarabaLexJourneyDefinition::getJourneyDefinition($avatar);
      $this->assertNotNull($journey, "getJourneyDefinition('$avatar') must not return NULL.");
      $this->assertSame($avatar, $journey['avatar']);
    }

    // Unknown avatar should return NULL.
    $this->assertNull(JarabaLexJourneyDefinition::getJourneyDefinition('unknown_avatar'));
  }

}
