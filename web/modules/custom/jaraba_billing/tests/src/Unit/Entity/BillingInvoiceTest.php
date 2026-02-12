<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\BillingInvoice;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para la entidad BillingInvoice.
 *
 * @covers \Drupal\jaraba_billing\Entity\BillingInvoice
 * @group jaraba_billing
 */
class BillingInvoiceTest extends UnitTestCase {

  /**
   * Tests isPaid() returns TRUE when status is 'paid'.
   */
  public function testIsPaidReturnsTrue(): void {
    $entity = $this->createMockInvoice('paid');
    $this->assertTrue($entity->isPaid());
  }

  /**
   * Tests isPaid() returns FALSE when status is not 'paid'.
   */
  public function testIsPaidReturnsFalse(): void {
    $entity = $this->createMockInvoice('open');
    $this->assertFalse($entity->isPaid());
  }

  /**
   * Tests isOverdue() returns TRUE when past due date and status is open.
   */
  public function testIsOverdueReturnsTrueWhenPastDue(): void {
    $entity = $this->createMockInvoice('open', '2020-01-01T00:00:00');
    $this->assertTrue($entity->isOverdue());
  }

  /**
   * Tests isOverdue() returns FALSE when paid.
   */
  public function testIsOverdueReturnsFalseWhenPaid(): void {
    $entity = $this->createMockInvoice('paid', '2020-01-01T00:00:00');
    $this->assertFalse($entity->isOverdue());
  }

  /**
   * Tests isOverdue() returns FALSE when void.
   */
  public function testIsOverdueReturnsFalseWhenVoid(): void {
    $entity = $this->createMockInvoice('void', '2020-01-01T00:00:00');
    $this->assertFalse($entity->isOverdue());
  }

  /**
   * Tests isOverdue() returns FALSE when no due date.
   */
  public function testIsOverdueReturnsFalseWithNoDueDate(): void {
    $entity = $this->createMockInvoice('open', NULL);
    $this->assertFalse($entity->isOverdue());
  }

  /**
   * Tests isOverdue() returns FALSE when due date is in the future.
   */
  public function testIsOverdueReturnsFalseWhenFutureDue(): void {
    $futureDate = (new \DateTime('+30 days'))->format('Y-m-d\TH:i:s');
    $entity = $this->createMockInvoice('open', $futureDate);
    $this->assertFalse($entity->isOverdue());
  }

  /**
   * Tests that baseFieldDefinitions contains new fiscal fields.
   */
  public function testBaseFieldDefinitionsContainsFiscalFields(): void {
    $reflection = new \ReflectionClass(BillingInvoice::class);
    $source = file_get_contents($reflection->getFileName());

    // Verify new fiscal fields are present in the source.
    $this->assertStringContainsString("'stripe_customer_id'", $source);
    $this->assertStringContainsString("'subtotal'", $source);
    $this->assertStringContainsString("'tax'", $source);
    $this->assertStringContainsString("'total'", $source);
    $this->assertStringContainsString("'billing_reason'", $source);
    $this->assertStringContainsString("'lines'", $source);
  }

  /**
   * Tests that billing_reason has correct allowed values.
   */
  public function testBillingReasonAllowedValues(): void {
    $reflection = new \ReflectionClass(BillingInvoice::class);
    $source = file_get_contents($reflection->getFileName());

    $this->assertStringContainsString('subscription_cycle', $source);
    $this->assertStringContainsString('subscription_create', $source);
    $this->assertStringContainsString('subscription_update', $source);
    $this->assertStringContainsString('manual', $source);
    $this->assertStringContainsString('upcoming', $source);
  }

  /**
   * Creates a mock BillingInvoice with given status and due_date.
   */
  protected function createMockInvoice(string $status, ?string $dueDate = NULL): BillingInvoice {
    // Use stdClass instead of mock for field values — PHP 8.4 breaks
    // dynamic properties on PHPUnit mock objects.
    $statusField = (object) ['value' => $status];
    $dueDateField = (object) ['value' => $dueDate];

    $entity = $this->getMockBuilder(BillingInvoice::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $entity->method('get')
      ->willReturnMap([
        ['status', $statusField],
        ['due_date', $dueDateField],
      ]);

    return $entity;
  }

}
