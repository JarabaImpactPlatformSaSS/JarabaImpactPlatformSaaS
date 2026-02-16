<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for XAdES-EPES signing service integration.
 *
 * @group jaraba_facturae
 */
class FacturaeXAdESKernelTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the XAdES service class exists.
   */
  public function testXAdESServiceClassExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Service\FacturaeXAdESService::class),
      'FacturaeXAdESService class should exist.'
    );
  }

  /**
   * Tests that the XAdES service has required methods.
   */
  public function testXAdESServiceHasRequiredMethods(): void {
    $methods = ['signDocument', 'verifySignature', 'getCertificateInfo'];
    foreach ($methods as $method) {
      $this->assertTrue(
        method_exists(\Drupal\jaraba_facturae\Service\FacturaeXAdESService::class, $method),
        "FacturaeXAdESService should have $method method."
      );
    }
  }

}
