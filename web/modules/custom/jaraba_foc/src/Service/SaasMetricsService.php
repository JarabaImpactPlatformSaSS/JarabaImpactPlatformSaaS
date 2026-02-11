<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de cálculo de métricas SaaS 2.0.
 *
 * PROPÓSITO:
 * Calcula las métricas financieras clave para SaaS según benchmarks 2025:
 * - MRR (Monthly Recurring Revenue)
 * - ARR (Annual Recurring Revenue)
 * - Churn Rate (Logo y Revenue)
 * - NRR (Net Revenue Retention)
 * - GRR (Gross Revenue Retention)
 * - LTV (Customer Lifetime Value)
 * - CAC (Customer Acquisition Cost)
 * - LTV:CAC Ratio
 *
 * BENCHMARKS 2025:
 * - MRR Growth: 15-20% MoM early stage
 * - Gross Margin: 70-85%
 * - NRR: >100% (ideal 110-120%)
 * - Logo Churn: <5% anual
 * - LTV:CAC: ≥3:1
 * - CAC Payback: <12 meses
 *
 * @see docs/tecnicos/20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md
 */
class SaasMetricsService
{

    /**
     * Database connection.
     */
    protected Connection $database;

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Calcula MRR (Monthly Recurring Revenue).
     *
     * MRR = Suma de ingresos recurrentes mensuales normalizados.
     * Excluye one-time fees.
     *
     * @param string|null $verticalId
     *   ID del vertical para filtrar (opcional).
     * @param string|null $month
     *   Mes en formato 'Y-m' (opcional, por defecto mes actual).
     *
     * @return float
     *   MRR en EUR.
     */
    public function calculateMRR(?string $verticalId = NULL, ?string $month = NULL): float
    {
        $month = $month ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=');

        // Solo ingresos recurrentes
        $query->condition('ft.transaction_type', ['recurring_revenue', 'subscription'], 'IN');

        if ($verticalId) {
            $query->condition('ft.related_vertical', $verticalId);
        }

        $query->addExpression('SUM(ft.amount)', 'total');
        $result = $query->execute()->fetchField();

        return (float) ($result ?? 0);
    }

    /**
     * Calcula ARR (Annual Recurring Revenue).
     *
     * ARR = MRR × 12
     *
     * @param string|null $verticalId
     *   ID del vertical para filtrar.
     *
     * @return float
     *   ARR en EUR.
     */
    public function calculateARR(?string $verticalId = NULL): float
    {
        return $this->calculateMRR($verticalId) * 12;
    }

    /**
     * Calcula Churn Rate (Logo Churn).
     *
     * Logo Churn = Clientes perdidos / Clientes totales inicio período
     * Benchmark: <5% anual
     *
     * @param string $period
     *   Período: 'month' o 'year'.
     *
     * @return float
     *   Tasa de churn como porcentaje.
     */
    public function calculateLogoChurn(string $period = 'month'): float
    {
        $now = new \DateTime();

        if ($period === 'year') {
            $startDate = $now->modify('-1 year')->format('Y-m-d');
        } else {
            $startDate = $now->modify('-1 month')->format('Y-m-d');
        }

        // Contar tenants activos al inicio del período
        $startingCustomers = $this->countActiveTenantsAtDate($startDate);

        if ($startingCustomers === 0) {
            return 0.0;
        }

        // Contar tenants que churnearon (cancelaron suscripción)
        $churnedQuery = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_type', 'subscription_canceled');

        $churnedQuery->addExpression('COUNT(DISTINCT ft.related_tenant)', 'churned');
        $churned = (int) $churnedQuery->execute()->fetchField();

        return ($churned / $startingCustomers) * 100;
    }

    /**
     * Calcula NRR (Net Revenue Retention).
     *
     * NRR = (Starting MRR + Expansion - Churn - Contraction) / Starting MRR × 100
     * Benchmark: >100% (ideal 110-120%)
     *
     * @param string|null $month
     *   Mes en formato 'Y-m'.
     *
     * @return float
     *   NRR como porcentaje.
     */
    public function calculateNRR(?string $month = NULL): float
    {
        $month = $month ?? date('Y-m', strtotime('-1 month'));
        $previousMonth = date('Y-m', strtotime($month . '-01 -1 month'));

        $startingMRR = $this->calculateMRR(NULL, $previousMonth);

        if ($startingMRR === 0.0) {
            return 100.0;
        }

        $currentMRR = $this->calculateMRR(NULL, $month);

        // Calcular expansión (upgrades)
        $expansion = $this->calculateExpansionMRR($month);

        // Calcular contracción (downgrades)
        $contraction = $this->calculateContractionMRR($month);

        // Calcular churn MRR
        $churnMRR = $this->calculateChurnMRR($month);

        $nrr = (($startingMRR + $expansion - $churnMRR - $contraction) / $startingMRR) * 100;

        return round($nrr, 2);
    }

