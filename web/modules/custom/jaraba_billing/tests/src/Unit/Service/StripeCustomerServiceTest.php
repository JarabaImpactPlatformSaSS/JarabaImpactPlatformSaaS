<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_billing\Service\StripeCustomerService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para StripeCustomerService.
 *
 * @covers \Drupal\jaraba_billing\Service\StripeCustomerService
 * @group jaraba_billing
 */
class StripeCustomerServiceTest extends UnitTestCase {

  /**
   * @var \Drupal\jaraba_foc\Service\StripeConnectService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stripeConnect;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * @var \Drupal\jaraba_billing\Service\StripeCustomerService
   */
  protected StripeCustomerService $service;

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stripeConnect = $this->createMock(StripeConnectService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $this->lock->method('acquire')->willReturn(TRUE);

    $this->service = new StripeCustomerService(
      $this->stripeConnect,
      $this->entityTypeManager,
      $this->logger,
      $this->lock,
    );
  }

  /**
   * Tests createOrGetCustomer returns existing customer.
   */
  public function testCreateOrGetCustomerReturnsExisting(): void {
    $existingCustomer = ['id' => 'cus_existing', 'email' => 'test@example.com'];

    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('GET', '/customers', $this->anything())
      ->willReturn(['data' => [$existingCustomer]]);

    $result = $this->service->createOrGetCustomer(1, 'test@example.com');
    $this->assertEquals('cus_existing', $result['id']);
  }

  /**
   * Tests createOrGetCustomer creates new when not found.
   */
  public function testCreateOrGetCustomerCreatesNew(): void {
    $newCustomer = ['id' => 'cus_new', 'email' => 'new@example.com'];

    $this->stripeConnect->expects($this->exactly(2))
      ->method('stripeRequest')
      ->willReturnOnConsecutiveCalls(
        ['data' => []], // No existing customer.
        $newCustomer     // Create new.
      );

    // Mock billing_customer storage for syncBillingCustomer().
    $mockEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $mockEntity->method('save')->willReturn(1);

    $billingCustomerStorage = $this->createMock(EntityStorageInterface::class);
    $billingCustomerStorage->method('loadByProperties')->willReturn([]);
    $billingCustomerStorage->method('create')->willReturn($mockEntity);

    $this->entityTypeManager->method('getStorage')
      ->with('billing_customer')
      ->willReturn($billingCustomerStorage);

    $result = $this->service->createOrGetCustomer(1, 'new@example.com', 'Test User');
    $this->assertEquals('cus_new', $result['id']);
  }

  /**
   * Tests attachPaymentMethod calls correct endpoint.
   */
  public function testAttachPaymentMethod(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/payment_methods/pm_123/attach', ['customer' => 'cus_456'])
      ->willReturn(['id' => 'pm_123', 'customer' => 'cus_456']);

    $result = $this->service->attachPaymentMethod('pm_123', 'cus_456');
    $this->assertEquals('pm_123', $result['id']);
  }

  /**
   * Tests detachPaymentMethod calls correct endpoint.
   */
  public function testDetachPaymentMethod(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('POST', '/payment_methods/pm_123/detach')
      ->willReturn(['id' => 'pm_123']);

    $result = $this->service->detachPaymentMethod('pm_123');
    $this->assertEquals('pm_123', $result['id']);
  }

  /**
   * Tests syncPaymentMethods creates local entities.
   */
  public function testSyncPaymentMethodsCreatesEntities(): void {
    $stripeData = [
      'data' => [
        [
          'id' => 'pm_1',
          'type' => 'card',
          'card' => [
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
          ],
        ],
      ],
    ];

    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->willReturn($stripeData);

    $mockEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $mockEntity->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);
    $storage->expects($this->once())
      ->method('create')
      ->willReturn($mockEntity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('billing_payment_method')
      ->willReturn($storage);

    $count = $this->service->syncPaymentMethods('cus_123', 1);
    $this->assertEquals(1, $count);
  }

}
