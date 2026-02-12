<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo fiscal para IRPF e IVA.
 *
 * Implementa los calculos de los principales impuestos espanoles
 * usando los tramos y tipos vigentes. Los calculos son orientativos
 * y no sustituyen al asesoramiento fiscal profesional.
 *
 * IMPUESTOS SOPORTADOS:
 * - IRPF: Calculo por tramos con deducciones personales y familiares.
 * - IVA: Calculo con tipos general (21%), reducido (10%),
 *   superreducido (4%) y exento (0%).
 * - Recargo de equivalencia: 5.2% (general), 1.4% (reducido),
 *   0.5% (superreducido).
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
   * Calcula el IRPF para unos ingresos brutos y deducciones dados.
   *
   * @param float $grossIncome
   *   Rendimiento bruto del trabajo en euros.
   * @param float $deductions
   *   Total de deducciones aplicables en euros.
   * @param string $regime
   *   Regimen fiscal: 'general', etc.
   * @param int $year
   *   Ejercicio fiscal (e.g., 2025).
   *
   * @return array
   *   Resultado del calculo:
   *   - taxable_base: (float) Base imponible tras deducciones.
   *   - gross_tax: (float) Cuota integra bruta.
   *   - net_tax: (float) Cuota liquida neta.
   *   - effective_rate: (float) Tipo efectivo en porcentaje.
   *   - brackets: (array) Desglose por tramo con claves:
   *     from, to, rate, taxable_amount, tax.
   */
  public function calculateIrpf(float $grossIncome, float $deductions = 0.0, string $regime = 'general', int $year = 2025): array {
    // Base imponible cannot be negative.
    $taxableBase = max(0.0, $grossIncome - $deductions);

    if ($taxableBase <= 0.0) {
      return [
        'taxable_base' => 0.0,
        'gross_tax' => 0.0,
        'net_tax' => 0.0,
        'effective_rate' => 0.0,
        'brackets' => [],
      ];
    }

    $brackets = $this->getIrpfBrackets($year);
    $grossTax = 0.0;
    $bracketDetails = [];
    $remaining = $taxableBase;

    foreach ($brackets as $bracket) {
      if ($remaining <= 0.0) {
        break;
      }

      $bracketWidth = $bracket['to'] - $bracket['from'];
      $taxableInBracket = min($remaining, $bracketWidth);
      $taxInBracket = $taxableInBracket * ($bracket['rate'] / 100.0);
      $grossTax += $taxInBracket;

      $bracketDetails[] = [
        'from' => $bracket['from'],
        'to' => $bracket['from'] + $taxableInBracket,
        'rate' => $bracket['rate'],
        'taxable_amount' => round($taxableInBracket, 2),
        'tax' => round($taxInBracket, 2),
      ];

      $remaining -= $taxableInBracket;
    }

    $grossTax = round($grossTax, 2);
    $netTax = $grossTax;
    $effectiveRate = $taxableBase > 0.0 ? round(($grossTax / $taxableBase) * 100, 2) : 0.0;

    $result = [
      'taxable_base' => round($taxableBase, 2),
      'gross_tax' => $grossTax,
      'net_tax' => $netTax,
      'effective_rate' => $effectiveRate,
      'brackets' => $bracketDetails,
    ];

    $this->logger->info('IRPF calculado: base @base, cuota @cuota, tipo efectivo @tipo%.', [
      '@base' => $result['taxable_base'],
      '@cuota' => $result['gross_tax'],
      '@tipo' => $result['effective_rate'],
    ]);

    return $result;
  }

  /**
   * Calcula el IVA para un importe base, tipo y recargo de equivalencia.
   *
   * @param float $baseAmount
   *   Importe base imponible en euros.
   * @param string $type
   *   Tipo de IVA: 'general', 'reducido', 'superreducido', 'exento'.
   * @param bool $withRecargo
   *   TRUE para aplicar recargo de equivalencia.
   *
   * @return array
   *   Resultado del calculo:
   *   - base_amount: (float) Base imponible.
   *   - rate: (int) Porcentaje de IVA aplicado.
   *   - iva_amount: (float) Cuota de IVA.
   *   - recargo_amount: (float) Cuota de recargo de equivalencia.
   *   - total: (float) Total con IVA y recargo incluido.
   */
  public function calculateIva(float $baseAmount, string $type = 'general', bool $withRecargo = FALSE): array {
    // Negative amounts are treated as zero (refunds handled separately).
    if ($baseAmount < 0.0) {
      return [
        'base_amount' => 0.0,
        'rate' => 0,
        'iva_amount' => 0.0,
        'recargo_amount' => 0.0,
        'total' => 0.0,
      ];
    }

    $ivaRates = $this->getIvaRates();
    $rate = $ivaRates[$type] ?? $ivaRates['general'];

    $ivaAmount = $baseAmount * ($rate / 100.0);

    $recargoAmount = 0.0;
    if ($withRecargo && $rate > 0) {
      $recargoRates = $this->getRecargoRates();
      $recargoRate = $recargoRates[$type] ?? 0.0;
      $recargoAmount = $baseAmount * ($recargoRate / 100.0);
    }

    $total = $baseAmount + $ivaAmount + $recargoAmount;

    $this->logger->info('IVA calculado: base @base, tipo @tipo%, cuota @cuota.', [
      '@base' => round($baseAmount, 2),
      '@tipo' => $rate,
      '@cuota' => round($ivaAmount, 2),
    ]);

    return [
      'base_amount' => $baseAmount,
      'rate' => $rate,
      'iva_amount' => $ivaAmount,
      'recargo_amount' => $recargoAmount,
      'total' => $total,
    ];
  }

  /**
   * Obtiene los tramos del IRPF vigentes para un ejercicio fiscal.
   *
   * Escala estatal general. Las comunidades autonomas tienen sus propios
   * tramos complementarios que se aplican de forma adicional.
   *
   * @param int $year
   *   Ejercicio fiscal.
   *
   * @return array
   *   Array de tramos. Cada tramo tiene claves: from, to, rate.
   *   El rate es un porcentaje (e.g., 19 para 19%).
   */
  public function getIrpfBrackets(int $year = 2025): array {
    // Escala estatal vigente 2025/2026.
    // @todo Implementar escalas autonomicas para cada comunidad.
    return [
      ['from' => 0, 'to' => 12450, 'rate' => 19],
      ['from' => 12450, 'to' => 20200, 'rate' => 24],
      ['from' => 20200, 'to' => 35200, 'rate' => 30],
      ['from' => 35200, 'to' => 60000, 'rate' => 37],
      ['from' => 60000, 'to' => 300000, 'rate' => 45],
      ['from' => 300000, 'to' => PHP_INT_MAX, 'rate' => 47],
    ];
  }

  /**
   * Obtiene las tasas oficiales de IVA espanol.
   *
   * @return array
   *   Array asociativo tipo => porcentaje (int).
   */
  public function getIvaRates(): array {
    return [
      'general' => 21,
      'reducido' => 10,
      'superreducido' => 4,
      'exento' => 0,
    ];
  }

  /**
   * Obtiene las tasas de recargo de equivalencia.
   *
   * El recargo de equivalencia es un regimen especial de IVA
   * aplicable a comerciantes minoristas personas fisicas.
   *
   * @return array
   *   Array asociativo tipo => porcentaje (float).
   */
  public function getRecargoRates(): array {
    return [
      'general' => 5.2,
      'reducido' => 1.4,
      'superreducido' => 0.5,
      'exento' => 0.0,
    ];
  }

}
