<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\AdaptiveDifficultyEngine;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the AdaptiveDifficultyEngine service.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AdaptiveDifficultyEngine
 * @group jaraba_andalucia_ei
 */
class AdaptiveDifficultyEngineTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected AdaptiveDifficultyEngine $engine;

  /**
   * Mock time service.
   */
  protected TimeInterface $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1740500000);
    $logger = $this->createMock(LoggerInterface::class);

    $this->engine = new AdaptiveDifficultyEngine(
      $entityTypeManager,
      $this->time,
      $logger,
    );
  }

  /**
   * Tests scoreToLevel() mapping.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreToLevelReturnsHighForScoreAbove70(): void {
    $method = new \ReflectionMethod($this->engine, 'scoreToLevel');

    $this->assertEquals('high', $method->invoke($this->engine, 85.0));
    $this->assertEquals('high', $method->invoke($this->engine, 70.0));
    $this->assertEquals('high', $method->invoke($this->engine, 100.0));
  }

  /**
   * Tests scoreToLevel() returns medium for 40-69.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreToLevelReturnsMediumForMidRange(): void {
    $method = new \ReflectionMethod($this->engine, 'scoreToLevel');

    $this->assertEquals('medium', $method->invoke($this->engine, 40.0));
    $this->assertEquals('medium', $method->invoke($this->engine, 55.0));
    $this->assertEquals('medium', $method->invoke($this->engine, 69.9));
  }

  /**
   * Tests scoreToLevel() returns low for 20-39.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreToLevelReturnsLowForLowRange(): void {
    $method = new \ReflectionMethod($this->engine, 'scoreToLevel');

    $this->assertEquals('low', $method->invoke($this->engine, 20.0));
    $this->assertEquals('low', $method->invoke($this->engine, 30.0));
    $this->assertEquals('low', $method->invoke($this->engine, 39.9));
  }

  /**
   * Tests scoreToLevel() returns at_risk for below 20.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreToLevelReturnsAtRiskForVeryLow(): void {
    $method = new \ReflectionMethod($this->engine, 'scoreToLevel');

    $this->assertEquals('at_risk', $method->invoke($this->engine, 0.0));
    $this->assertEquals('at_risk', $method->invoke($this->engine, 10.0));
    $this->assertEquals('at_risk', $method->invoke($this->engine, 19.9));
  }

  /**
   * Tests nudge frequency varies by engagement level.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function nudgeFrequencyIncreasesForLowerEngagement(): void {
    $method = new \ReflectionMethod($this->engine, 'getNudgeFrequency');

    $high = $method->invoke($this->engine, 'high');
    $medium = $method->invoke($this->engine, 'medium');
    $low = $method->invoke($this->engine, 'low');
    $atRisk = $method->invoke($this->engine, 'at_risk');

    // Higher engagement = less frequent nudges.
    $this->assertGreaterThan($atRisk['interval_hours'], $high['interval_hours']);
    $this->assertGreaterThan($atRisk['interval_hours'], $medium['interval_hours']);
    $this->assertGreaterThan($atRisk['interval_hours'], $low['interval_hours']);

    // More channels for lower engagement.
    $this->assertLessThan(count($atRisk['channels']), count($high['channels']));
  }

  /**
   * Tests course difficulty based on hours.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function courseDifficultyReturnsCorrectLevel(): void {
    $method = new \ReflectionMethod($this->engine, 'getCourseDifficulty');

    // Advanced: formacion >= 40 AND ia >= 5.
    $participante = $this->createParticipanteMock(45.0, 6.0);
    $this->assertEquals('advanced', $method->invoke($this->engine, $participante));

    // Intermediate: formacion >= 15 OR ia >= 2.
    $participante = $this->createParticipanteMock(20.0, 1.0);
    $this->assertEquals('intermediate', $method->invoke($this->engine, $participante));

    // Beginner: low hours.
    $participante = $this->createParticipanteMock(5.0, 0.5);
    $this->assertEquals('beginner', $method->invoke($this->engine, $participante));
  }

  /**
   * Tests course difficulty returns intermediate when only IA hours qualify.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function courseDifficultyIntermediateWithIaHoursAlone(): void {
    $method = new \ReflectionMethod($this->engine, 'getCourseDifficulty');

    $participante = $this->createParticipanteMock(5.0, 3.0);
    $this->assertEquals('intermediate', $method->invoke($this->engine, $participante));
  }

  /**
   * Creates a mock participant with specified hours.
   */
  protected function createParticipanteMock(float $formacion, float $mentoriaIa): ProgramaParticipanteEiInterface {
    $participante = $this->createMock(ProgramaParticipanteEiInterface::class);
    $participante->method('getHorasMentoriaIa')->willReturn($mentoriaIa);

    // Use anonymous class to properly handle ->value property access.
    $formacionField = new class($formacion) {
      public $value;
      public function __construct(float $v) { $this->value = $v; }
      public function isEmpty(): bool { return FALSE; }
    };

    $emptyField = new class() {
      public $value = NULL;
      public function isEmpty(): bool { return TRUE; }
    };

    $participante->method('get')
      ->willReturnCallback(function (string $field) use ($formacionField, $emptyField) {
        if ($field === 'horas_formacion') {
          return $formacionField;
        }
        return $emptyField;
      });

    return $participante;
  }

}
