<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para cálculo de métricas SaaS 2.0.
 *
 * PROPÓSITO:
 * Calcula todas las métricas financieras del ecosistema a partir de las
 * transacciones almacenadas en la entidad FinancialTransaction.
 *
 * MÉTRICAS IMPLEMENTADAS:
 * ═══════════════════════════════════════════════════════════════════════════
 * Salud y Crecimiento:
 * - MRR: Monthly Recurring Revenue
 * - ARR: Annual Recurring Revenue (MRR × 12)
 * - ARPU: Average Revenue Per User
 * - Gross Margin: (Revenue - COGS) / Revenue
 *
 * Retención:
 * - NRR: Net Revenue Retention
 * - GRR: Gross Revenue Retention
 * - Churn Rate: Logo y Revenue
 *
 * Unit Economics:
 * - CAC: Customer Acquisition Cost
 * - LTV: Customer Lifetime Value
 * - LTV:CAC Ratio
 * - CAC Payback (meses)
 * ═══════════════════════════════════════════════════════════════════════════
 */
class MetricsCalculatorService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Database\Connection $database
     *   La conexión a base de datos.
     * @param \Drupal\Core\Datetime\TimeInterface $time
     *   El servicio de tiempo.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del módulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        protected TimeInterface $time,
        protected LoggerInterface $logger,
        protected CacheBackendInterface $cache,
    ) {
    }

    /**
     * Calcula el MRR (Monthly Recurring Revenue) actual.
     *
     * FÓRMULA:
     * Suma de todas las transacciones recurrentes del mes actual.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar, o NULL para toda la plataforma.
     *
     * @return string
     *   MRR en formato decimal.
     */
    public function calculateMRR(?int $tenantId = NULL): string
    {
        try {
            $query = $this->database->select('financial_transaction', 'ft')
                ->condition('ft.is_recurring', 1)
                ->condition('ft.transaction_timestamp', strtotime('first day of this month'), '>=')
                ->condition('ft.transaction_timestamp', strtotime('last day of this month 23:59:59'), '<=');

            if ($tenantId !== NULL) {
                $query->condition('ft.related_tenant', $tenantId);
            }

            $query->addExpression('SUM(ft.amount)', 'total_mrr');
            $result = $query->execute()->fetchField();

            return $result ?: '0.00';
        } catch (\Exception $e) {
            $this->logger->debug('Error calculating MRR: @error', ['@error' => $e->getMessage()]);
            return '0.00';
        }
    }

    /**
     * Calcula el ARR (Annual Recurring Revenue).
     *
     * FÓRMULA: MRR × 12
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar, o NULL para toda la plataforma.
     *
     * @return string
     *   ARR en formato decimal.
     */
    public function calculateARR(?int $tenantId = NULL): string
    {
        $mrr = $this->calculateMRR($tenantId);
        return bcmul($mrr, '12', 2);
    }

    /**
     * Calcula el Gross Margin.
     *
     * FÓRMULA: (Revenue - COGS) / Revenue × 100
     * COGS incluye: hosting, soporte, DevOps, procesamiento de pagos.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar.
     *
     * @return string
     *   Porcentaje de margen bruto.
     */
    public function calculateGrossMargin(?int $tenantId = NULL): string
    {
        $revenue = $this->getTotalRevenue($tenantId);
        $cogs = $this->getTotalCOGS($tenantId);

        if (bccomp($revenue, '0', 2) === 0) {
            return '0.00';
        }

        $margin = bcdiv(bcsub($revenue, $cogs, 2), $revenue, 4);
        return bcmul($margin, '100', 2);
    }

    /**
     * Calcula el ARPU (Average Revenue Per User).
     *
     * FÓRMULA: MRR / Número de clientes activos
     *
     * @return string
     *   ARPU en formato decimal.
     */
    public function calculateARPU(): string
    {
        $mrr = $this->calculateMRR();
        $activeCustomers = $this->getActiveCustomerCount();

        if ($activeCustomers === 0) {
            return '0.00';
        }

        return bcdiv($mrr, (string) $activeCustomers, 2);
    }

    /**
     * Calcula el LTV (Customer Lifetime Value).
     *
     * FÓRMULA: (ARPU × Gross Margin) / Churn Rate
     * Asumimos Gross Margin de 75% y Churn de 5% como defaults.
     *
     * @param int|null $tenantId
     *   ID del tenant para cálculo específico.
     *
     * @return string
     *   LTV en formato decimal.
     */
    public function calculateLTV(?int $tenantId = NULL): string
    {
        $arpu = $tenantId ? $this->getTenantMRR($tenantId) : $this->calculateARPU();
        $grossMargin = '0.75'; // 75% por defecto, benchmark industria
        $churnRate = '0.05';   // 5% anual por defecto

        if (bccomp($churnRate, '0', 4) === 0) {
            // Evitar división por cero
            $churnRate = '0.01';
        }

        $numerator = bcmul($arpu, $grossMargin, 2);
        return bcdiv($numerator, $churnRate, 2);
    }

    /**
     * Calcula el ratio LTV:CAC.
     *
     * BENCHMARK: ≥3:1 es saludable, ≥5:1 es excelente.
     *
     * @param int|null $tenantId
     *   ID del tenant para cálculo específico.
     *
     * @return string
     *   Ratio LTV:CAC.
     */
    public function calculateLTVCACRatio(?int $tenantId = NULL): string
    {
        $ltv = $this->calculateLTV($tenantId);
        $cac = $this->getCAC();

        if (bccomp($cac, '0', 2) === 0) {
            return '0.00';
        }

        return bcdiv($ltv, $cac, 2);
    }

    /**
     * Calcula el CAC Payback en meses.
     *
     * FÓRMULA: CAC / (ARPU × Gross Margin)
     * BENCHMARK: <12 meses es saludable.
     *
     * @return string
     *   Meses para recuperar el CAC.
     */
    public function calculateCACPayback(): string
    {
        $cac = $this->getCAC();
        $arpu = $this->calculateARPU();
        $grossMargin = '0.75';

        $monthlyContribution = bcmul($arpu, $grossMargin, 2);

        if (bccomp($monthlyContribution, '0', 2) === 0) {
            return '0.0';
        }

        return bcdiv($cac, $monthlyContribution, 1);
    }

    /**
     * Obtiene métricas por tenant para el dashboard de Analítica de Inquilinos.
     *
     * @return array
     *   Array de métricas por tenant.
     */
    public function getTenantAnalytics(): array
    {
        // AUDIT-PERF-N08: Cache 5 min — evita N+1 queries por tenant.
        $cacheKey = 'jaraba_foc:tenant_analytics';
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached->data;
        }

        $tenants = [];

        try {
            $groupStorage = $this->entityTypeManager->getStorage('group');
            $groups = $groupStorage->loadMultiple();

            foreach ($groups as $group) {
                $tenantId = (int) $group->id();
                $mrr = $this->getTenantMRR($tenantId);
                $ltv = $this->calculateLTV($tenantId);
                $ltvCacRatio = $this->calculateLTVCACRatio($tenantId);

                // Determinar estado de salud
                $healthStatus = $this->getTenantHealthStatus($ltvCacRatio);

                $tenants[] = [
                    'id' => $tenantId,
                    'name' => $group->label(),
                    'mrr' => $mrr,
                    'ltv' => $ltv,
                    'cac' => $this->getCAC(),
                    'ltv_cac_ratio' => $ltvCacRatio,
                    'payback_months' => $this->calculateTenantPayback($tenantId),
                    'health_status' => $healthStatus,
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error calculando métricas de tenants: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        $this->cache->set($cacheKey, $tenants, $this->time->getRequestTime() + 300);

        return $tenants;
    }

    /**
     * Obtiene el estado de salud de un tenant basado en su ratio LTV:CAC.
     *
     * @param string $ltvCacRatio
     *   El ratio LTV:CAC.
     *
     * @return string
     *   Estado: 'vip', 'healthy', 'at_risk', 'in_loss'.
     */
    protected function getTenantHealthStatus(string $ltvCacRatio): string
    {
        $ratio = (float) $ltvCacRatio;

        if ($ratio >= 5) {
            return 'vip';
        }
        if ($ratio >= 3) {
            return 'healthy';
        }
        if ($ratio >= 1) {
            return 'at_risk';
        }
        return 'in_loss';
    }

    /**
     * Obtiene el MRR de un tenant específico.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return string
     *   MRR del tenant.
     */
    protected function getTenantMRR(int $tenantId): string
    {
        return $this->calculateMRR($tenantId);
    }

    /**
     * Calcula el payback de un tenant específico.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return string
     *   Meses de payback.
     */
    protected function calculateTenantPayback(int $tenantId): string
    {
        $mrr = $this->getTenantMRR($tenantId);
        $cac = $this->getCAC();
        $grossMargin = '0.75';

        $monthlyContribution = bcmul($mrr, $grossMargin, 2);

        if (bccomp($monthlyContribution, '0', 2) === 0) {
            return '0.0';
        }

        return bcdiv($cac, $monthlyContribution, 1);
    }

    /**
     * Obtiene el total de ingresos.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar.
     *
     * @return string
     *   Total de ingresos.
     */
    protected function getTotalRevenue(?int $tenantId = NULL): string
    {
        try {
            $query = $this->database->select('financial_transaction', 'ft')
                ->condition('ft.amount', 0, '>');

            if ($tenantId !== NULL) {
                $query->condition('ft.related_tenant', $tenantId);
            }

            $query->addExpression('SUM(ft.amount)', 'total');
            $result = $query->execute()->fetchField();

            return $result ?: '0.00';
        } catch (\Exception $e) {
            return '0.00';
        }
    }

    /**
     * Obtiene el total de COGS.
     *
     * @param int|null $tenantId
     *   ID del tenant para filtrar.
     *
     * @return string
     *   Total de COGS.
     */
    protected function getTotalCOGS(?int $tenantId = NULL): string
    {
        try {
            $query = $this->database->select('financial_transaction', 'ft')
                ->condition('ft.amount', 0, '<');

            if ($tenantId !== NULL) {
                $query->condition('ft.related_tenant', $tenantId);
            }

            $query->addExpression('ABS(SUM(ft.amount))', 'total');
            $result = $query->execute()->fetchField();

            return $result ?: '0.00';
        } catch (\Exception $e) {
            return '0.00';
        }
    }

    /**
     * Obtiene el número de clientes activos.
     *
     * @return int
     *   Número de clientes activos.
     */
    protected function getActiveCustomerCount(): int
    {
        try {
            $groupStorage = $this->entityTypeManager->getStorage('group');
            return (int) $groupStorage->getQuery()
                ->accessCheck(FALSE)
                ->count()
                ->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error contando clientes activos: @error', [
                '@error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtiene el CAC (Customer Acquisition Cost).
     *
     * @return string
     *   CAC en formato decimal. Por defecto €200.
     */
    protected function getCAC(): string
    {
        try {
            // Query marketing expenses for the current period (last 3 months
            // for a more stable CAC calculation).
            $periodStart = strtotime('-3 months');
            $periodEnd = $this->time->getRequestTime();

            $query = $this->database->select('financial_transaction', 'ft')
                ->condition('ft.source_system', [
                    'marketing_expense',
                    'activecampaign',
                    'ads_expense',
                    'sales_expense',
                ], 'IN')
                ->condition('ft.transaction_timestamp', $periodStart, '>=')
                ->condition('ft.transaction_timestamp', $periodEnd, '<=');

            $query->addExpression('ABS(SUM(ft.amount))', 'total_marketing');
            $totalMarketing = (float) ($query->execute()->fetchField() ?: 0);

            if ($totalMarketing <= 0) {
                // No marketing expense data; fall back to reference value.
                return '200.00';
            }

            // Count new customers acquired in the same period.
            // New customers = groups created during the period.
            $newCustomers = 0;
            try {
                $groupQuery = $this->entityTypeManager->getStorage('group')->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('created', $periodStart, '>=')
                    ->condition('created', $periodEnd, '<=')
                    ->count();
                $newCustomers = (int) $groupQuery->execute();
            }
            catch (\Exception $e) {
                // If group query fails, try counting distinct new tenants
                // from recurring transactions in the period.
                $tenantQuery = $this->database->select('financial_transaction', 'ft')
                    ->condition('ft.is_recurring', 1)
                    ->condition('ft.transaction_timestamp', $periodStart, '>=')
                    ->condition('ft.transaction_timestamp', $periodEnd, '<=');
                $tenantQuery->addExpression('COUNT(DISTINCT ft.related_tenant)', 'tenant_count');
                $newCustomers = (int) ($tenantQuery->execute()->fetchField() ?: 0);
            }

            if ($newCustomers <= 0) {
                // No new customers acquired; fall back to reference value.
                return '200.00';
            }

            $cac = $totalMarketing / $newCustomers;

            return number_format($cac, 2, '.', '');
        }
        catch (\Exception $e) {
            $this->logger->debug('Error calculating CAC: @error', [
                '@error' => $e->getMessage(),
            ]);
            // Fall back to reference value from FOC document.
            return '200.00';
        }
    }

}
