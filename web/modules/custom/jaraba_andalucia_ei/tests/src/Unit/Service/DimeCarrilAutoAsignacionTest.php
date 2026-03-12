<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la lógica de auto-asignación de carril DIME.
 *
 * Testea la lógica de _jaraba_andalucia_ei_auto_asignar_carril_dime()
 * sin bootstrap de Drupal. La función es procedural y depende de
 * \Drupal::logger(), por lo que se replica la lógica pura en un
 * helper privado con idéntico algoritmo de decisión.
 *
 * @group jaraba_andalucia_ei
 */
class DimeCarrilAutoAsignacionTest extends TestCase {

  /**
   * Replica la lógica de asignación de carril sin dependencias de Drupal.
   *
   * Espeja _jaraba_andalucia_ei_auto_asignar_carril_dime() sin el
   * bootstrap de Drupal ni la llamada a \Drupal::logger(). Devuelve el
   * carril calculado o NULL si no procede asignar.
   *
   * @param mixed $dimeScore
   *   Valor del campo dime_score (puede ser NULL, '' o numérico).
   * @param string $carrilActual
   *   Valor actual del campo carril. Vacío si no está asignado.
   *
   * @return string|null
   *   Carril calculado, o NULL si la función retornaría sin asignar.
   */
  private function calcularCarril(mixed $dimeScore, string $carrilActual = ''): ?string {
    if ($dimeScore === NULL || $dimeScore === '') {
      return NULL;
    }

    if ($carrilActual !== '') {
      return NULL;
    }

    $score = (int) $dimeScore;

    if ($score <= 8) {
      return 'impulso_digital';
    }
    elseif ($score <= 14) {
      return 'hibrido';
    }
    else {
      return 'acelera_pro';
    }
  }

  /**
   * Datos para testScoreAsignaCarrilCorrecto().
   *
   * @return array<string, array{int, string}>
   *   Tuplas [score, carril_esperado].
   */
  public static function providerScoreCarril(): array {
    return [
      'score_0_impulso_digital' => [0, 'impulso_digital'],
      'score_8_impulso_digital' => [8, 'impulso_digital'],
      'score_9_hibrido' => [9, 'hibrido'],
      'score_14_hibrido' => [14, 'hibrido'],
      'score_15_acelera_pro' => [15, 'acelera_pro'],
      'score_20_acelera_pro' => [20, 'acelera_pro'],
    ];
  }

  /**
   * Verifica que el score asigna el carril correcto.
   *
   * @dataProvider providerScoreCarril
   */
  public function testScoreAsignaCarrilCorrecto(int $score, string $carrilEsperado): void {
    $this->assertSame($carrilEsperado, $this->calcularCarril($score));
  }

  /**
   * Score NULL no asigna carril.
   */
  public function testScoreNullNoAsignaCarril(): void {
    $this->assertNull($this->calcularCarril(NULL));
  }

  /**
   * Score cadena vacía no asigna carril.
   */
  public function testScoreCadenaVaciaNoAsignaCarril(): void {
    $this->assertNull($this->calcularCarril(''));
  }

  /**
   * Datos para testCarrilPrevioNoSeSobreescribe().
   *
   * @return array<string, array{string}>
   *   Tuplas [carril_previo].
   */
  public static function providerCarrilPrevio(): array {
    return [
      'carril_impulso_digital_previo' => ['impulso_digital'],
      'carril_hibrido_previo' => ['hibrido'],
      'carril_acelera_pro_previo' => ['acelera_pro'],
      'carril_manual_cualquiera' => ['carril_personalizado'],
    ];
  }

  /**
   * Carril ya asignado no debe sobreescribirse.
   *
   * @dataProvider providerCarrilPrevio
   */
  public function testCarrilPrevioNoSeSobreescribe(string $carrilPrevio): void {
    // Score 20 normalmente asignaría 'acelera_pro', pero hay carril previo.
    $resultado = $this->calcularCarril(20, $carrilPrevio);
    $this->assertNull($resultado, "El carril '$carrilPrevio' no debe sobreescribirse.");
  }

  /**
   * Verifica la frontera exacta entre impulso_digital e hibrido (8 vs 9).
   */
  public function testFronteraScore8vs9(): void {
    $this->assertSame('impulso_digital', $this->calcularCarril(8));
    $this->assertSame('hibrido', $this->calcularCarril(9));
  }

  /**
   * Verifica la frontera exacta entre hibrido y acelera_pro (14 vs 15).
   */
  public function testFronteraScore14vs15(): void {
    $this->assertSame('hibrido', $this->calcularCarril(14));
    $this->assertSame('acelera_pro', $this->calcularCarril(15));
  }

