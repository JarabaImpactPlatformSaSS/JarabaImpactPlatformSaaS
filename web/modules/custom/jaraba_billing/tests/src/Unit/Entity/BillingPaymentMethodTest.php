<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\BillingPaymentMethod;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para la entidad BillingPaymentMethod.
 *
 * @covers \Drupal\jaraba_billing\Entity\BillingPaymentMethod
 * @group jaraba_billing
 */
class BillingPaymentMethodTest extends UnitTestCase {

  /**
   * Tests isDefault() returns TRUE when is_default is set.
   */
  public function testIsDefaultReturnsTrue(): void {
    $entity = $this->createMockPaymentMethod('card', TRUE, 12, 2030);
    $this->assertTrue($entity->isDefault());
  }

  /**
   * Tests isDefault() returns FALSE when is_default is not set.
   */
  public function testIsDefaultReturnsFalse(): void {
    $entity = $this->createMockPaymentMethod('card', FALSE, 12, 2030);
    $this->assertFalse($entity->isDefault());
  }

  /**
   * Tests isExpired() returns TRUE when card has expired.
   */
  public function testIsExpiredReturnsTrueForExpiredCard(): void {
    $entity = $this->createMockPaymentMethod('card', FALSE, 1, 2020);
    $this->assertTrue($entity->isExpired());
  }

  /**
   * Tests isExpired() returns FALSE when card has not expired.
   */
  public function testIsExpiredReturnsFalseForValidCard(): void {
    $entity = $this->createMockPaymentMethod('card', FALSE, 12, 2030);
    $this->assertFalse($entity->isExpired());
  }

  /**
   * Tests isExpired() returns FALSE for non-card types.
   */
  public function testIsExpiredReturnsFalseForNonCard(): void {
    $entity = $this->createMockPaymentMethod('sepa_debit', FALSE, 0, 0);
    $this->assertFalse($entity->isExpired());
  }

  /**
   * Tests isExpired() returns FALSE when exp fields are empty.
   */
  public function testIsExpiredReturnsFalseWhenNoExpData(): void {
    $entity = $this->createMockPaymentMethod('card', FALSE, 0, 0);
    $this->assertFalse($entity->isExpired());
  }

  /**
   * Creates a mock BillingPaymentMethod.
   */
  protected function createMockPaymentMethod(string $type, bool $isDefault, int $expMonth, int $expYear): BillingPaymentMethod {
    $fields = [
      'type' => $this->createFieldMock($type),
      'is_default' => $this->createFieldMock($isDefault ? '1' : '0'),
      'card_exp_month' => $this->createFieldMock((string) $expMonth),
      'card_exp_year' => $this->createFieldMock((string) $expYear),
    ];

    $entity = $this->getMockBuilder(BillingPaymentMethod::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $entity->method('get')
      ->willReturnCallback(function (string $fieldName) use ($fields) {
        return $fields[$fieldName] ?? $this->createFieldMock(NULL);
      });

    return $entity;
  }

  /**
   * Creates a simple field value object.
   *
   * Uses stdClass instead of mock — PHP 8.4 breaks dynamic properties
   * on PHPUnit mock objects.
   */
  protected function createFieldMock($value): object {
    return (object) ['value' => $value];
  }

}