    /**
     * Calcula GRR (Gross Revenue Retention).
     *
     * GRR = (Starting MRR - Churn - Contraction) / Starting MRR × 100
     * Benchmark: 85-95%
     *
     * @param string|null $month
     *   Mes en formato 'Y-m'.
     *
     * @return float
     *   GRR como porcentaje.
     */
    public function calculateGRR(?string $month = NULL): float
    {
        $month = $month ?? date('Y-m', strtotime('-1 month'));
        $previousMonth = date('Y-m', strtotime($month . '-01 -1 month'));

        $startingMRR = $this->calculateMRR(NULL, $previousMonth);

        if ($startingMRR === 0.0) {
            return 100.0;
        }

        $contraction = $this->calculateContractionMRR($month);
        $churnMRR = $this->calculateChurnMRR($month);

        $grr = (($startingMRR - $churnMRR - $contraction) / $startingMRR) * 100;

        return max(0, round($grr, 2));
    }

    /**
     * Calcula LTV (Customer Lifetime Value).
     *
     * LTV = (ARPU × Gross Margin) / Revenue Churn Rate
     *
     * @return float
     *   LTV en EUR.
     */
    public function calculateLTV(): float
    {
        $arpu = $this->calculateARPU();
        $grossMargin = $this->calculateGrossMargin() / 100;
        $revenueChurn = $this->calculateRevenueChurn();

        if ($revenueChurn === 0.0) {
            // Si no hay churn, usar estimación de 5 años
            return $arpu * $grossMargin * 60;
        }

        return ($arpu * $grossMargin) / ($revenueChurn / 100);
    }

    /**
     * Calcula CAC (Customer Acquisition Cost).
     *
     * CAC = (S&M Spend Total) / New Customers
     *
     * @param string|null $month
     *   Mes en formato 'Y-m'.
     *
     * @return float
     *   CAC en EUR.
     */
    public function calculateCAC(?string $month = NULL): float
    {
        $month = $month ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Obtener gastos de marketing
        $marketingQuery = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.transaction_type', ['marketing_cost', 'sales_cost'], 'IN');

        $marketingQuery->addExpression('ABS(SUM(ft.amount))', 'total');
        $marketingSpend = (float) ($marketingQuery->execute()->fetchField() ?? 0);

        // Contar nuevos clientes
        $newCustomers = $this->countNewCustomers($month);

        if ($newCustomers === 0) {
            return 0.0;
        }

        return $marketingSpend / $newCustomers;
    }

    /**
     * Calcula ratio LTV:CAC.
     *
     * Benchmark: ≥3:1 (ideal 5:1)
     *
     * @return float
     *   Ratio LTV:CAC.
     */
    public function calculateLTVCACRatio(): float
    {
        $ltv = $this->calculateLTV();
        $cac = $this->calculateCAC();

        if ($cac === 0.0) {
            return 0.0;
        }

        return round($ltv / $cac, 2);
    }

    /**
     * Calcula ARPU (Average Revenue Per User).
     *
     * ARPU = MRR / Active Customers
     *
     * @return float
     *   ARPU en EUR.
     */
    public function calculateARPU(): float
    {
        $mrr = $this->calculateMRR();
        $activeCustomers = $this->countActiveTenantsAtDate(date('Y-m-d'));

        if ($activeCustomers === 0) {
            return 0.0;
        }

        return $mrr / $activeCustomers;
    }

    /**
     * Calcula Gross Margin.
     *
     * Gross Margin = (Revenue - COGS) / Revenue × 100
     * COGS = Hosting + Support + DevOps + Payment Processing
     * Benchmark: 70-85%
     *
     * @param string|null $month
     *   Mes en formato 'Y-m'.
     *
     * @return float
     *   Gross Margin como porcentaje.
     */
    public function calculateGrossMargin(?string $month = NULL): float
    {
        $month = $month ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Revenue total
        $revenueQuery = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.amount', 0, '>');

        $revenueQuery->addExpression('SUM(ft.amount)', 'total');
        $revenue = (float) ($revenueQuery->execute()->fetchField() ?? 0);

        if ($revenue === 0.0) {
            return 0.0;
        }

        // COGS (costes directos)
        $cogsQuery = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.transaction_type', ['hosting_cost', 'support_cost', 'payment_processing'], 'IN');

        $cogsQuery->addExpression('ABS(SUM(ft.amount))', 'total');
        $cogs = (float) ($cogsQuery->execute()->fetchField() ?? 0);

        return (($revenue - $cogs) / $revenue) * 100;
    }

