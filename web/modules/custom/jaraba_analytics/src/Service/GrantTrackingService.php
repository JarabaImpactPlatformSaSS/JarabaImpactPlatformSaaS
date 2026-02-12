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
   *   Resumen con burn rate, partidas y alertas.
   */
  public function getGrantSummary(array $grantConfig): array {
    $burnRate = $this->calculateBurnRate(
      (int) ($grantConfig['total'] ?? 0),
      (int) ($grantConfig['spent'] ?? 0),
      $grantConfig['start_date'] ?? date('Y-01-01'),
      $grantConfig['end_date'] ?? date('Y-12-31'),
    );

    $budgetLines = $this->calculateBudgetLines($grantConfig['budget_lines'] ?? []);

    return [
      'burn_rate' => $burnRate,
      'budget_lines' => $budgetLines,
      'total' => $grantConfig['total'] ?? 0,
      'spent' => $grantConfig['spent'] ?? 0,
      'remaining' => ($grantConfig['total'] ?? 0) - ($grantConfig['spent'] ?? 0),
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
