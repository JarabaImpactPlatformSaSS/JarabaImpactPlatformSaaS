<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\jaraba_facturae\ValueObject\ValidationResult;
use Drupal\jaraba_facturae\Service\FacturaeValidationService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae validation integration.
 *
 * @group jaraba_facturae
 */
class FacturaeValidationKernelTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the validation service class exists.
   */
  public function testValidationServiceExists(): void {
    $this->assertTrue(
      class_exists(FacturaeValidationService::class),
      'FacturaeValidationService class should exist.'
    );
  }

  /**
   * Tests that ValidationResult value object is immutable.
   */
  public function testValidationResultImmutable(): void {
    $result = new ValidationResult(TRUE, []);
    $this->assertTrue($result->valid);
    $this->assertEmpty($result->errors);

    // Verify readonly properties.
    $reflection = new \ReflectionClass($result);
    $validProp = $reflection->getProperty('valid');
    $this->assertTrue($validProp->isReadOnly());
  }

}
