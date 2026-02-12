<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Controller\HypothesisApiController;
use Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the HypothesisApiController.
 *
 * @covers \Drupal\jaraba_copilot_v2\Controller\HypothesisApiController
 * @group jaraba_copilot_v2
 */
class HypothesisApiControllerTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked prioritization service.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected HypothesisPrioritizationService $prioritization;

  /**
   * The controller under test.
   *
   * @var \Drupal\jaraba_copilot_v2\Controller\HypothesisApiController
   */
  protected HypothesisApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->prioritization = $this->createMock(HypothesisPrioritizationService::class);

    $this->controller = new HypothesisApiController(
      $this->entityTypeManager,
      $this->prioritization,
    );
  }

  /**
   * Tests that the controller can be instantiated.
   */
  public function testControllerExists(): void {
    $this->assertInstanceOf(HypothesisApiController::class, $this->controller);
  }

  /**
   * Tests that the controller has all expected API methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);

    $expectedMethods = [
      'list',
      'store',
      'get',
      'update',
      'prioritize',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Controller should have method: {$method}"
      );
    }
  }

  /**
   * Tests that all API methods declare JsonResponse as their return type.
   */
  public function testMethodReturnTypes(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);

    $apiMethods = [
      'list',
      'store',
      'get',
      'update',
      'prioritize',
    ];

    foreach ($apiMethods as $methodName) {
      $method = $reflection->getMethod($methodName);
      $returnType = $method->getReturnType();
      $this->assertNotNull($returnType, "Method {$methodName} should have a return type");
      $this->assertEquals(
        'Symfony\Component\HttpFoundation\JsonResponse',
        $returnType->getName(),
        "Method {$methodName} should return JsonResponse"
      );
    }
  }

  /**
   * Tests that the store method is public.
   */
  public function testStoreMethodIsPublic(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);
    $method = $reflection->getMethod('store');
    $this->assertTrue($method->isPublic(), 'store method should be public');
  }

  /**
   * Tests that serializeHypothesis is a protected method.
   */
  public function testSerializeHypothesisIsProtected(): void {
    $reflection = new \ReflectionClass(HypothesisApiController::class);
    $method = $reflection->getMethod('serializeHypothesis');
    $this->assertTrue($method->isProtected(), 'serializeHypothesis method should be protected');
  }

}
