<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for FACe client service integration.
 *
 * @group jaraba_facturae
 */
class FACeClientKernelTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the FACe client service class exists.
   */
  public function testFACeClientServiceClassExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Service\FACeClientService::class),
      'FACeClientService class should exist.'
    );
  }

  /**
   * Tests that the FACe client has all SOAP operation methods.
   */
  public function testFACeClientHasSoapOperations(): void {
    $methods = ['sendInvoice', 'queryInvoice', 'queryInvoiceList', 'cancelInvoice', 'testConnection'];
    foreach ($methods as $method) {
      $this->assertTrue(
        method_exists(\Drupal\jaraba_facturae\Service\FACeClientService::class, $method),
        "FACeClientService should have $method method."
      );
    }
  }

}
