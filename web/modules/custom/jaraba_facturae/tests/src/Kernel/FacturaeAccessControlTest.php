<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for Facturae entity access control.
 *
 * @group jaraba_facturae
 */
class FacturaeAccessControlTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that FacturaeDocumentAccessControlHandler class exists.
   */
  public function testDocumentAccessControlHandlerExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Access\FacturaeDocumentAccessControlHandler::class),
      'FacturaeDocumentAccessControlHandler class should exist.'
    );
  }

  /**
   * Tests that FacturaeFaceLogAccessControlHandler class exists.
   */
  public function testFaceLogAccessControlHandlerExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Access\FacturaeFaceLogAccessControlHandler::class),
      'FacturaeFaceLogAccessControlHandler class should exist.'
    );
  }

}
