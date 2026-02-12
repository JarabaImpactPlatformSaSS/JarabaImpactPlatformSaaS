<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Controller;

use Drupal\jaraba_billing\Controller\UsageBillingApiController;
use Drupal\jaraba_billing\Service\PlanValidator;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para UsageBillingApiController.
 *
 * @covers \Drupal\jaraba_billing\Controller\UsageBillingApiController
 * @group jaraba_billing
 */
class UsageBillingApiControllerTest extends UnitTestCase {

  protected TenantMeteringService $metering;
  protected PlanValidator $planValidator;
  protected LoggerInterface $logger;
  protected UsageBillingApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->metering = $this->createMock(TenantMeteringService::class);
    $this->planValidator = $this->createMock(PlanValidator::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->controller = new UsageBillingApiController(
      $this->metering,
      $this->planValidator,
      $this->logger,
    );
  }

  /**
   * Tests that the controller class exists.
   */
  public function testControllerExists(): void {
    $this->assertInstanceOf(UsageBillingApiController::class, $this->controller);
  }

  /**
   * Tests that the controller has all expected methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(UsageBillingApiController::class);

    $expectedMethods = [
      'recordUsage',
      'getCurrentUsage',
      'getUsageHistory',
      'getUsageBreakdown',
      'getUsageForecast',
      'getMyPlan',
      'estimateCost',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Controller should have method: {$method}"
      );
    }
  }

  /**
   * Tests that all API methods return JsonResponse.
   */
  public function testMethodReturnTypes(): void {
    $reflection = new \ReflectionClass(UsageBillingApiController::class);

    $apiMethods = [
      'getCurrentUsage',
      'getUsageForecast',
      'estimateCost',
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

}
