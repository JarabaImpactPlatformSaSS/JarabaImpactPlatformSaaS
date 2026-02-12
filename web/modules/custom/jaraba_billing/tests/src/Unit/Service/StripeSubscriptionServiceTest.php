<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_billing\Service\StripeSubscriptionService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para StripeSubscriptionService.
 *
 * @covers \Drupal\jaraba_billing\Service\StripeSubscriptionService
 * @group jaraba_billing
 */
class StripeSubscriptionServiceTest extends UnitTestCase {

  protected $stripeConnect;
  protected $tenantSubscription;
  protected $entityTypeManager;
  protected $logger;
  protected StripeSubscriptionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->tenantSubscription = $this->createMock(TenantSubscriptionService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new StripeSubscriptionService(
      $this->stripeConnect,
      $this->tenantSubscription,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests createSubscription sends correct params.
   */
  public function testCreateSubscription(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/subscriptions', $this->callback(function ($params) {
        return $params['customer'] === 'cus_123'
          && $params['items'][0]['price'] === 'price_abc';
      }))
      ->willReturn(['id' => 'sub_123', 'status' => 'active']);

    $result = $this->service->createSubscription('cus_123', 'price_abc');
    $this->assertEquals('sub_123', $result['id']);
  }

  /**
   * Tests createSubscription with trial period.
   */
  public function testCreateSubscriptionWithTrial(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/subscriptions', $this->callback(function ($params) {
        return $params['trial_period_days'] === 14;
      }))
      ->willReturn(['id' => 'sub_trial', 'status' => 'trialing']);

    $result = $this->service->createSubscription('cus_123', 'price_abc', [
      'trial_period_days' => 14,
    ]);
    $this->assertEquals('sub_trial', $result['id']);
  }

  /**
   * Tests cancelSubscription immediately.
   */
  public function testCancelSubscriptionImmediately(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('DELETE', '/subscriptions/sub_123')
      ->willReturn(['id' => 'sub_123', 'status' => 'canceled']);

    $result = $this->service->cancelSubscription('sub_123', TRUE);
    $this->assertEquals('canceled', $result['status']);
  }

  /**
   * Tests cancelSubscription at period end.
   */
  public function testCancelSubscriptionAtPeriodEnd(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/subscriptions/sub_123', $this->callback(function ($params) {
        return $params['cancel_at_period_end'] === 'true';
      }))
      ->willReturn(['id' => 'sub_123', 'cancel_at_period_end' => TRUE]);

    $result = $this->service->cancelSubscription('sub_123', FALSE);
    $this->assertTrue($result['cancel_at_period_end']);
  }

  /**
   * Tests updateSubscription changes plan.
   */
  public function testUpdateSubscription(): void {
    $this->stripeConnect->expects($this->exactly(2))
      ->method('stripeRequest')
      ->willReturnOnConsecutiveCalls(
        ['items' => ['data' => [['id' => 'si_123']]]],
        ['id' => 'sub_123', 'status' => 'active']
      );

    $result = $this->service->updateSubscription('sub_123', 'price_new');
    $this->assertEquals('sub_123', $result['id']);
  }

  /**
   * Tests pauseSubscription.
   */
  public function testPauseSubscription(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/subscriptions/sub_123', $this->callback(function ($params) {
        return isset($params['pause_collection']);
      }))
      ->willReturn(['id' => 'sub_123']);

    $result = $this->service->pauseSubscription('sub_123');
    $this->assertEquals('sub_123', $result['id']);
  }

}
