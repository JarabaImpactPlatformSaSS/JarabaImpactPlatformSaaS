<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Entity;

use Drupal\jaraba_andalucia_ei\Entity\MaterialDidacticoEiInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests MaterialDidacticoEi constants.
 *
 * @group jaraba_andalucia_ei
 */
class MaterialDidacticoEiConstantsTest extends UnitTestCase {

  /**
   * Tests TIPOS_MATERIAL has expected types.
   */
  public function testTiposMaterialComplete(): void {
    $expected = [
      'documento',
      'video',
      'presentacion',
      'guia',
      'ejercicio',
      'evaluacion',
      'recurso_externo',
    ];

    $keys = array_keys(MaterialDidacticoEiInterface::TIPOS_MATERIAL);
    foreach ($expected as $tipo) {
      $this->assertContains($tipo, $keys, "Missing tipo_material: $tipo");
    }
  }

  /**
   * Tests all types have non-empty labels.
   */
  public function testTiposMaterialHaveLabels(): void {
    foreach (MaterialDidacticoEiInterface::TIPOS_MATERIAL as $key => $label) {
      $this->assertNotEmpty($label, "Empty label for tipo_material: $key");
    }
  }

}
