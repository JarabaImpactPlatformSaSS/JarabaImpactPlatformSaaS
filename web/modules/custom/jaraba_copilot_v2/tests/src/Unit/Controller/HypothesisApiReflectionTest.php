<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Controller;

use Drupal\jaraba_copilot_v2\Controller\HypothesisApiController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the HypothesisApiController via reflection.
 *
 * Verifica operaciones CRUD de hipotesis y priorizacion ICE
 * mediante inspeccion de la clase y su codigo fuente.
 *
 * @covers \Drupal\jaraba_copilot_v2\Controller\HypothesisApiController
 * @group jaraba_copilot_v2
 */
class HypothesisApiReflectionTest extends TestCase {

  /**
   * Tests that the controller class exists and can be reflected.
   */
  public function testControllerClassExists(): void {
    $this->assertTrue(
      class_exists(HypothesisApiController::class),
      'HypothesisApiController class should exist'
    );
  }

  /**
   * Tests that all CRUD methods exist with correct signatures.
   */
  public function testCrudMethodsExist(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);

    $methods = ['list', 'store', 'get', 'update', 'prioritize'];
    foreach ($methods as $methodName) {
      $this->assertTrue(
        $reflection->hasMethod($methodName),
        "HypothesisApiController should have method: {$methodName}"
      );
      $method = $reflection->getMethod($methodName);
      $this->assertTrue($method->isPublic(), "{$methodName} should be public");
    }
  }

  /**
   * Tests that API methods return JsonResponse.
   */
  public function testMethodReturnTypes(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);

    $methods = ['list', 'store', 'get', 'update', 'prioritize'];
    foreach ($methods as $methodName) {
      $method = $reflection->getMethod($methodName);
      $returnType = $method->getReturnType();
      $this->assertNotNull($returnType, "Method {$methodName} should declare return type");
      $this->assertEquals(
        'Symfony\Component\HttpFoundation\JsonResponse',
        $returnType->getName(),
        "Method {$methodName} should return JsonResponse"
      );
    }
  }

  /**
   * Tests that the controller uses the standard API response pattern.
   */
  public function testApiResponsePatternHasSuccessKey(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString("'success' => TRUE", $source);
    $this->assertStringContainsString("'success' => FALSE", $source);
    $this->assertStringContainsString("'data'", $source);
    $this->assertStringContainsString("'error'", $source);
  }

  /**
   * Tests that the list method accepts Request parameter.
   */
  public function testListMethodAcceptsRequest(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);
    $method = $reflection->getMethod('list');
    $params = $method->getParameters();

    $this->assertNotEmpty($params, 'list method should have parameters');
    $firstParam = $params[0];
    $this->assertEquals('request', $firstParam->getName());
  }

  /**
   * Tests that the controller uses accessCheck in queries.
   */
  public function testAccessCheckUsedInQueries(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString('accessCheck(', $source,
      'Controller should use accessCheck() on entity queries');
  }

}
