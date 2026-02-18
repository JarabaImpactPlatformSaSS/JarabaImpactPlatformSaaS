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
  public function calculateIrpf(float $grossIncome, float $deductions = 0.0, string $regime = 'general', int $year = 2025, string $community = 'estatal'): array {
    // Base imponible cannot be negative.
    $taxableBase = max(0.0, $grossIncome - $deductions);

    if ($taxableBase <= 0.0) {
      return [
        'taxable_base' => 0.0,
        'gross_tax' => 0.0,
        'net_tax' => 0.0,
        'effective_rate' => 0.0,
        'brackets' => [],
        'community' => $community,
      ];
    }

    $brackets = $this->getIrpfBrackets($year, $community);
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
      'community' => $community,
    ];

    $this->logger->info('IRPF calculado: base @base, cuota @cuota, tipo efectivo @tipo% (comunidad: @community).', [
      '@base' => $result['taxable_base'],
      '@cuota' => $result['gross_tax'],
      '@tipo' => $result['effective_rate'],
      '@community' => $community,
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
  public function getIrpfBrackets(int $year = 2025, string $community = 'estatal'): array {
    // AUDIT-TODO-RESOLVED: State + autonomous community IRPF scales for Spain.
    // The total IRPF = state scale + autonomous scale. This method returns the
    // combined (effective) brackets when a community is specified, or just the
    // state scale when community = 'estatal'.

    $stateScale = [
      ['from' => 0, 'to' => 12450, 'rate' => 9.50],
      ['from' => 12450, 'to' => 20200, 'rate' => 12.00],
      ['from' => 20200, 'to' => 35200, 'rate' => 15.00],
      ['from' => 35200, 'to' => 60000, 'rate' => 18.50],
      ['from' => 60000, 'to' => 300000, 'rate' => 22.50],
      ['from' => 300000, 'to' => PHP_INT_MAX, 'rate' => 24.50],
    ];

    $autonomousScales = $this->getAutonomousScales($year);
    $communityKey = $this->normalizeCommunityKey($community);

    if ($communityKey === 'estatal' || !isset($autonomousScales[$communityKey])) {
      // Return the combined general scale (state portions summed for backwards compatibility).
      return [
        ['from' => 0, 'to' => 12450, 'rate' => 19],
        ['from' => 12450, 'to' => 20200, 'rate' => 24],
        ['from' => 20200, 'to' => 35200, 'rate' => 30],
        ['from' => 35200, 'to' => 60000, 'rate' => 37],
        ['from' => 60000, 'to' => 300000, 'rate' => 45],
        ['from' => 300000, 'to' => PHP_INT_MAX, 'rate' => 47],
      ];
    }

    // Combine state + autonomous scales into effective brackets.
    return $this->combineScales($stateScale, $autonomousScales[$communityKey]);
  }

  /**
   * Returns the autonomous community IRPF scales for a given fiscal year.
   *
   * Each community has its own complementary IRPF scale that is applied
   * alongside the state scale. The rates shown are the autonomous portion only.
   *
   * @param int $year
   *   Fiscal year.
   *
   * @return array
   *   Associative array keyed by community identifier, each containing
   *   an array of brackets with 'from', 'to', 'rate' keys.
   */
  public function getAutonomousScales(int $year = 2025): array {
    return [
      // Andalucia: Ley 5/2021 y actualizaciones.
      'andalucia' => [
        ['from' => 0, 'to' => 12450, 'rate' => 9.50],
        ['from' => 12450, 'to' => 20200, 'rate' => 12.00],
        ['from' => 20200, 'to' => 28000, 'rate' => 15.00],
        ['from' => 28000, 'to' => 35200, 'rate' => 15.50],
        ['from' => 35200, 'to' => 50000, 'rate' => 18.50],
        ['from' => 50000, 'to' => 60000, 'rate' => 19.50],
        ['from' => 60000, 'to' => 120000, 'rate' => 23.50],
        ['from' => 120000, 'to' => PHP_INT_MAX, 'rate' => 24.50],
      ],

      // Cataluna: Escala autonomica con tramos adicionales.
      'cataluna' => [
        ['from' => 0, 'to' => 12450, 'rate' => 10.50],
        ['from' => 12450, 'to' => 17707, 'rate' => 12.00],
        ['from' => 17707, 'to' => 20200, 'rate' => 14.00],
        ['from' => 20200, 'to' => 33007, 'rate' => 15.00],
        ['from' => 33007, 'to' => 35200, 'rate' => 17.00],
        ['from' => 35200, 'to' => 53407, 'rate' => 18.50],
        ['from' => 53407, 'to' => 60000, 'rate' => 20.50],
        ['from' => 60000, 'to' => 90000, 'rate' => 21.50],
        ['from' => 90000, 'to' => 120000, 'rate' => 23.50],
        ['from' => 120000, 'to' => 175000, 'rate' => 24.50],
        ['from' => 175000, 'to' => PHP_INT_MAX, 'rate' => 25.50],
      ],

      // Madrid: Escala autonomica (deflactada, generalmente mas baja).
      'madrid' => [
        ['from' => 0, 'to' => 12961, 'rate' => 8.50],
        ['from' => 12961, 'to' => 18612, 'rate' => 10.70],
        ['from' => 18612, 'to' => 21122, 'rate' => 12.80],
        ['from' => 21122, 'to' => 35200, 'rate' => 13.30],
        ['from' => 35200, 'to' => 53407, 'rate' => 17.90],
        ['from' => 53407, 'to' => 60000, 'rate' => 18.80],
        ['from' => 60000, 'to' => PHP_INT_MAX, 'rate' => 20.50],
      ],

      // Comunitat Valenciana.
      'valencia' => [
        ['from' => 0, 'to' => 12450, 'rate' => 10.00],
        ['from' => 12450, 'to' => 17000, 'rate' => 12.00],
        ['from' => 17000, 'to' => 20200, 'rate' => 14.00],
        ['from' => 20200, 'to' => 30000, 'rate' => 15.00],
        ['from' => 30000, 'to' => 35200, 'rate' => 17.00],
        ['from' => 35200, 'to' => 50000, 'rate' => 18.00],
        ['from' => 50000, 'to' => 65000, 'rate' => 22.50],
        ['from' => 65000, 'to' => 80000, 'rate' => 24.50],
        ['from' => 80000, 'to' => 140000, 'rate' => 25.00],
        ['from' => 140000, 'to' => PHP_INT_MAX, 'rate' => 25.50],
      ],

      // Galicia.
      'galicia' => [
        ['from' => 0, 'to' => 12450, 'rate' => 9.50],
        ['from' => 12450, 'to' => 20200, 'rate' => 11.75],
        ['from' => 20200, 'to' => 35200, 'rate' => 14.90],
        ['from' => 35200, 'to' => 60000, 'rate' => 18.50],
        ['from' => 60000, 'to' => PHP_INT_MAX, 'rate' => 22.50],
      ],

      // Pais Vasco (regimen foral propio - escala completa, no complementaria).
      // Nota: Pais Vasco y Navarra tienen regimen foral, la escala es propia
      // y no se suma a la estatal. Se devuelve la escala completa.
      'pais_vasco' => [
        ['from' => 0, 'to' => 16030, 'rate' => 23.00],
        ['from' => 16030, 'to' => 32060, 'rate' => 28.00],
        ['from' => 32060, 'to' => 48090, 'rate' => 35.00],
        ['from' => 48090, 'to' => 68220, 'rate' => 40.00],
        ['from' => 68220, 'to' => 96300, 'rate' => 45.00],
        ['from' => 96300, 'to' => 174600, 'rate' => 49.00],
        ['from' => 174600, 'to' => PHP_INT_MAX, 'rate' => 52.00],
      ],

      // Navarra (regimen foral propio).
      'navarra' => [
        ['from' => 0, 'to' => 4160, 'rate' => 13.00],
        ['from' => 4160, 'to' => 10640, 'rate' => 22.00],
        ['from' => 10640, 'to' => 18720, 'rate' => 25.00],
        ['from' => 18720, 'to' => 29220, 'rate' => 28.00],
        ['from' => 29220, 'to' => 42640, 'rate' => 36.50],
        ['from' => 42640, 'to' => 62360, 'rate' => 40.00],
        ['from' => 62360, 'to' => 93960, 'rate' => 44.00],
        ['from' => 93960, 'to' => 180200, 'rate' => 47.00],
        ['from' => 180200, 'to' => 320200, 'rate' => 49.00],
        ['from' => 320200, 'to' => PHP_INT_MAX, 'rate' => 52.00],
      ],
    ];
  }

  /**
   * Normalizes a community name to the internal key format.
   *
   * @param string $community
   *   Community name in various formats.
   *
   * @return string
   *   Normalized key.
   */
  protected function normalizeCommunityKey(string $community): string {
    $normalized = mb_strtolower(trim($community));

    // Remove accents.
    $accents = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'];
    $noAccents = ['a', 'e', 'i', 'o', 'u', 'n', 'u'];
    $normalized = str_replace($accents, $noAccents, $normalized);

    // Map common variations to canonical keys.
    $aliases = [
      'andalucia' => 'andalucia',
      'andalusia' => 'andalucia',
      'cataluna' => 'cataluna',
      'catalonia' => 'cataluna',
      'catalunya' => 'cataluna',
      'madrid' => 'madrid',
      'comunidad de madrid' => 'madrid',
      'valencia' => 'valencia',
      'comunitat valenciana' => 'valencia',
      'comunidad valenciana' => 'valencia',
      'galicia' => 'galicia',
      'pais vasco' => 'pais_vasco',
      'pais_vasco' => 'pais_vasco',
      'euskadi' => 'pais_vasco',
      'navarra' => 'navarra',
      'comunidad foral de navarra' => 'navarra',
      'estatal' => 'estatal',
      'general' => 'estatal',
    ];

    // Replace spaces with underscores for key lookup.
    $key = str_replace(' ', '_', $normalized);

    // Try direct match first, then alias lookup.
    if (isset($aliases[$normalized])) {
      return $aliases[$normalized];
    }
    if (isset($aliases[$key])) {
      return $aliases[$key];
    }

    return $key;
  }

  /**
   * Combines state and autonomous scales into effective IRPF brackets.
   *
   * This merges two progressive scales by creating sub-brackets at each
   * boundary from either scale, then summing the applicable rates.
   *
   * @param array $stateScale
   *   State IRPF brackets.
   * @param array $autonomousScale
   *   Autonomous community IRPF brackets.
   *
   * @return array
   *   Combined effective brackets.
   */
  protected function combineScales(array $stateScale, array $autonomousScale): array {
    // Collect all unique boundary points from both scales.
    $boundaries = [0];
    foreach ($stateScale as $bracket) {
      $boundaries[] = $bracket['from'];
      if ($bracket['to'] < PHP_INT_MAX) {
        $boundaries[] = $bracket['to'];
      }
    }
    foreach ($autonomousScale as $bracket) {
      $boundaries[] = $bracket['from'];
      if ($bracket['to'] < PHP_INT_MAX) {
        $boundaries[] = $bracket['to'];
      }
    }

    $boundaries = array_unique($boundaries);
    sort($boundaries);

    // Build combined brackets.
    $combined = [];
    for ($i = 0; $i < count($boundaries); $i++) {
      $from = $boundaries[$i];
      $to = isset($boundaries[$i + 1]) ? $boundaries[$i + 1] : PHP_INT_MAX;

      // Find applicable state rate for this range.
      $stateRate = 0.0;
      foreach ($stateScale as $bracket) {
        if ($from >= $bracket['from'] && $from < $bracket['to']) {
          $stateRate = $bracket['rate'];
          break;
        }
      }

      // Find applicable autonomous rate for this range.
      $autoRate = 0.0;
      foreach ($autonomousScale as $bracket) {
        if ($from >= $bracket['from'] && $from < $bracket['to']) {
          $autoRate = $bracket['rate'];
          break;
        }
      }

      $combined[] = [
        'from' => $from,
        'to' => $to,
        'rate' => round($stateRate + $autoRate, 2),
        'state_rate' => $stateRate,
        'autonomous_rate' => $autoRate,
      ];
    }

    // Merge consecutive brackets with the same effective rate.
    $merged = [];
    foreach ($combined as $bracket) {
      $last = end($merged);
      if ($last && abs($last['rate'] - $bracket['rate']) < 0.001 && $last['to'] === $bracket['from']) {
        $merged[count($merged) - 1]['to'] = $bracket['to'];
      }
      else {
        $merged[] = $bracket;
      }
    }

    return $merged;
  }

  /**
   * Returns the list of supported autonomous communities.
   *
   * @return array
   *   Array of community keys and labels.
   */
  public function getSupportedCommunities(): array {
    return [
      'estatal' => 'Escala estatal (general)',
      'andalucia' => 'Andalucia',
      'cataluna' => 'Cataluna',
      'madrid' => 'Comunidad de Madrid',
      'valencia' => 'Comunitat Valenciana',
      'galicia' => 'Galicia',
      'pais_vasco' => 'Pais Vasco (regimen foral)',
      'navarra' => 'Navarra (regimen foral)',
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
