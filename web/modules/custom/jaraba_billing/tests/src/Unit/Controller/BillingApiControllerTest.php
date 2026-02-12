<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Controller;

use Drupal\jaraba_billing\Controller\BillingApiController;
use Drupal\jaraba_billing\Service\StripeCustomerService;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_billing\Service\StripeSubscriptionService;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para BillingApiController.
 *
 * @covers \Drupal\jaraba_billing\Controller\BillingApiController
 * @group jaraba_billing
 */
class BillingApiControllerTest extends UnitTestCase {

  protected StripeCustomerService $stripeCustomer;
  protected StripeSubscriptionService $stripeSubscription;
  protected StripeInvoiceService $stripeInvoice;
  protected TenantSubscriptionService $tenantSubscription;
  protected TenantMeteringService $metering;
  protected StripeConnectService $stripeConnect;
  protected LoggerInterface $logger;
  protected BillingApiController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stripeCustomer = $this->createMock(StripeCustomerService::class);
    $this->stripeSubscription = $this->createMock(StripeSubscriptionService::class);
    $this->stripeInvoice = $this->createMock(StripeInvoiceService::class);
    $this->tenantSubscription = $this->createMock(TenantSubscriptionService::class);
    $this->metering = $this->createMock(TenantMeteringService::class);
    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->controller = new BillingApiController(
      $this->stripeCustomer,
      $this->stripeSubscription,
      $this->stripeInvoice,
      $this->tenantSubscription,
      $this->metering,
      $this->stripeConnect,
      $this->logger,
    );
  }

  /**
   * Tests that the controller class exists.
   */
  public function testControllerExists(): void {
    $this->assertInstanceOf(BillingApiController::class, $this->controller);
  }

  /**
   * Tests that the controller has all expected methods.
   */
  public function testControllerHasExpectedMethods(): void {
    $reflection = new \ReflectionClass(BillingApiController::class);

    $expectedMethods = [
      'getSubscription',
      'createSubscription',
      'changePlan',
      'cancelSubscription',
      'reactivateSubscription',
      'listInvoices',
      'getInvoicePdf',
      'getUsage',
      'createPortalSession',
      'listPaymentMethods',
      'addPaymentMethod',
      'deletePaymentMethod',
      'updateCustomer',
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
    $reflection = new \ReflectionClass(BillingApiController::class);

    $apiMethods = [
      'getSubscription',
      'listInvoices',
      'getUsage',
      'listPaymentMethods',
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