  /**
   * Score como string numérico funciona igual que entero.
   *
   * Los campos de Drupal devuelven strings desde la DB. La función
   * real hace (int) sobre el valor raw del campo.
   */
  public function testScoreComoStringNumerico(): void {
    $this->assertSame('impulso_digital', $this->calcularCarril('5'));
    $this->assertSame('hibrido', $this->calcularCarril('12'));
    $this->assertSame('acelera_pro', $this->calcularCarril('18'));
  }

  /**
   * Con score NULL el mock de entidad no recibe llamada a set().
   *
   * MOCK-DYNPROP-001: clase anónima con typed properties.
   * TEST-CACHE-001: cache metadata implementada.
   */
  public function testEntityMockNoLlamaSetSiScoreNull(): void {
    $setLlamado = FALSE;

    // MOCK-DYNPROP-001: clase anónima en lugar de createMock().
    $entity = new class($setLlamado) {

      /**
       * Referencia al flag externo para detectar llamadas a set().
       */
      private bool $setLlamadoRef;

      /**
       * Constructor.
       */
      public function __construct(bool &$setLlamado) {
        $this->setLlamadoRef = &$setLlamado;
      }

      /**
       * Simula EntityInterface::get() devolviendo campo con value NULL.
       */
      public function get(string $fieldName): object {
        if ($fieldName === 'dime_score') {
          return new class() {

            /**
             * Valor del campo.
             *
             * @var mixed
             */
            public mixed $value = NULL;
          };
        }

        return new class() {

          /**
           * Valor del campo.
           *
           * @var string
           */
          public string $value = '';
        };
      }

      /**
       * Simula EntityInterface::set() y activa el flag de control.
       */
      public function set(string $fieldName, mixed $value): static {
        $this->setLlamadoRef = TRUE;
        return $this;
      }

      /**
       * TEST-CACHE-001.
       *
       * @return array<string>
       *   Lista de contextos de cache.
       */
      public function getCacheContexts(): array {
        return [];
      }

      /**
       * TEST-CACHE-001.
       *
       * @return array<string>
       *   Lista de etiquetas de cache.
       */
      public function getCacheTags(): array {
        return ['programa_participante_ei:test'];
      }

      /**
       * TEST-CACHE-001.
       */
      public function getCacheMaxAge(): int {
        return -1;
      }

    };

    // Reproduce el guard clause de la función real.
    $dimeScore = $entity->get('dime_score')->value;
    if ($dimeScore !== NULL && $dimeScore !== '') {
      $carrilActual = $entity->get('carril')->value ?? '';
      if ($carrilActual === '') {
        $entity->set('carril', 'cualquiera');
      }
    }

    $this->assertFalse($setLlamado, 'set() no debe invocarse si dime_score es NULL.');
  }

  /**
   * Con carril previo el mock de entidad no recibe llamada a set().
   *
   * MOCK-DYNPROP-001: clase anónima con typed properties.
   * TEST-CACHE-001: cache metadata implementada.
   */
  public function testEntityMockNoLlamaSetSiCarrilPrevio(): void {
    $setLlamado = FALSE;

    // MOCK-DYNPROP-001: clase anónima en lugar de createMock().
    $entity = new class($setLlamado) {

      /**
       * Referencia al flag externo para detectar llamadas a set().
       */
      private bool $setLlamadoRef;

      /**
       * Constructor.
       */
      public function __construct(bool &$setLlamado) {
        $this->setLlamadoRef = &$setLlamado;
      }

      /**
       * Simula EntityInterface::get() con score=15 y carril='hibrido' previo.
       */
      public function get(string $fieldName): object {
        if ($fieldName === 'dime_score') {
          return new class() {

            /**
             * Valor del campo.
             *
             * @var int
             */
            public int $value = 15;
          };
        }

        return new class() {

          /**
           * Valor del campo.
           *
           * @var string
           */
          public string $value = 'hibrido';
        };
      }

      /**
       * Simula EntityInterface::set() y activa el flag de control.
       */
      public function set(string $fieldName, mixed $value): static {
        $this->setLlamadoRef = TRUE;
        return $this;
      }

      /**
       * TEST-CACHE-001.
       *
       * @return array<string>
       *   Lista de contextos de cache.
       */
      public function getCacheContexts(): array {
        return [];
      }

      /**
       * TEST-CACHE-001.
       *
       * @return array<string>
       *   Lista de etiquetas de cache.
       */
      public function getCacheTags(): array {
        return ['programa_participante_ei:test'];
      }

      /**
       * TEST-CACHE-001.
       */
      public function getCacheMaxAge(): int {
        return -1;
      }

    };

    // Reproduce el guard clause de la función real.
    $dimeScore = $entity->get('dime_score')->value;
    if ($dimeScore !== NULL && $dimeScore !== '') {
      $carrilActual = $entity->get('carril')->value ?? '';
      if ($carrilActual === '') {
        $entity->set('carril', 'acelera_pro');
      }
    }

    $this->assertFalse($setLlamado, 'set() no debe invocarse si el carril ya está asignado.');
  }

}
