<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\KitDigitalAgreement;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for KitDigitalAgreement entity constants and configuration.
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Entity\KitDigitalAgreement
 */
class KitDigitalAgreementTest extends UnitTestCase {

  /**
   * Tests that PAQUETES constant has exactly 5 entries.
   */
  public function testPaquetesConstant(): void {
    $paquetes = KitDigitalAgreement::PAQUETES;
    $this->assertCount(5, $paquetes);
    $this->assertArrayHasKey('comercio_digital', $paquetes);
    $this->assertArrayHasKey('productor_digital', $paquetes);
    $this->assertArrayHasKey('profesional_digital', $paquetes);
    $this->assertArrayHasKey('despacho_digital', $paquetes);
    $this->assertArrayHasKey('emprendedor_digital', $paquetes);
  }

  /**
   * Tests that SEGMENTOS constant has 5 segments (I-V).
   */
  public function testSegmentosConstant(): void {
    $segmentos = KitDigitalAgreement::SEGMENTOS;
    $this->assertCount(5, $segmentos);
    $this->assertArrayHasKey('I', $segmentos);
    $this->assertArrayHasKey('II', $segmentos);
    $this->assertArrayHasKey('III', $segmentos);
    $this->assertArrayHasKey('IV', $segmentos);
    $this->assertArrayHasKey('V', $segmentos);
  }

  /**
   * Tests that STATUSES constant has the complete lifecycle.
   */
  public function testStatusesConstant(): void {
    $statuses = KitDigitalAgreement::STATUSES;
    $this->assertCount(7, $statuses);

    $expectedStatuses = [
      'draft',
      'signed',
      'active',
      'justification_pending',
      'justified',
      'paid',
      'expired',
    ];

    foreach ($expectedStatuses as $status) {
      $this->assertArrayHasKey($status, $statuses, "Status '$status' should exist in lifecycle.");
    }
  }

  /**
   * Tests that all status labels are non-empty Spanish strings.
   */
  public function testStatusLabelsNotEmpty(): void {
    foreach (KitDigitalAgreement::STATUSES as $key => $label) {
      $this->assertNotEmpty($label, "Status '$key' should have a non-empty label.");
      $this->assertIsString($label);
    }
  }

  /**
   * Tests paquete labels contain the vertical name.
   */
  public function testPaqueteLabelsContainVertical(): void {
    $verticalMap = [
      'comercio_digital' => 'ComercioConecta',
      'productor_digital' => 'AgroConecta',
      'profesional_digital' => 'ServiciosConecta',
      'despacho_digital' => 'JarabaLex',
      'emprendedor_digital' => 'Emprendimiento',
    ];

    foreach ($verticalMap as $key => $vertical) {
      $label = KitDigitalAgreement::PAQUETES[$key];
      $this->assertStringContainsString($vertical, $label, "Paquete '$key' label should contain '$vertical'.");
    }
  }

}
