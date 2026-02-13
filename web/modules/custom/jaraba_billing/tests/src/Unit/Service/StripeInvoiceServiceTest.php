<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para StripeInvoiceService.
 *
 * @covers \Drupal\jaraba_billing\Service\StripeInvoiceService
 * @group jaraba_billing
 */
class StripeInvoiceServiceTest extends UnitTestCase {

  protected $stripeConnect;
  protected $entityTypeManager;
  protected $logger;
  protected $lock;
  protected StripeInvoiceService $service;

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

    $this->service = new StripeInvoiceService(
      $this->stripeConnect,
      $this->entityTypeManager,
      $this->logger,
      $this->lock,
    );
  }

  /**
   * Tests syncInvoice creates a new local entity.
   */
  public function testSyncInvoiceCreatesNewEntity(): void {
    $stripeInvoice = [
      'id' => 'in_123',
      'number' => 'INV-001',
      'status' => 'paid',
      'amount_due' => 5000,
      'amount_paid' => 5000,
      'currency' => 'eur',
      'period_start' => 1700000000,
      'period_end' => 1702592000,
      'metadata' => ['tenant_id' => '5'],
      'status_transitions' => ['paid_at' => 1700100000],
      'invoice_pdf' => 'https://stripe.com/pdf/in_123',
      'hosted_invoice_url' => 'https://stripe.com/hosted/in_123',
    ];

    $mockEntity = $this->createMock(ContentEntityInterface::class);
    $mockEntity->expects($this->once())->method('save');
    $mockEntity->expects($this->once())->method('id')->willReturn('42');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['stripe_invoice_id' => 'in_123'])
      ->willReturn([]);
    $storage->expects($this->once())
      ->method('create')
      ->willReturn($mockEntity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('billing_invoice')
      ->willReturn($storage);

    $entityId = $this->service->syncInvoice($stripeInvoice);
    $this->assertEquals(42, $entityId);
  }

  /**
   * Tests syncInvoice updates an existing entity.
   */
  public function testSyncInvoiceUpdatesExisting(): void {
    $stripeInvoice = [
      'id' => 'in_existing',
      'number' => 'INV-002',
      'status' => 'paid',
      'amount_due' => 3000,
      'amount_paid' => 3000,
      'currency' => 'eur',
      'metadata' => [],
    ];

    $mockEntity = $this->createMock(ContentEntityInterface::class);
    $mockEntity->expects($this->atLeastOnce())->method('set');
    $mockEntity->expects($this->once())->method('save');
    $mockEntity->expects($this->once())->method('id')->willReturn('10');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([$mockEntity]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('billing_invoice')
      ->willReturn($storage);

    $entityId = $this->service->syncInvoice($stripeInvoice, 1);
    $this->assertEquals(10, $entityId);
  }

  /**
   * Tests reportUsage calls correct endpoint.
   */
  public function testReportUsage(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with(
        'POST',
        '/subscription_items/si_abc/usage_records',
        $this->callback(function ($params) {
          return $params['quantity'] === 100
            && $params['action'] === 'increment';
        })
      )
      ->willReturn(['id' => 'ur_123', 'quantity' => 100]);

    $result = $this->service->reportUsage('si_abc', 100);
    $this->assertEquals('ur_123', $result['id']);
  }

  /**
   * Tests listInvoices calls Stripe API.
   */
  public function testListInvoices(): void {
    $invoices = [
      ['id' => 'in_1'],
      ['id' => 'in_2'],
    ];

    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->with('GET', '/invoices', $this->callback(function ($params) {
        return $params['customer'] === 'cus_123' && $params['limit'] === 5;
      }))
      ->willReturn(['data' => $invoices]);

    $result = $this->service->listInvoices('cus_123', 5);
    $this->assertCount(2, $result);
  }

  /**
   * Tests getInvoicePdf returns PDF URL.
   */
  public function testGetInvoicePdf(): void {
    $this->stripeConnect->expects($this->once())
      ->method('stripeRequest')
      ->willReturn(['invoice_pdf' => 'https://stripe.com/pdf/test']);

    $url = $this->service->getInvoicePdf('in_123');
    $this->assertEquals('https://stripe.com/pdf/test', $url);
  }

}
