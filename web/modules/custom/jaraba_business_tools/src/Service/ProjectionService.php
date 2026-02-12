<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for financial projections and scenario analysis.
 *
 * Generates revenue/cost projections, break-even analysis,
 * scenario modeling (best/worst/expected), and ROI calculations
 * for entrepreneurs on the Jaraba Impact Platform.
 */
class ProjectionService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Default projection period in months.
   */
  protected const DEFAULT_PROJECTION_MONTHS = 36;

  /**
   * Scenario multipliers for optimistic/pessimistic adjustments.
   */
  protected const SCENARIO_MULTIPLIERS = [
    'best' => [
      'revenue' => 1.30,
      'cost' => 0.90,
      'growth' => 1.25,
    ],
    'expected' => [
      'revenue' => 1.00,
      'cost' => 1.00,
      'growth' => 1.00,
    ],
    'worst' => [
      'revenue' => 0.60,
      'cost' => 1.20,
      'growth' => 0.70,
    ],
  ];

  /**
   * Constructs a new ProjectionService.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->logger = $loggerFactory->get('jaraba_business_tools');
  }

  /**
   * Generates financial projections for revenue and costs.
   *
   * @param array $params
   *   Projection parameters:
   *   - 'initial_revenue': float, monthly revenue at start (EUR)
   *   - 'monthly_growth_rate': float, expected monthly growth (0.0-1.0)
   *   - 'fixed_costs': float, monthly fixed costs (EUR)
   *   - 'variable_cost_ratio': float, variable cost as % of revenue (0.0-1.0)
   *   - 'initial_investment': float, one-time startup investment (EUR)
   *   - 'months': int, projection period (default 36)
   *   - 'seasonality': array|null, 12 monthly multipliers (e.g., [1.0, 0.8, ...])
   *   - 'price_per_unit': float|null, unit price for unit-based projections
   *   - 'units_month_1': int|null, initial monthly units sold
   *   - 'unit_growth_rate': float|null, monthly unit growth rate
   *
   * @return array
   *   Full projection with monthly breakdown and summary.
   */
  public function generateFinancialProjection(array $params): array {
    $months = (int) ($params['months'] ?? self::DEFAULT_PROJECTION_MONTHS);
    $months = max(1, min(120, $months));

    $initialRevenue = (float) ($params['initial_revenue'] ?? 0);
    $growthRate = (float) ($params['monthly_growth_rate'] ?? 0.05);
    $fixedCosts = (float) ($params['fixed_costs'] ?? 0);
    $variableCostRatio = (float) ($params['variable_cost_ratio'] ?? 0.3);
    $initialInvestment = (float) ($params['initial_investment'] ?? 0);
    $seasonality = $params['seasonality'] ?? NULL;

    // Unit-based calculation overrides.
    $pricePerUnit = isset($params['price_per_unit']) ? (float) $params['price_per_unit'] : NULL;
    $unitsMonth1 = isset($params['units_month_1']) ? (int) $params['units_month_1'] : NULL;
    $unitGrowthRate = isset($params['unit_growth_rate']) ? (float) $params['unit_growth_rate'] : NULL;

    $monthly = [];
    $cumulativeRevenue = 0.0;
    $cumulativeCosts = $initialInvestment;
    $cumulativeProfit = -$initialInvestment;
    $breakevenMonth = NULL;

    for ($m = 1; $m <= $months; $m++) {
      // Calculate revenue for this month.
      if ($pricePerUnit !== NULL && $unitsMonth1 !== NULL) {
        $unitGrowth = $unitGrowthRate ?? $growthRate;
        $units = $unitsMonth1 * pow(1 + $unitGrowth, $m - 1);
        $revenue = $units * $pricePerUnit;
      }
      else {
        $revenue = $initialRevenue * pow(1 + $growthRate, $m - 1);
      }

      // Apply seasonality if provided.
      if (is_array($seasonality) && count($seasonality) === 12) {
        $monthIndex = ($m - 1) % 12;
        $revenue *= $seasonality[$monthIndex];
      }

      // Calculate costs.
      $variableCosts = $revenue * $variableCostRatio;
      $totalCosts = $fixedCosts + $variableCosts;
      $profit = $revenue - $totalCosts;

      // Cumulative tracking.
      $cumulativeRevenue += $revenue;
      $cumulativeCosts += $totalCosts;
      $cumulativeProfit += $profit;

      // Detect breakeven month.
      if ($breakevenMonth === NULL && $cumulativeProfit >= 0) {
        $breakevenMonth = $m;
      }

      $monthly[] = [
        'month' => $m,
        'revenue' => round($revenue, 2),
        'fixed_costs' => round($fixedCosts, 2),
        'variable_costs' => round($variableCosts, 2),
        'total_costs' => round($totalCosts, 2),
        'profit' => round($profit, 2),
        'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
        'cumulative_revenue' => round($cumulativeRevenue, 2),
        'cumulative_costs' => round($cumulativeCosts, 2),
        'cumulative_profit' => round($cumulativeProfit, 2),
        'units' => isset($units) ? (int) round($units) : NULL,
      ];
    }

    // Calculate annual summaries.
    $annualSummaries = $this->buildAnnualSummaries($monthly);

    $result = [
      'projection' => [
        'months' => $months,
        'monthly' => $monthly,
        'annual' => $annualSummaries,
      ],
      'summary' => [
        'total_revenue' => round($cumulativeRevenue, 2),
        'total_costs' => round($cumulativeCosts, 2),
        'total_profit' => round($cumulativeProfit, 2),
        'average_monthly_revenue' => round($cumulativeRevenue / $months, 2),
        'average_monthly_profit' => round($cumulativeProfit / $months, 2),
        'final_monthly_revenue' => $monthly[$months - 1]['revenue'],
        'final_monthly_profit' => $monthly[$months - 1]['profit'],
        'overall_margin' => $cumulativeRevenue > 0
          ? round(($cumulativeProfit / $cumulativeRevenue) * 100, 1)
          : 0,
        'breakeven_month' => $breakevenMonth,
        'initial_investment' => round($initialInvestment, 2),
      ],
      'params' => $params,
      'generated_at' => date('Y-m-d\TH:i:s'),
      'generated_by' => (int) $this->currentUser->id(),
    ];

    $this->logger->info('Financial projection generated: @months months, breakeven month @be', [
      '@months' => $months,
      '@be' => $breakevenMonth ?? 'N/A',
    ]);

    return $result;
  }

  /**
   * Calculates break-even analysis from cost and revenue data.
   *
   * @param array $costs
   *   Cost structure:
   *   - 'fixed_monthly': float, monthly fixed costs
   *   - 'variable_per_unit': float, variable cost per unit
   *   - 'initial_investment': float, one-time startup costs
   *
   * @param array $revenue
   *   Revenue parameters:
   *   - 'price_per_unit': float, selling price per unit
   *   - 'units_per_month': int, expected monthly units
   *   - 'monthly_growth_rate': float, monthly unit growth rate (0.0-1.0)
   *
   * @return array
   *   Break-even analysis results.
   */
  public function calculateBreakeven(array $costs, array $revenue): array {
    $fixedMonthly = (float) ($costs['fixed_monthly'] ?? 0);
    $variablePerUnit = (float) ($costs['variable_per_unit'] ?? 0);
    $initialInvestment = (float) ($costs['initial_investment'] ?? 0);
    $pricePerUnit = (float) ($revenue['price_per_unit'] ?? 0);
    $unitsPerMonth = (int) ($revenue['units_per_month'] ?? 0);
    $growthRate = (float) ($revenue['monthly_growth_rate'] ?? 0);

    // Contribution margin per unit.
    $contributionMargin = $pricePerUnit - $variablePerUnit;

    // Break-even units per month (ignoring investment).
    $breakevenUnits = $contributionMargin > 0
      ? (int) ceil($fixedMonthly / $contributionMargin)
      : 0;

    // Break-even revenue per month.
    $breakevenRevenue = $pricePerUnit > 0
      ? $breakevenUnits * $pricePerUnit
      : 0;

    // Calculate months to recover initial investment.
    $monthsToBreakeven = NULL;
    if ($contributionMargin > 0 && $unitsPerMonth > 0) {
      $cumulativeProfit = -$initialInvestment;
      $currentUnits = (float) $unitsPerMonth;

      for ($m = 1; $m <= 120; $m++) {
        $monthlyRevenue = $currentUnits * $pricePerUnit;
        $monthlyVariableCosts = $currentUnits * $variablePerUnit;
        $monthlyProfit = $monthlyRevenue - $fixedMonthly - $monthlyVariableCosts;
        $cumulativeProfit += $monthlyProfit;

        if ($cumulativeProfit >= 0) {
          $monthsToBreakeven = $m;
          break;
        }

        $currentUnits *= (1 + $growthRate);
      }
    }

    // Safety margin: how far current sales are from breakeven.
    $safetyMargin = $unitsPerMonth > 0 && $breakevenUnits > 0
      ? round((($unitsPerMonth - $breakevenUnits) / $unitsPerMonth) * 100, 1)
      : 0;

    return [
      'breakeven_units' => $breakevenUnits,
      'breakeven_revenue' => round($breakevenRevenue, 2),
      'contribution_margin' => round($contributionMargin, 2),
      'contribution_margin_ratio' => $pricePerUnit > 0
        ? round(($contributionMargin / $pricePerUnit) * 100, 1)
        : 0,
      'months_to_breakeven' => $monthsToBreakeven,
      'safety_margin' => $safetyMargin,
      'is_above_breakeven' => $unitsPerMonth >= $breakevenUnits,
      'units_gap' => max(0, $breakevenUnits - $unitsPerMonth),
      'analysis' => $this->getBreakevenInterpretation(
        $breakevenUnits,
        $unitsPerMonth,
        $monthsToBreakeven,
        $safetyMargin
      ),
    ];
  }

  /**
   * Performs scenario analysis with best, expected, and worst cases.
   *
   * @param array $base
   *   Base projection parameters (same format as generateFinancialProjection).
   * @param array $scenarios
   *   Optional custom scenario overrides. Keys: 'best', 'expected', 'worst'.
   *   Each can override any base parameter.
   *
   * @return array
   *   Analysis with projections for each scenario and comparison.
   */
  public function scenarioAnalysis(array $base, array $scenarios = []): array {
    $results = [];

    foreach (['best', 'expected', 'worst'] as $scenarioKey) {
      $multipliers = self::SCENARIO_MULTIPLIERS[$scenarioKey];
      $customOverrides = $scenarios[$scenarioKey] ?? [];

      // Build scenario params from base + multipliers + custom overrides.
      $scenarioParams = $base;

      // Apply multipliers to base values.
      if (isset($scenarioParams['initial_revenue'])) {
        $scenarioParams['initial_revenue'] *= $multipliers['revenue'];
      }
      if (isset($scenarioParams['fixed_costs'])) {
        $scenarioParams['fixed_costs'] *= $multipliers['cost'];
      }
      if (isset($scenarioParams['monthly_growth_rate'])) {
        $scenarioParams['monthly_growth_rate'] *= $multipliers['growth'];
      }

      // Apply custom overrides on top.
      $scenarioParams = array_merge($scenarioParams, $customOverrides);

      // Generate projection for this scenario.
      $projection = $this->generateFinancialProjection($scenarioParams);

      $results[$scenarioKey] = [
        'label' => $this->getScenarioLabel($scenarioKey),
        'params' => $scenarioParams,
        'summary' => $projection['summary'],
        'monthly' => $projection['projection']['monthly'],
        'annual' => $projection['projection']['annual'] ?? [],
      ];
    }

    // Comparison table.
    $comparison = [
      'total_revenue' => [
        'best' => $results['best']['summary']['total_revenue'],
        'expected' => $results['expected']['summary']['total_revenue'],
        'worst' => $results['worst']['summary']['total_revenue'],
      ],
      'total_profit' => [
        'best' => $results['best']['summary']['total_profit'],
        'expected' => $results['expected']['summary']['total_profit'],
        'worst' => $results['worst']['summary']['total_profit'],
      ],
      'breakeven_month' => [
        'best' => $results['best']['summary']['breakeven_month'],
        'expected' => $results['expected']['summary']['breakeven_month'],
        'worst' => $results['worst']['summary']['breakeven_month'],
      ],
      'final_monthly_revenue' => [
        'best' => $results['best']['summary']['final_monthly_revenue'],
        'expected' => $results['expected']['summary']['final_monthly_revenue'],
        'worst' => $results['worst']['summary']['final_monthly_revenue'],
      ],
      'overall_margin' => [
        'best' => $results['best']['summary']['overall_margin'],
        'expected' => $results['expected']['summary']['overall_margin'],
        'worst' => $results['worst']['summary']['overall_margin'],
      ],
    ];

    // Risk assessment.
    $riskAssessment = $this->assessRisk($results);

    return [
      'scenarios' => $results,
      'comparison' => $comparison,
      'risk_assessment' => $riskAssessment,
      'generated_at' => date('Y-m-d\TH:i:s'),
    ];
  }

  /**
   * Calculates Return on Investment (ROI).
   *
   * @param float $investment
   *   Total investment amount (EUR).
   * @param array $returns
   *   Expected returns:
   *   - 'monthly_returns': float[], array of monthly net returns
   *   - 'total_return': float, alternatively a single total return value
   *   - 'period_months': int, investment period
   *   - 'discount_rate': float, annual discount rate for NPV (default 0.10)
   *
   * @return array
   *   ROI metrics including simple ROI, annualized, NPV, IRR approximation.
   */
  public function calculateROI(float $investment, array $returns): array {
    if ($investment <= 0) {
      return [
        'error' => 'La inversion debe ser mayor que 0.',
        'roi' => 0,
      ];
    }

    $monthlyReturns = $returns['monthly_returns'] ?? [];
    $periodMonths = (int) ($returns['period_months'] ?? count($monthlyReturns));
    $annualDiscountRate = (float) ($returns['discount_rate'] ?? 0.10);
    $monthlyDiscountRate = pow(1 + $annualDiscountRate, 1 / 12) - 1;

    // If monthly returns not provided, calculate from total.
    if (empty($monthlyReturns) && isset($returns['total_return'])) {
      $totalReturn = (float) $returns['total_return'];
    }
    else {
      $totalReturn = array_sum($monthlyReturns);
    }

    // Simple ROI.
    $simpleRoi = (($totalReturn - $investment) / $investment) * 100;

    // Annualized ROI.
    $periodYears = max(1, $periodMonths) / 12;
    $annualizedRoi = $periodYears > 0
      ? (pow(($totalReturn / $investment), 1 / $periodYears) - 1) * 100
      : 0;

    // Net Present Value (NPV).
    $npv = -$investment;
    if (!empty($monthlyReturns)) {
      foreach ($monthlyReturns as $month => $cashflow) {
        $npv += $cashflow / pow(1 + $monthlyDiscountRate, $month + 1);
      }
    }
    else {
      // Distribute total return evenly for NPV approximation.
      $monthlyReturn = $periodMonths > 0 ? $totalReturn / $periodMonths : 0;
      for ($m = 1; $m <= $periodMonths; $m++) {
        $npv += $monthlyReturn / pow(1 + $monthlyDiscountRate, $m);
      }
    }

    // Payback period.
    $paybackMonth = NULL;
    $cumulative = 0.0;
    if (!empty($monthlyReturns)) {
      foreach ($monthlyReturns as $month => $cashflow) {
        $cumulative += $cashflow;
        if ($cumulative >= $investment && $paybackMonth === NULL) {
          $paybackMonth = $month + 1;
        }
      }
    }
    elseif ($periodMonths > 0 && $totalReturn > 0) {
      $monthlyReturn = $totalReturn / $periodMonths;
      $paybackMonth = $monthlyReturn > 0 ? (int) ceil($investment / $monthlyReturn) : NULL;
    }

    return [
      'investment' => round($investment, 2),
      'total_return' => round($totalReturn, 2),
      'net_return' => round($totalReturn - $investment, 2),
      'simple_roi' => round($simpleRoi, 2),
      'annualized_roi' => round($annualizedRoi, 2),
      'npv' => round($npv, 2),
      'npv_positive' => $npv > 0,
      'payback_months' => $paybackMonth,
      'period_months' => $periodMonths,
      'discount_rate' => $annualDiscountRate,
      'interpretation' => $this->getRoiInterpretation($simpleRoi, $npv, $paybackMonth),
    ];
  }

  /**
   * Builds annual summaries from monthly data.
   */
  protected function buildAnnualSummaries(array $monthly): array {
    $annuals = [];
    $currentYear = 0;
    $yearData = [
      'revenue' => 0,
      'costs' => 0,
      'profit' => 0,
    ];

    foreach ($monthly as $index => $month) {
      $year = (int) floor($index / 12) + 1;

      if ($year !== $currentYear) {
        if ($currentYear > 0) {
          $annuals[] = [
            'year' => $currentYear,
            'revenue' => round($yearData['revenue'], 2),
            'costs' => round($yearData['costs'], 2),
            'profit' => round($yearData['profit'], 2),
            'margin' => $yearData['revenue'] > 0
              ? round(($yearData['profit'] / $yearData['revenue']) * 100, 1)
              : 0,
          ];
        }
        $currentYear = $year;
        $yearData = ['revenue' => 0, 'costs' => 0, 'profit' => 0];
      }

      $yearData['revenue'] += $month['revenue'];
      $yearData['costs'] += $month['total_costs'];
      $yearData['profit'] += $month['profit'];
    }

    // Add last year.
    if ($currentYear > 0) {
      $annuals[] = [
        'year' => $currentYear,
        'revenue' => round($yearData['revenue'], 2),
        'costs' => round($yearData['costs'], 2),
        'profit' => round($yearData['profit'], 2),
        'margin' => $yearData['revenue'] > 0
          ? round(($yearData['profit'] / $yearData['revenue']) * 100, 1)
          : 0,
      ];
    }

    return $annuals;
  }

  /**
   * Gets human-readable scenario label.
   */
  protected function getScenarioLabel(string $key): string {
    return match ($key) {
      'best' => 'Escenario Optimista',
      'expected' => 'Escenario Esperado',
      'worst' => 'Escenario Pesimista',
      default => ucfirst($key),
    };
  }

  /**
   * Assesses risk from scenario comparison.
   */
  protected function assessRisk(array $scenarios): array {
    $worstProfit = $scenarios['worst']['summary']['total_profit'] ?? 0;
    $expectedProfit = $scenarios['expected']['summary']['total_profit'] ?? 0;
    $bestProfit = $scenarios['best']['summary']['total_profit'] ?? 0;

    // Volatility: spread between best and worst relative to expected.
    $spread = $bestProfit - $worstProfit;
    $volatility = $expectedProfit != 0
      ? abs($spread / $expectedProfit)
      : 0;

    // Downside risk: probability-weighted loss.
    $downsideRisk = $worstProfit < 0 ? abs($worstProfit) : 0;

    // Risk level.
    if ($worstProfit < 0 && $expectedProfit < 0) {
      $riskLevel = 'very_high';
    }
    elseif ($worstProfit < 0) {
      $riskLevel = $volatility > 2 ? 'high' : 'moderate';
    }
    elseif ($volatility > 1.5) {
      $riskLevel = 'moderate';
    }
    else {
      $riskLevel = 'low';
    }

    return [
      'risk_level' => $riskLevel,
      'volatility' => round($volatility, 2),
      'downside_risk' => round($downsideRisk, 2),
      'worst_case_profitable' => $worstProfit > 0,
      'recommendation' => $this->getRiskRecommendation($riskLevel, $worstProfit),
    ];
  }

  /**
   * Gets risk recommendation text.
   */
  protected function getRiskRecommendation(string $riskLevel, float $worstProfit): string {
    return match ($riskLevel) {
      'very_high' => 'Riesgo muy alto: incluso en el escenario esperado hay perdidas. Reconsidera el modelo de negocio antes de invertir.',
      'high' => 'Riesgo alto: el escenario pesimista genera perdidas de ' . number_format(abs($worstProfit), 0, ',', '.') . ' EUR. Busca formas de reducir costes fijos o mejorar el margen.',
      'moderate' => 'Riesgo moderado: existe una diferencia significativa entre escenarios. Prepara planes de contingencia para el escenario pesimista.',
      'low' => 'Riesgo bajo: el negocio es rentable incluso en el peor escenario. Buenas condiciones para avanzar.',
      default => 'Analiza los escenarios con detenimiento antes de tomar decisiones.',
    };
  }

  /**
   * Gets breakeven interpretation text.
   */
  protected function getBreakevenInterpretation(int $breakevenUnits, int $currentUnits, ?int $monthsToBreakeven, float $safetyMargin): string {
    $parts = [];

    if ($currentUnits >= $breakevenUnits) {
      $parts[] = 'El negocio esta por encima del punto de equilibrio con un margen de seguridad del ' . $safetyMargin . '%.';
    }
    else {
      $gap = $breakevenUnits - $currentUnits;
      $parts[] = 'Necesitas vender ' . $gap . ' unidades adicionales al mes para alcanzar el punto de equilibrio.';
    }

    if ($monthsToBreakeven !== NULL) {
      if ($monthsToBreakeven <= 6) {
        $parts[] = 'Se recupera la inversion en ' . $monthsToBreakeven . ' meses, lo cual es muy rapido.';
      }
      elseif ($monthsToBreakeven <= 18) {
        $parts[] = 'La inversion se recupera en ' . $monthsToBreakeven . ' meses, un plazo razonable.';
      }
      else {
        $parts[] = 'La recuperacion tarda ' . $monthsToBreakeven . ' meses. Considera estrategias para acelerar.';
      }
    }
    else {
      $parts[] = 'Con los parametros actuales, no se alcanza el punto de equilibrio en el periodo analizado.';
    }

    return implode(' ', $parts);
  }

  /**
   * Gets ROI interpretation text.
   */
  protected function getRoiInterpretation(float $roi, float $npv, ?int $paybackMonths): string {
    $parts = [];

    if ($roi > 100) {
      $parts[] = 'Excelente retorno: mas del doble de la inversion.';
    }
    elseif ($roi > 50) {
      $parts[] = 'Buen retorno sobre la inversion.';
    }
    elseif ($roi > 0) {
      $parts[] = 'Retorno positivo pero moderado.';
    }
    else {
      $parts[] = 'Retorno negativo: la inversion genera perdidas.';
    }

    if ($npv > 0) {
      $parts[] = 'El VAN es positivo (' . number_format($npv, 0, ',', '.') . ' EUR), lo que indica que el proyecto crea valor.';
    }
    else {
      $parts[] = 'El VAN es negativo, lo que sugiere que el proyecto destruye valor a la tasa de descuento aplicada.';
    }

    if ($paybackMonths !== NULL) {
      $parts[] = 'Periodo de recuperacion: ' . $paybackMonths . ' meses.';
    }

    return implode(' ', $parts);
  }

}
