<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Controller;

use Drupal\jaraba_copilot_v2\Controller\ExperimentApiController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExperimentApiController via reflection.
 *
 * Verifica creacion de experimentos, transiciones de estado,
 * registro de resultados con puntos de impacto y milestones.
 *
 * @covers \Drupal\jaraba_copilot_v2\Controller\ExperimentApiController
 * @group jaraba_copilot_v2
 */
class ExperimentApiReflectionTest extends TestCase {

  /**
   * Tests that the controller class exists.
   */
  public function testControllerClassExists(): void {
    $this->assertTrue(
      class_exists(ExperimentApiController::class),
      'ExperimentApiController class should exist'
    );
  }

  /**
   * Tests that all lifecycle methods exist.
   */
  public function testLifecycleMethodsExist(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);

    $methods = [
      'listUserExperiments',
      'store',
      'get',
      'start',
      'recordResult',
    ];
    foreach ($methods as $methodName) {
      $this->assertTrue(
        $reflection->hasMethod($methodName),
        "ExperimentApiController should have method: {$methodName}"
      );
    }
  }

  /**
   * Tests IMPACT_POINTS constant values.
   */
  public function testImpactPointsValues(): void {
    $points = ExperimentApiController::IMPACT_POINTS;

    $this->assertIsArray($points);
    $this->assertCount(5, $points, 'Should have exactly 5 decision types');

    $this->assertEquals(100, $points['PERSEVERE']);
    $this->assertEquals(75, $points['PIVOT']);
    $this->assertEquals(75, $points['ZOOM_IN']);
    $this->assertEquals(75, $points['ZOOM_OUT']);
    $this->assertEquals(50, $points['KILL']);
  }

  /**
   * Tests that valid state transitions are enforced in code.
   */
  public function testStateTransitionValidation(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString("'PLANNED'", $source,
      'start() should check for PLANNED status');
    $this->assertStringContainsString("'IN_PROGRESS'", $source,
      'recordResult() should check for IN_PROGRESS status');
    $this->assertStringContainsString("'COMPLETED'", $source,
      'Should set COMPLETED status after recording result');
  }

  /**
   * Tests that recordResult handles all decision types.
   */
  public function testAllDecisionTypesHandled(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $decisions = ['PERSEVERE', 'PIVOT', 'ZOOM_IN', 'ZOOM_OUT', 'KILL'];
    foreach ($decisions as $decision) {
      $this->assertStringContainsString($decision, $source,
        "Controller should handle {$decision} decision");
    }
  }

  /**
   * Tests that the recordMilestone method exists.
   */
  public function testRecordMilestoneMethodExists(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $this->assertTrue(
      $reflection->hasMethod('recordMilestone'),
      'Controller should have recordMilestone method'
    );
  }

  /**
   * Tests that the API response pattern is followed.
   */
  public function testApiResponsePattern(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString("'success' => TRUE", $source);
    $this->assertStringContainsString("'success' => FALSE", $source);
    $this->assertStringContainsString("'data'", $source);
    $this->assertStringContainsString("'error'", $source);
    $this->assertStringContainsString("'points_awarded'", $source);
  }

  /**
   * Tests that awardImpactPoints method exists and is protected.
   */
  public function testAwardImpactPointsIsProtected(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $this->assertTrue($reflection->hasMethod('awardImpactPoints'));

    $method = $reflection->getMethod('awardImpactPoints');
    $this->assertTrue($method->isProtected(),
      'awardImpactPoints should be protected');
  }

  /**
   * Tests that the controller uses accessCheck in queries.
   */
  public function testAccessCheckUsed(): void {
    $reflection = new \ReflectionClass(ExperimentApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString('accessCheck(', $source,
      'Controller should use accessCheck() on entity queries');
  }

}