    /**
     * Obtiene snapshot completo de métricas.
     *
     * @return array
     *   Array con todas las métricas calculadas.
     */
    public function getMetricsSnapshot(): array
    {
        $mrr = $this->calculateMRR();
        $arr = $this->calculateARR();
        $nrr = $this->calculateNRR();
        $grr = $this->calculateGRR();
        $ltv = $this->calculateLTV();
        $cac = $this->calculateCAC();
        $ltvCacRatio = $this->calculateLTVCACRatio();
        $arpu = $this->calculateARPU();
        $grossMargin = $this->calculateGrossMargin();
        $logoChurn = $this->calculateLogoChurn('month');

        return [
            'timestamp' => date('c'),
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'nrr' => $nrr,
            'grr' => $grr,
            'ltv' => round($ltv, 2),
            'cac' => round($cac, 2),
            'ltv_cac_ratio' => $ltvCacRatio,
            'arpu' => round($arpu, 2),
            'gross_margin' => round($grossMargin, 2),
            'logo_churn_monthly' => round($logoChurn, 2),
            'active_customers' => $this->countActiveTenantsAtDate(date('Y-m-d')),
            'health_indicators' => $this->getHealthIndicators($mrr, $nrr, $ltvCacRatio, $grossMargin),
        ];
    }

    /**
     * Obtiene indicadores de salud basados en benchmarks.
     */
    protected function getHealthIndicators(float $mrr, float $nrr, float $ltvCacRatio, float $grossMargin): array
    {
        return [
            'nrr_status' => $nrr >= 100 ? 'healthy' : 'warning',
            'ltv_cac_status' => $ltvCacRatio >= 3 ? 'healthy' : ($ltvCacRatio >= 2 ? 'warning' : 'critical'),
            'gross_margin_status' => $grossMargin >= 70 ? 'healthy' : ($grossMargin >= 50 ? 'warning' : 'critical'),
        ];
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Cuenta tenants activos a una fecha.
     */
    protected function countActiveTenantsAtDate(string $date): int
    {
        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');
            $query = $tenantStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', 1);

            return (int) $query->count()->execute();
        } catch (\Exception $e) {
            $this->logger->warning('Error counting tenants: @error', ['@error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Cuenta nuevos clientes en un mes.
     */
    protected function countNewCustomers(string $month): int
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');
            $query = $tenantStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('created', strtotime($startDate), '>=')
                ->condition('created', strtotime($endDate . ' 23:59:59'), '<=');

            return (int) $query->count()->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calcula Expansion MRR (upgrades).
     */
    protected function calculateExpansionMRR(string $month): float
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.transaction_type', 'upgrade');

        $query->addExpression('SUM(ft.amount)', 'total');
        return (float) ($query->execute()->fetchField() ?? 0);
    }

    /**
     * Calcula Contraction MRR (downgrades).
     */
    protected function calculateContractionMRR(string $month): float
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.transaction_timestamp', $startDate, '>=')
            ->condition('ft.transaction_timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.transaction_type', 'downgrade');

        $query->addExpression('ABS(SUM(ft.amount))', 'total');
        return (float) ($query->execute()->fetchField() ?? 0);
    }

    /**
     * Calcula Churn MRR.
     */
    protected function calculateChurnMRR(string $month): float
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = $this->database->select('financial_transaction', 'ft')
            ->condition('ft.timestamp', $startDate, '>=')
            ->condition('ft.timestamp', $endDate . ' 23:59:59', '<=')
            ->condition('ft.transaction_type', 'subscription_canceled');

        $query->addExpression('ABS(SUM(ft.amount))', 'total');
        return (float) ($query->execute()->fetchField() ?? 0);
    }

    /**
     * Calcula Revenue Churn Rate mensual.
     */
    protected function calculateRevenueChurn(): float
    {
        $previousMonth = date('Y-m', strtotime('-1 month'));
        $startingMRR = $this->calculateMRR(NULL, $previousMonth);

        if ($startingMRR === 0.0) {
            return 0.0;
        }

        $currentMonth = date('Y-m');
        $churnMRR = $this->calculateChurnMRR($currentMonth);
        $contraction = $this->calculateContractionMRR($currentMonth);

        return (($churnMRR + $contraction) / $startingMRR) * 100;
    }

}
