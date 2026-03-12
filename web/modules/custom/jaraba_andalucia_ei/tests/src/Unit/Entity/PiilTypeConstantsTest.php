<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Entity;

use Drupal\jaraba_andalucia_ei\Entity\ActuacionSto;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests PIIL type constants and mappings on entities.
 *
 * Sprint 14: Verifica que las constantes de tipos, fases, y mapas
 * de migración legacy son coherentes con la normativa PIIL.
 *
 * @group jaraba_andalucia_ei
 */
class PiilTypeConstantsTest extends UnitTestCase {

  /**
   * Tests SesionProgramadaEi TIPOS_SESION has all required types.
   */
  public function testSesionTiposSesionComplete(): void {
    $expected = [
      'orientacion_laboral_individual',
      'orientacion_laboral_grupal',
      'orientacion_insercion_individual',
      'orientacion_insercion_grupal',
      'sesion_formativa',
      'tutoria_seguimiento',
    ];

    $keys = array_keys(SesionProgramadaEiInterface::TIPOS_SESION);
    foreach ($expected as $tipo) {
      $this->assertContains($tipo, $keys, "Missing tipo_sesion: $tipo");
    }
  }

  /**
   * Tests FASE_POR_TIPO covers all TIPOS_SESION.
   */
  public function testSesionFasePorTipoCoversAll(): void {
    foreach (array_keys(SesionProgramadaEiInterface::TIPOS_SESION) as $tipo) {
      $this->assertArrayHasKey(
        $tipo,
        SesionProgramadaEiInterface::FASE_POR_TIPO,
        "FASE_POR_TIPO missing entry for: $tipo"
      );
    }
  }

  /**
   * Tests fase values are valid PIIL phases.
   */
  public function testSesionFaseValuesValid(): void {
    $validFases = ['atencion', 'insercion', 'transversal'];

    foreach (SesionProgramadaEiInterface::FASE_POR_TIPO as $tipo => $fase) {
      $this->assertContains(
        $fase,
        $validFases,
        "Invalid fase '$fase' for tipo '$tipo'"
      );
    }
  }

  /**
   * Tests legacy map targets all existing PIIL types.
   */
  public function testSesionLegacyMapTargetsExist(): void {
    $validTypes = array_keys(SesionProgramadaEiInterface::TIPOS_SESION);

    foreach (SesionProgramadaEiInterface::TIPOS_SESION_LEGACY_MAP as $legacy => $new) {
      $this->assertContains(
        $new,
        $validTypes,
        "Legacy map for '$legacy' targets non-existent type: $new"
      );
    }
  }

  /**
   * Tests legacy map does not contain current types as keys.
   */
  public function testSesionLegacyMapNoCurrentKeysOverlap(): void {
    $currentTypes = array_keys(SesionProgramadaEiInterface::TIPOS_SESION);
    $legacyKeys = array_keys(SesionProgramadaEiInterface::TIPOS_SESION_LEGACY_MAP);

    $overlap = array_intersect($currentTypes, $legacyKeys);
    $this->assertEmpty(
      $overlap,
      'Legacy map should not contain current TIPOS_SESION keys: ' . implode(', ', $overlap)
    );
  }

  /**
   * Tests ActuacionSto TIPOS_ACTUACION has all required types.
   */
  public function testActuacionTiposComplete(): void {
    $expected = [
      'orientacion_laboral_individual',
      'orientacion_laboral_grupal',
      'orientacion_insercion_individual',
      'orientacion_insercion_grupal',
      'formacion',
      'tutoria',
      'prospeccion',
      'intermediacion',
    ];

    $keys = array_keys(ActuacionSto::TIPOS_ACTUACION);
    foreach ($expected as $tipo) {
      $this->assertContains($tipo, $keys, "Missing tipo_actuacion: $tipo");
    }
  }

  /**
   * Tests ActuacionSto FASE_POR_TIPO covers all TIPOS_ACTUACION.
   */
  public function testActuacionFasePorTipoCoversAll(): void {
    foreach (array_keys(ActuacionSto::TIPOS_ACTUACION) as $tipo) {
      $this->assertArrayHasKey(
        $tipo,
        ActuacionSto::FASE_POR_TIPO,
        "FASE_POR_TIPO missing entry for: $tipo"
      );
    }
  }

  /**
   * Tests orientación laboral types map to fase atencion.
   */
  public function testOrientacionLaboralMapsToAtencion(): void {
    $this->assertEquals(
      'atencion',
      SesionProgramadaEiInterface::FASE_POR_TIPO['orientacion_laboral_individual']
    );
    $this->assertEquals(
      'atencion',
      SesionProgramadaEiInterface::FASE_POR_TIPO['orientacion_laboral_grupal']
    );
  }

  /**
   * Tests orientación inserción types map to fase insercion.
   */
  public function testOrientacionInsercionMapsToInsercion(): void {
    $this->assertEquals(
      'insercion',
      SesionProgramadaEiInterface::FASE_POR_TIPO['orientacion_insercion_individual']
    );
    $this->assertEquals(
      'insercion',
      SesionProgramadaEiInterface::FASE_POR_TIPO['orientacion_insercion_grupal']
    );
  }

  /**
   * Tests sesion formativa maps to fase atencion.
   */
  public function testFormativaMapsToAtencion(): void {
    $this->assertEquals(
      'atencion',
      SesionProgramadaEiInterface::FASE_POR_TIPO['sesion_formativa']
    );
  }

  /**
   * Tests ActuacionSto legacy map targets exist.
   */
  public function testActuacionLegacyMapTargetsExist(): void {
    $validTypes = array_keys(ActuacionSto::TIPOS_ACTUACION);

    foreach (ActuacionSto::TIPOS_LEGACY_MAP as $legacy => $new) {
      $this->assertContains(
        $new,
        $validTypes,
        "ActuacionSto legacy map for '$legacy' targets non-existent type: $new"
      );
    }
  }

}
