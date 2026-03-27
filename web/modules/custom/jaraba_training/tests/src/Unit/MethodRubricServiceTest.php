<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_training\Unit;

use Drupal\jaraba_training\Service\MethodRubricService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Tests para MethodRubricService.
 *
 * @group jaraba_training
 * @coversDefaultClass \Drupal\jaraba_training\Service\MethodRubricService
 */
class MethodRubricServiceTest extends UnitTestCase {

  protected MethodRubricService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new MethodRubricService(new NullLogger());
    $this->service->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::calculateOverallLevel
   */
  public function testCalculateOverallLevelReturnsMinimum(): void {
    $scores = ['pedir' => 3, 'evaluar' => 2, 'iterar' => 4, 'integrar' => 3];
    static::assertSame(2, $this->service->calculateOverallLevel($scores));
  }

  /**
   * @covers ::calculateOverallLevel
   */
  public function testCalculateOverallLevelAllEqual(): void {
    $scores = ['pedir' => 4, 'evaluar' => 4, 'iterar' => 4, 'integrar' => 4];
    static::assertSame(4, $this->service->calculateOverallLevel($scores));
  }

  /**
   * @covers ::calculateOverallLevel
   */
  public function testCalculateOverallLevelReturnsZeroIfMissing(): void {
    $scores = ['pedir' => 3, 'evaluar' => 2, 'iterar' => 4];
    static::assertSame(0, $this->service->calculateOverallLevel($scores));
  }

  /**
   * @covers ::calculateOverallLevel
   */
  public function testCalculateOverallLevelReturnsZeroIfOutOfRange(): void {
    $scores = ['pedir' => 5, 'evaluar' => 2, 'iterar' => 3, 'integrar' => 1];
    static::assertSame(0, $this->service->calculateOverallLevel($scores));
  }

  /**
   * @covers ::checkScoreDisparity
   */
  public function testCheckScoreDisparityDetectsLargeGap(): void {
    $scores = ['pedir' => 1, 'evaluar' => 4, 'iterar' => 3, 'integrar' => 3];
    $result = $this->service->checkScoreDisparity($scores);
    static::assertNotNull($result);
    static::assertStringContainsString('3', $result);
  }

  /**
   * @covers ::checkScoreDisparity
   */
  public function testCheckScoreDisparityReturnsNullWhenSmallGap(): void {
    $scores = ['pedir' => 3, 'evaluar' => 3, 'iterar' => 4, 'integrar' => 3];
    static::assertNull($this->service->checkScoreDisparity($scores));
  }

  /**
   * @covers ::calculateOverallLevel
   */
  public function testCalculateOverallLevelMinimumOne(): void {
    $scores = ['pedir' => 1, 'evaluar' => 1, 'iterar' => 1, 'integrar' => 1];
    static::assertSame(1, $this->service->calculateOverallLevel($scores));
  }

}
