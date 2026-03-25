<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Validación de criterios de inserción laboral ICV 2025.
 *
 * Normativa §5.2.B Pautas Gestión Técnica:
 * - Cuenta ajena: ≥4 meses alta jornada completa
 * - Cuenta propia: ≥4 meses RETA
 * - Sector agrario: ≥3 meses Sistema Especial Agrario
 * - Combinable entre regímenes SS excepto con Sistema Especial Agrario
 * - Períodos no consecutivos: mínimo 1 mes continuado (completa) o 2 meses (parcial)
 */
class InsercionValidatorService {

  use StringTranslationTrait;

  /**
   * Meses mínimos por tipo de inserción.
   */
  private const MESES_MINIMOS = [
    'cuenta_ajena' => 4,
    'cuenta_propia' => 4,
    'agrario' => 3,
  ];

  /**
   * Desglose fiscal del incentivo a la participación (§5.1.C, Anexo IV).
   */
  public const INCENTIVO_BASE = 528.00;
  public const INCENTIVO_IRPF_PCT = 2.0;
  public const INCENTIVO_IRPF = 10.56;
  public const INCENTIVO_NETO = 517.44;

  /**
   * INS-04: BBRR §5.a.4.b — Mínimo media jornada para inserción parcial.
   */
  public const JORNADA_PARCIAL_MINIMA = 0.5;

  /**
   * INS-05: BBRR §5.a.4.c — NIF de la entidad beneficiaria (PED S.L.).
   */
  public const NIF_ENTIDAD_BENEFICIARIA = 'B93750271';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Valida si un participante cumple los criterios de inserción laboral.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return array{valid: bool, meses_alta: float, meses_requeridos: int, message: string}
   */
  public function validateInsercion(object $participante): array {
    $tipo = $participante->get('tipo_insercion')->value ?? NULL;
    $fechaInsercion = $participante->get('fecha_insercion')->value ?? NULL;

    if (!$tipo || !$fechaInsercion) {
      return [
        'valid' => FALSE,
        'meses_alta' => 0,
        'meses_requeridos' => 4,
        'message' => (string) $this->t('Tipo y fecha de inserción requeridos.'),
      ];
    }

    $minMeses = self::MESES_MINIMOS[$tipo] ?? 4;
    $mesesAlta = $this->calcularMesesAlta($fechaInsercion);

    $valid = $mesesAlta >= $minMeses;

    return [
      'valid' => $valid,
      'meses_alta' => $mesesAlta,
      'meses_requeridos' => $minMeses,
      'message' => $valid
        ? (string) $this->t('Inserción válida: @meses meses (mínimo @min).', [
          '@meses' => number_format($mesesAlta, 1),
          '@min' => $minMeses,
        ])
        : (string) $this->t('Insuficiente: @meses/@min meses de alta.', [
          '@meses' => number_format($mesesAlta, 1),
          '@min' => $minMeses,
        ]),
    ];
  }

  /**
   * Valida criterios específicos del sector agrario.
   *
   * §5.2.B.1: El sector agrario NO puede combinar con otros regímenes SS
   * para el cómputo de inserción. Mínimo 3 meses Sistema Especial.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return array{valid: bool, message: string}
   */
  public function validateAgrario(object $participante): array {
    $tipo = $participante->get('tipo_insercion')->value ?? '';

    if ($tipo !== 'agrario') {
      return ['valid' => TRUE, 'message' => ''];
    }

    $fechaInsercion = $participante->get('fecha_insercion')->value ?? NULL;
    if (!$fechaInsercion) {
      return [
        'valid' => FALSE,
        'message' => (string) $this->t('Fecha de inserción requerida para sector agrario.'),
      ];
    }

    $meses = $this->calcularMesesAlta($fechaInsercion);

    if ($meses < 3) {
      return [
        'valid' => FALSE,
        'message' => (string) $this->t('Sector agrario requiere mínimo 3 meses en Sistema Especial Agrario (@actual meses).', [
          '@actual' => number_format($meses, 1),
        ]),
      ];
    }

    return [
      'valid' => TRUE,
      'message' => (string) $this->t('Inserción agraria válida: @meses meses.', [
        '@meses' => number_format($meses, 1),
      ]),
    ];
  }

  /**
   * Calcula el desglose fiscal del incentivo a la participación.
   *
   * Anexo IV ICV 2025: Base 528€, IRPF 2% = 10,56€, neto 517,44€.
   *
   * @return array{base_imponible: float, irpf_porcentaje: float, irpf_importe: float, total_percibir: float}
   */
  public static function getDesgloseFiscalIncentivo(): array {
    return [
      'base_imponible' => self::INCENTIVO_BASE,
      'irpf_porcentaje' => self::INCENTIVO_IRPF_PCT,
      'irpf_importe' => self::INCENTIVO_IRPF,
      'total_percibir' => self::INCENTIVO_NETO,
    ];
  }

  /**
   * Computes proportional months for part-time employment.
   *
   * INS-04: BBRR §5.a.4.b — Tiempo parcial: mínimo media jornada,
   * cómputo proporcional.
   *
   * @param int $mesesAlta
   *   Months registered in Social Security.
   * @param float $jornadaPorcentaje
   *   Employment percentage (0.5 = half-time, 1.0 = full-time).
   *
   * @return float
   *   Proportional months. Returns 0 if below minimum threshold.
   */
  public function computoProporcionalJornada(int $mesesAlta, float $jornadaPorcentaje): float {
    if ($jornadaPorcentaje < self::JORNADA_PARCIAL_MINIMA) {
      return 0.0;
    }
    return $mesesAlta * $jornadaPorcentaje;
  }

  /**
   * Validates that insertion is NOT in beneficiary entity or linked companies.
   *
   * INS-05: BBRR §5.a.4.c — No inserción en entidad beneficiaria,
   * grupo empresa o vinculadas.
   *
   * @param string $nifEmpresa
   *   NIF/CIF of the hiring company.
   *
   * @return bool
   *   TRUE if valid (NOT self-hire), FALSE if auto-contratación detected.
   */
  public function validateNoAutocontratacion(string $nifEmpresa): bool {
    $nifNormalized = strtoupper(trim($nifEmpresa));
    if ($nifNormalized === self::NIF_ENTIDAD_BENEFICIARIA) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Calcula meses de alta desde la fecha de inserción hasta hoy.
   *
   * @param string $fechaInsercion
   *   Fecha de inserción en formato Y-m-d\TH:i:s o Y-m-d.
   *
   * @return float
   *   Meses de alta (con decimales).
   */
  private function calcularMesesAlta(string $fechaInsercion): float {
    try {
      $inicio = new \DateTimeImmutable(str_replace('T', ' ', $fechaInsercion));
      $ahora = new \DateTimeImmutable();
      $diff = $inicio->diff($ahora);
      return (float) $diff->m + ($diff->y * 12) + ($diff->d / 30);
    }
    catch (\Throwable) {
      return 0.0;
    }
  }

}
