<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de proyecciones financieras.
 *
 * PROPÓSITO:
 * Genera proyecciones financieras utilizando modelos matemáticos.
 * En el futuro puede integrarse con APIs de IA cuando estén disponibles.
 *
 * ESCENARIOS SOPORTADOS:
 * - base: Proyección conservadora basada en tendencia actual
 * - optimistic: +20% sobre base, asumiendo crecimiento acelerado
 * - pessimistic: -15% sobre base, asumiendo desaceleración
 * - custom: Parámetros personalizados por usuario
 *
 * MÉTRICAS PROYECTADAS:
 * - MRR/ARR
 * - Churn Rate
 * - Net Revenue Retention
 * - Customer Lifetime Value
 */
class ForecastingService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\jaraba_foc\Service\MetricsCalculatorService $metricsCalculator
     *   El servicio de cálculo de métricas.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   El factory de configuración.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del módulo.
     */
    public function __construct(
        protected MetricsCalculatorService $metricsCalculator,
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Genera proyecciones financieras.
     *
     * @param string $scenario
     *   Tipo de escenario: 'base', 'optimistic', 'pessimistic', 'custom'.
     * @param int $horizonMonths
     *   Número de meses a proyectar.
     * @param array $customParams
     *   Parámetros personalizados para escenario 'custom'.
     *
     * @return array
     *   Array con proyecciones mes a mes.
     */
    public function generateProjections(
        string $scenario = 'base',
        int $horizonMonths = 12,
        array $customParams = []
    ): array {
        // Obtener métricas actuales
        $currentMetrics = $this->getCurrentMetrics();

        $this->logger->info('Generando proyecciones: escenario @scenario, @months meses', [
            '@scenario' => $scenario,
            '@months' => $horizonMonths,
        ]);

        // Usar modelo de proyección lineal
        return $this->generateLinearProjection($currentMetrics, $scenario, $horizonMonths, $customParams);
    }

    /**
     * Obtiene las métricas actuales para proyección.
     *
     * @return array
     *   Métricas actuales.
     */
    protected function getCurrentMetrics(): array
    {
        return [
            'mrr' => $this->metricsCalculator->calculateMRR(),
            'arr' => $this->metricsCalculator->calculateARR(),
            'gross_margin' => $this->metricsCalculator->calculateGrossMargin(),
            'ltv' => $this->metricsCalculator->calculateLTV(),
            'ltv_cac_ratio' => $this->metricsCalculator->calculateLTVCACRatio(),
            'arpu' => $this->metricsCalculator->calculateARPU(),
        ];
    }

    /**
     * Genera proyección usando modelo lineal con tasas por escenario.
     *
     * @param array $metrics
     *   Métricas actuales.
     * @param string $scenario
     *   Tipo de escenario.
     * @param int $months
     *   Meses a proyectar.
     * @param array $customParams
     *   Parámetros personalizados.
     *
     * @return array
     *   Proyección.
     */
    protected function generateLinearProjection(array $metrics, string $scenario, int $months, array $customParams = []): array
    {
        $growthRates = [
            'base' => 0.02,        // 2% mensual
            'optimistic' => 0.04,  // 4% mensual
            'pessimistic' => 0.005, // 0.5% mensual
            'custom' => $customParams['growth_rate'] ?? 0.02,
        ];

        $churnRates = [
            'base' => 0.03,        // 3% mensual
            'optimistic' => 0.015, // 1.5% mensual
            'pessimistic' => 0.05, // 5% mensual
            'custom' => $customParams['churn_rate'] ?? 0.03,
        ];

        $growthRate = $growthRates[$scenario] ?? 0.02;
        $churnRate = $churnRates[$scenario] ?? 0.03;
        $currentMrr = (float) $metrics['mrr'];
        $projections = [];

        for ($i = 1; $i <= $months; $i++) {
            // Crecimiento compuesto ajustado por churn
            $netGrowth = $growthRate - $churnRate;
            $projectedMrr = $currentMrr * pow(1 + $netGrowth, $i);

            $projections[] = [
                'month' => $i,
                'mrr' => round($projectedMrr, 2),
                'arr' => round($projectedMrr * 12, 2),
                'growth_rate' => round($growthRate * 100, 2),
                'churn_rate' => round($churnRate * 100, 2),
                'net_growth' => round($netGrowth * 100, 2),
            ];
        }

        $endMrr = $projections[count($projections) - 1]['mrr'] ?? $currentMrr;
        $totalGrowth = $currentMrr > 0 ? (($endMrr - $currentMrr) / $currentMrr) * 100 : 0;

        return [
            'scenario' => $scenario,
            'horizon_months' => $months,
            'current_metrics' => $metrics,
            'projections' => [
                'monthly_projections' => $projections,
                'summary' => [
                    'start_mrr' => $currentMrr,
                    'end_mrr' => $endMrr,
                    'total_growth_percent' => round($totalGrowth, 2),
                    'avg_monthly_growth' => round($growthRate * 100, 2),
                    'avg_monthly_churn' => round($churnRate * 100, 2),
                    'confidence' => 'medium',
                    'model' => 'linear_compound',
                ],
            ],
            'generated_at' => \Drupal::time()->getRequestTime(),
        ];
    }

}
