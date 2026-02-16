<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae entity schema definitions.
 *
 * @group jaraba_facturae
 */
class FacturaeEntitySchemaTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests FacturaeDocument entity class exists and has correct annotation.
   */
  public function testFacturaeDocumentEntityExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Entity\FacturaeDocument::class),
      'FacturaeDocument entity class should exist.'
    );
  }

  /**
   * Tests FacturaeTenantConfig entity class exists.
   */
  public function testFacturaeTenantConfigEntityExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Entity\FacturaeTenantConfig::class),
      'FacturaeTenantConfig entity class should exist.'
    );
  }

  /**
   * Tests FacturaeFaceLog entity class exists.
   */
  public function testFacturaeFaceLogEntityExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Entity\FacturaeFaceLog::class),
      'FacturaeFaceLog entity class should exist.'
    );
  }

}
