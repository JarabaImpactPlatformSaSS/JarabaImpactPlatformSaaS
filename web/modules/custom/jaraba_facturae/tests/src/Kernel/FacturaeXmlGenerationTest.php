<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae XML generation.
 *
 * @group jaraba_facturae
 */
class FacturaeXmlGenerationTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the FacturaeXmlService class exists and is loadable.
   */
  public function testXmlServiceClassExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Service\FacturaeXmlService::class),
      'FacturaeXmlService class should exist.'
    );
  }

  /**
   * Tests that the XML service has the buildFacturaeXml method.
   */
  public function testXmlServiceHasBuildMethod(): void {
    $this->assertTrue(
      method_exists(\Drupal\jaraba_facturae\Service\FacturaeXmlService::class, 'buildFacturaeXml'),
      'FacturaeXmlService should have buildFacturaeXml method.'
    );
  }

}
