<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Controller;

use Drupal\jaraba_billing\Controller\AddonApiController;
use Drupal\jaraba_billing\Service\FeatureAccessService;
use Drupal\jaraba_billing\Service\StripeSubscriptionService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AddonApiController.
 *
 * @covers \Drupal\jaraba_billing\Controller\AddonApiController
 * @group jaraba_billing
 */
class AddonApiControllerTest extends UnitTestCase {

  protected FeatureAccessService $featureAccess;
  protected StripeSubscriptionService $stripeSubscription;
  protected TenantSubscriptionService $tenantSubscription;
  protected StripeConnectService $stripeConnect;
  protected LoggerInterface $logger;
  protected AddonApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->featureAccess = $this->createMock(FeatureAccessService::class);
    $this->stripeSubscription = $this->createMock(StripeSubscriptionService::class);
    $this->tenantSubscription = $this->createMock(TenantSubscriptionService::class);
    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->controller = new AddonApiController(
      $this->featureAccess,
      $this->stripeSubscription,
      $this->tenantSubscription,
      $this->stripeConnect,
      $this->logger,
    );
  }

  /**
   * Tests that the controller class exists.
   */
  public function testControllerExists(): void {
    $this->assertInstanceOf(AddonApiController::class, $this->controller);
  }

  /**
   * Tests that the controller has all expected methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(AddonApiController::class);

    $expectedMethods = [
      'getSubscription',
      'getAvailableAddons',
      'activateAddon',
      'cancelAddon',
      'upgradePlan',
      'getUpcomingInvoice',
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
    $reflection = new \ReflectionClass(AddonApiController::class);

    $apiMethods = [
      'getSubscription',
      'getAvailableAddons',
      'getUpcomingInvoice',
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
