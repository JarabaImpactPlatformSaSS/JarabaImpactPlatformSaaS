<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo fiscal para IRPF e IVA.
 *
 * Implementa los calculos de los principales impuestos espanoles
 * usando los tramos y tipos vigentes para 2026. Los calculos son
 * orientativos y no sustituyen al asesoramiento fiscal profesional.
 *
 * IMPUESTOS SOPORTADOS:
 * - IRPF: Calculo por tramos con deducciones personales y familiares.
 * - IVA: Calculo con tipos general (21%), reducido (10%),
 *   superreducido (4%) y exento (0%).
 *
 * NOTA: Los tramos corresponden a la escala estatal. Las comunidades
 * autonomas tienen competencia sobre la escala autonomica, que se
 * aplica de forma complementaria.
 */
class TaxCalculatorService {

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el IRPF para una situacion personal dada.
   *
   * @param array $params
   *   Parametros del calculo:
   *   - gross_income: (float) Rendimiento bruto del trabajo.
   *   - personal_situation: (string) 'soltero', 'casado', 'familia_monoparental'.
   *   - num_children: (int) Numero de hijos menores o dependientes.
   *   - disability_percentage: (float) Porcentaje de discapacidad (0-100).
   *   - autonomous_community: (string) Comunidad autonoma (default: 'general').
   *
   * @return array
   *   Resultado del calculo:
   *   - base_imponible: (float) Base imponible tras reducciones.
   *   - cuota_integra: (float) Cuota integra antes de deducciones.
   *   - deducciones: (float) Total de deducciones aplicadas.
   *   - cuota_liquida: (float) Cuota liquida final.
   *   - tipo_efectivo: (float) Tipo efectivo en porcentaje.
   *   - tramos: (array) Desglose detallado por tramo.
   */
  public function calculateIrpf(array $params): array {
    $grossIncome = (float) ($params['gross_income'] ?? 0);
    $situation = $params['personal_situation'] ?? 'soltero';
    $numChildren = (int) ($params['num_children'] ?? 0);
    $disabilityPercentage = (float) ($params['disability_percentage'] ?? 0);
    $community = $params['autonomous_community'] ?? 'general';

    if ($grossIncome <= 0) {
      return [
        'base_imponible' => 0.0,
        'cuota_integra' => 0.0,
        'deducciones' => 0.0,
        'cuota_liquida' => 0.0,
        'tipo_efectivo' => 0.0,
        'tramos' => [],
      ];
    }

    // 1. Calcular deducciones personales y familiares.
    $deducciones = $this->getPersonalDeductions($situation, $numChildren, $disabilityPercentage);

    // 2. Base imponible = ingresos brutos - deducciones.
    $baseImponible = max(0, $grossIncome - $deducciones);

    // 3. Calcular cuota integra aplicando tramos.
    $brackets = $this->getIrpfBrackets($community);
    $cuotaIntegra = 0.0;
    $tramos = [];
    $remainingBase = $baseImponible;

    foreach ($brackets as $bracket) {
      [$from, $to, $rate] = $bracket;

      if ($remainingBase <= 0) {
        break;
      }

      $bracketWidth = $to - $from;
      $taxableInBracket = min($remainingBase, $bracketWidth);
      $taxInBracket = $taxableInBracket * $rate;
      $cuotaIntegra += $taxInBracket;

      $tramos[] = [
        'desde' => $from,
        'hasta' => min($to, $from + $taxableInBracket),
        'tipo' => round($rate * 100, 1),
        'base_tramo' => round($taxableInBracket, 2),
        'cuota_tramo' => round($taxInBracket, 2),
      ];

      $remainingBase -= $taxableInBracket;
    }

    // 4. Cuota liquida (no puede ser negativa).
    $cuotaLiquida = max(0, $cuotaIntegra);

    // 5. Tipo efectivo.
    $tipoEfectivo = $grossIncome > 0 ? ($cuotaLiquida / $grossIncome) * 100 : 0.0;

    $result = [
      'base_imponible' => round($baseImponible, 2),
      'cuota_integra' => round($cuotaIntegra, 2),
      'deducciones' => round($deducciones, 2),
      'cuota_liquida' => round($cuotaLiquida, 2),
      'tipo_efectivo' => round($tipoEfectivo, 2),
      'tramos' => $tramos,
    ];

    $this->logger->info('IRPF calculado: base @base, cuota @cuota, tipo efectivo @tipo%.', [
      '@base' => round($baseImponible, 2),
      '@cuota' => round($cuotaLiquida, 2),
      '@tipo' => round($tipoEfectivo, 2),
    ]);

    return $result;
  }

