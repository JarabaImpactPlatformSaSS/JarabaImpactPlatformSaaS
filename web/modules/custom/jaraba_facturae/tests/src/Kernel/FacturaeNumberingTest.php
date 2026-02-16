<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae numbering service.
 *
 * @group jaraba_facturae
 */
class FacturaeNumberingTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the numbering service class exists.
   */
  public function testNumberingServiceClassExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Service\FacturaeNumberingService::class),
      'FacturaeNumberingService class should exist.'
    );
  }

  /**
   * Tests that the numbering service has the formatNumber method.
   */
  public function testFormatNumberMethodExists(): void {
    $this->assertTrue(
      method_exists(\Drupal\jaraba_facturae\Service\FacturaeNumberingService::class, 'formatNumber'),
      'FacturaeNumberingService should have formatNumber method.'
    );
  }

}
