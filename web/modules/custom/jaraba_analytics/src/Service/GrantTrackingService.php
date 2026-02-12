<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Psr\Log\LoggerInterface;

/**
 * Grant Burn Rate tracking service.
 *
 * Calcula la tasa de consumo de fondos publicos (grants) respecto a su
 * linea temporal. Detecta desviaciones y genera alertas cuando el consumo
 * supera el umbral configurado (por defecto 15%).
 *
 * Usado por el avatar Elena (administradora institucional) en el
 * dashboard de programa (/programa/dashboard).
 *
 * F7 — Doc 182.
 */
class GrantTrackingService {

  /**
   * Umbral de desviacion para activar alerta (porcentaje).
   */
  protected const DEVIATION_ALERT_THRESHOLD = 15.0;

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula la tasa de consumo del grant respecto a la linea temporal.
   *
   * @param int $grantTotal
   *   Total del grant en euros.
   * @param int $spentAmount
   *   Cantidad gastada hasta la fecha.
   * @param string $startDate
   *   Fecha de inicio del programa (ISO 8601).
   * @param string $endDate
   *   Fecha de fin del programa (ISO 8601).
   *
   * @return array{burn_rate: float, expected_rate: float, deviation: float, alert: bool, forecast_end: string, runway_days: int}
   */
  public function calculateBurnRate(int $grantTotal, int $spentAmount, string $startDate, string $endDate): array {
    if ($grantTotal <= 0) {
      return [
        'burn_rate' => 0.0,
        'expected_rate' => 0.0,
        'deviation' => 0.0,
        'alert' => FALSE,
        'forecast_end' => $endDate,
        'runway_days' => 0,
      ];
    }

    $start = new \DateTime($startDate);
    $end = new \DateTime($endDate);
    $now = new \DateTime();

    $totalDays = max(1, $end->diff($start)->days);
    $elapsedDays = max(0, $now->diff($start)->days);

    // Tasas.
    $burnRate = ($spentAmount / $grantTotal) * 100;
    $expectedRate = ($elapsedDays / $totalDays) * 100;
    $deviation = $burnRate - $expectedRate;

    // Forecast: si se mantiene el ritmo actual, cuando se acaba el dinero.
    $remainingAmount = $grantTotal - $spentAmount;
    $dailyBurn = $elapsedDays > 0 ? $spentAmount / $elapsedDays : 0;
    $runwayDays = $dailyBurn > 0 ? (int) ceil($remainingAmount / $dailyBurn) : 9999;

    $forecastEnd = (clone $now)->modify("+{$runwayDays} days")->format('Y-m-d');

    $alert = abs($deviation) > self::DEVIATION_ALERT_THRESHOLD;

    if ($alert) {
      $this->logger->warning('Grant burn rate alert: deviation @dev% (burn=@burn%, expected=@exp%)', [
        '@dev' => round($deviation, 1),
        '@burn' => round($burnRate, 1),
        '@exp' => round($expectedRate, 1),
      ]);
    }

    return [
      'burn_rate' => round($burnRate, 1),
      'expected_rate' => round($expectedRate, 1),
      'deviation' => round($deviation, 1),
      'alert' => $alert,
      'forecast_end' => $forecastEnd,
      'runway_days' => $runwayDays,
    ];
  }

  /**
   * Genera un resumen de estado del grant para el dashboard.
   *
   * @param array $grantConfig
   *   Configuracion del grant con claves:
   *   - total: (int) Total del grant en euros.
   *   - spent: (int) Cantidad gastada.
   *   - start_date: (string) Fecha inicio ISO 8601.
   *   - end_date: (string) Fecha fin ISO 8601.
   *   - budget_lines: (array) Partidas presupuestarias.
   *
   * @return array
   *   Resumen con burn rate, partidas, timeline y alertas.
   */
  public function getGrantSummary(array $grantConfig): array {
    $total = (int) ($grantConfig['total'] ?? 0);
    $spent = (int) ($grantConfig['spent'] ?? 0);
    $startDate = $grantConfig['start_date'] ?? date('Y-01-01');
    $endDate = $grantConfig['end_date'] ?? date('Y-12-31');

    $burnRate = $this->calculateBurnRate($total, $spent, $startDate, $endDate);
    $budgetLines = $this->calculateBudgetLines($grantConfig['budget_lines'] ?? []);
    $timeline = $this->buildTimeline($total, $spent, $startDate, $endDate);

    return [
      'burn_rate' => $burnRate,
      'budget_lines' => $budgetLines,
      'timeline' => $timeline,
      'total' => $total,
      'spent' => $spent,
      'remaining' => $total - $spent,
    ];
  }

  /**
   * Genera datos de timeline mensual para grafico de lineas.
   *
   * Calcula puntos de datos mensuales: consumo esperado (lineal) vs
   * consumo real acumulado. Util para Chart.js line chart.
   *
   * @param int $total
   *   Total del grant en euros.
   * @param int $spent
   *   Cantidad gastada hasta la fecha.
   * @param string $startDate
   *   Fecha inicio ISO 8601.
   * @param string $endDate
   *   Fecha fin ISO 8601.
   *
   * @return array{labels: string[], expected: int[], actual: int[]}
   */
  protected function buildTimeline(int $total, int $spent, string $startDate, string $endDate): array {
    $start = new \DateTime($startDate);
    $end = new \DateTime($endDate);
    $now = new \DateTime();

    $totalMonths = max(1, (int) $start->diff($end)->format('%m') + ((int) $start->diff($end)->format('%y') * 12));
    $elapsedMonths = max(0, (int) $start->diff($now)->format('%m') + ((int) $start->diff($now)->format('%y') * 12));

    $labels = [];
    $expected = [];
    $actual = [];

    $monthNames = [
      1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
      5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
      9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    $cursor = clone $start;
    $monthlyExpected = $totalMonths > 0 ? $total / $totalMonths : 0;
    $monthlyActual = $elapsedMonths > 0 ? $spent / $elapsedMonths : 0;

    for ($i = 0; $i < $totalMonths; $i++) {
      $monthNum = (int) $cursor->format('n');
      $labels[] = $monthNames[$monthNum] ?? $cursor->format('M');
      $expected[] = (int) round($monthlyExpected * ($i + 1));

      // Actual data only up to current month.
      if ($i < $elapsedMonths) {
        $actual[] = (int) round($monthlyActual * ($i + 1));
      }

      $cursor->modify('+1 month');
    }

    // Ensure the last actual data point matches exactly.
    if (!empty($actual)) {
      $actual[count($actual) - 1] = $spent;
    }

    return [
      'labels' => $labels,
      'expected' => $expected,
      'actual' => $actual,
    ];
  }

  /**
   * Calcula el estado de cada partida presupuestaria.
   *
   * @param array $lines
   *   Array de partidas, cada una con:
   *   - name: (string) Nombre de la partida.
   *   - budget: (int) Presupuesto asignado.
   *   - spent: (int) Cantidad gastada.
   *
   * @return array
   *   Partidas con porcentaje de consumo y alerta.
   */
  protected function calculateBudgetLines(array $lines): array {
    $result = [];

    foreach ($lines as $line) {
      $budget = (int) ($line['budget'] ?? 0);
      $spent = (int) ($line['spent'] ?? 0);
      $percentage = $budget > 0 ? round(($spent / $budget) * 100, 1) : 0.0;

      $result[] = [
        'name' => $line['name'] ?? '',
        'budget' => $budget,
        'spent' => $spent,
        'remaining' => $budget - $spent,
        'percentage' => $percentage,
        'alert' => $percentage > 90.0,
      ];
    }

    return $result;
  }

}