  /**
   * Calcula el IVA para un importe base y tipo de IVA.
   *
   * @param array $params
   *   Parametros del calculo:
   *   - base_amount: (float) Importe base imponible.
   *   - iva_type: (string) Tipo de IVA: 'general', 'reducido',
   *     'superreducido', 'exento'.
   *
   * @return array
   *   Resultado del calculo:
   *   - base: (float) Base imponible.
   *   - tipo: (float) Porcentaje de IVA aplicado (21, 10, 4, 0).
   *   - cuota: (float) Cuota de IVA.
   *   - total: (float) Total con IVA incluido.
   */
  public function calculateIva(array $params): array {
    $baseAmount = (float) ($params['base_amount'] ?? 0);
    $ivaType = $params['iva_type'] ?? 'general';

    $tipo = match ($ivaType) {
      'general' => 21.0,
      'reducido' => 10.0,
      'superreducido' => 4.0,
      'exento' => 0.0,
      default => 21.0,
    };

    $cuota = $baseAmount * ($tipo / 100);
    $total = $baseAmount + $cuota;

    $this->logger->info('IVA calculado: base @base, tipo @tipo%, cuota @cuota.', [
      '@base' => round($baseAmount, 2),
      '@tipo' => $tipo,
      '@cuota' => round($cuota, 2),
    ]);

    return [
      'base' => round($baseAmount, 2),
      'tipo' => $tipo,
      'cuota' => round($cuota, 2),
      'total' => round($total, 2),
    ];
  }

  /**
   * Obtiene los tramos del IRPF vigentes para 2026.
   *
   * Escala estatal general. Las comunidades autonomas tienen sus propios
   * tramos complementarios que se aplican de forma adicional.
   *
   * @param string $community
   *   Comunidad autonoma (actualmente solo 'general' esta implementado).
   *
   * @return array
   *   Array de tramos. Cada tramo: [desde, hasta, tipo].
   */
  protected function getIrpfBrackets(string $community): array {
    // Escala estatal vigente 2026.
    // @todo Implementar escalas autonomicas para cada comunidad.
    return [
      [0, 12450, 0.19],
      [12450, 20200, 0.24],
      [20200, 35200, 0.30],
      [35200, 60000, 0.37],
      [60000, 300000, 0.45],
      [300000, PHP_FLOAT_MAX, 0.47],
    ];
  }

  /**
   * Calcula las deducciones personales y familiares.
   *
   * @param string $situation
   *   Situacion personal: 'soltero', 'casado', 'familia_monoparental'.
   * @param int $children
   *   Numero de hijos menores o dependientes.
   * @param float $disability
   *   Porcentaje de discapacidad (0-100).
   *
   * @return float
   *   Total de deducciones en euros.
   */
  protected function getPersonalDeductions(string $situation, int $children, float $disability): float {
    // Minimo personal del contribuyente.
    $deductions = 5550.0;

    // Incremento por situacion familiar.
    if ($situation === 'casado') {
      // Minimo por tributacion conjunta si procede.
      $deductions += 3400.0;
    }
    elseif ($situation === 'familia_monoparental') {
      $deductions += 2150.0;
    }

    // Minimo por descendientes.
    // 1er hijo: 2400, 2do: 2700, 3ro: 4000, 4to y siguientes: 4500.
    $childDeductions = [2400, 2700, 4000, 4500];
    for ($i = 0; $i < $children; $i++) {
      $deductions += $childDeductions[min($i, count($childDeductions) - 1)];
    }

    // Deduccion por discapacidad del contribuyente.
    if ($disability >= 33 && $disability < 65) {
      $deductions += 3000.0;
    }
    elseif ($disability >= 65) {
      $deductions += 9000.0;
    }

    return $deductions;
  }

}
