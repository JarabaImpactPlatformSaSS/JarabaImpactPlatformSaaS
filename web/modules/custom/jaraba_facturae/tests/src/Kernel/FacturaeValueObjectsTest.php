<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\jaraba_facturae\ValueObject\FACeResponse;
use Drupal\jaraba_facturae\ValueObject\FACeStatus;
use Drupal\jaraba_facturae\ValueObject\DIR3Unit;
use Drupal\jaraba_facturae\ValueObject\ValidationResult;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae value objects integration.
 *
 * @group jaraba_facturae
 */
class FacturaeValueObjectsTest extends KernelTestBase {

  protected static $modules = [
    'system',
  ];

  /**
   * Tests FACeResponse is final and immutable.
   */
  public function testFACeResponseIsFinalAndImmutable(): void {
    $reflection = new \ReflectionClass(FACeResponse::class);
    $this->assertTrue($reflection->isFinal());

    foreach (['success', 'code', 'description', 'registryNumber', 'csv'] as $prop) {
      $this->assertTrue($reflection->getProperty($prop)->isReadOnly());
    }
  }

  /**
   * Tests FACeStatus is final and immutable.
   */
  public function testFACeStatusIsFinalAndImmutable(): void {
    $reflection = new \ReflectionClass(FACeStatus::class);
    $this->assertTrue($reflection->isFinal());
    $this->assertTrue($reflection->getProperty('registryNumber')->isReadOnly());
  }

  /**
   * Tests FACeStatus lifecycle constants cover all expected states.
   */
  public function testFACeStatusLifecycleConstants(): void {
    $expectedConstants = [
      'STATUS_REGISTERED',
      'STATUS_REGISTERED_RCF',
      'STATUS_ACCOUNTED',
      'STATUS_OBLIGATION_RECOGNIZED',
      'STATUS_PAID',
      'STATUS_CANCELLATION_REQUESTED',
      'STATUS_CANCELLATION_ACCEPTED',
      'STATUS_CANCELLATION_REJECTED',
    ];

    foreach ($expectedConstants as $constant) {
      $this->assertTrue(
        defined(FACeStatus::class . '::' . $constant),
        "FACeStatus should define $constant."
      );
    }
  }

  /**
   * Tests DIR3Unit is final and has toArray.
   */
  public function testDIR3UnitStructure(): void {
    $reflection = new \ReflectionClass(DIR3Unit::class);
    $this->assertTrue($reflection->isFinal());
    $this->assertTrue($reflection->hasMethod('toArray'));
    $this->assertTrue($reflection->hasMethod('fromArray'));
  }

  /**
   * Tests ValidationResult is final and has factories.
   */
  public function testValidationResultStructure(): void {
    $reflection = new \ReflectionClass(ValidationResult::class);
    $this->assertTrue($reflection->isFinal());
    $this->assertTrue($reflection->hasMethod('success'));
    $this->assertTrue($reflection->hasMethod('failure'));
    $this->assertTrue($reflection->hasMethod('toArray'));
  }

}
