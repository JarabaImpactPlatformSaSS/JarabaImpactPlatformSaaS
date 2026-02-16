<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Service\EInvoicePaymentStatusService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the payment status service.
 *
 * Tests pure logic methods that do not require entity storage.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Service\EInvoicePaymentStatusService
 */
class EInvoicePaymentStatusServiceTest extends UnitTestCase {

  protected EInvoicePaymentStatusService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(\Drupal\Core\Entity\EntityTypeManagerInterface::class);
    $spfeClient = $this->createMock(\Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface::class);
    $loggerFactory = $this->createMock(\Drupal\Core\Logger\LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->createMock(\Psr\Log\LoggerInterface::class));

    $this->service = new EInvoicePaymentStatusService(
      $entityTypeManager,
      $spfeClient,
      $loggerFactory,
    );
  }

  /**
   * Tests calculateOverdueDays returns positive for past dates.
   *
   * @covers ::calculateOverdueDays
   */
  public function testCalculateOverdueDaysPastDate(): void {
    $pastDate = (new \DateTimeImmutable('-10 days'))->format('Y-m-d');
    $days = $this->service->calculateOverdueDays($pastDate);
    $this->assertSame(10, $days);
  }

  /**
   * Tests calculateOverdueDays returns negative for future dates.
   *
   * @covers ::calculateOverdueDays
   */
  public function testCalculateOverdueDaysFutureDate(): void {
    $futureDate = (new \DateTimeImmutable('+15 days'))->format('Y-m-d');
    $days = $this->service->calculateOverdueDays($futureDate);
    $this->assertSame(-15, $days);
  }

  /**
   * Tests calculateOverdueDays returns 0 for today.
   *
   * @covers ::calculateOverdueDays
   */
  public function testCalculateOverdueDaysToday(): void {
    $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
    $days = $this->service->calculateOverdueDays($today);
    $this->assertSame(0, $days);
  }

  /**
   * Tests calculateOverdueDays with invalid date returns 0.
   *
   * @covers ::calculateOverdueDays
   */
  public function testCalculateOverdueDaysInvalidDate(): void {
    $days = $this->service->calculateOverdueDays('not-a-date');
    $this->assertSame(0, $days);
  }

  /**
   * Tests calculateOverdueDays with Ley 3/2004 30-day deadline.
   *
   * @covers ::calculateOverdueDays
   */
  public function testLey3200430DayDeadline(): void {
    $thirtyOneDaysAgo = (new \DateTimeImmutable('-31 days'))->format('Y-m-d');
    $days = $this->service->calculateOverdueDays($thirtyOneDaysAgo);
    $this->assertSame(31, $days);
    // Per Ley 3/2004 art. 4: general 30 days from receipt.
    $this->assertGreaterThan(30, $days);
  }

  /**
   * Tests calculateOverdueDays with Ley 3/2004 60-day maximum.
   *
   * @covers ::calculateOverdueDays
   */
  public function testLey3200460DayMaximum(): void {
    $sixtyOneDaysAgo = (new \DateTimeImmutable('-61 days'))->format('Y-m-d');
    $days = $this->service->calculateOverdueDays($sixtyOneDaysAgo);
    $this->assertSame(61, $days);
    // Per Ley 3/2004 art. 4.3: 60 day max cannot be exceeded by contract.
    $this->assertGreaterThan(60, $days);
  }

}
