<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\FairUsePolicyService;
use Drupal\jaraba_billing\Service\WalletService;

/**
 * Servicio de metering avanzado por tenant.
 *
 * PROPÓSITO:
 * Rastrea y mide el uso detallado de recursos por tenant
 * para permitir facturación basada en uso (usage-based pricing).
 *
 * Unit prices are read from FairUsePolicy ConfigEntity via
 * FairUsePolicyService (no hardcoded values).
 *
 * Q4 2026 - Sprint 13-14: Outcome-Based Pricing
 */
class TenantMeteringService
{

    /**
     * Tipos de métricas medibles.
     */
    public const METRIC_API_CALLS = 'api_calls';
    public const METRIC_AI_TOKENS = 'ai_tokens';
    public const METRIC_STORAGE_MB = 'storage_mb';
    public const METRIC_ORDERS = 'orders';
    public const METRIC_PRODUCTS = 'products';
    public const METRIC_CUSTOMERS = 'customers';
    public const METRIC_EMAILS_SENT = 'emails_sent';
    public const METRIC_BANDWIDTH_GB = 'bandwidth_gb';

    /**
     * Fallback unit prices — used when FairUsePolicyService unavailable.
     */
    protected const FALLBACK_UNIT_PRICES = [
        self::METRIC_API_CALLS => 0.0001,
        self::METRIC_AI_TOKENS => 0.00002,
        self::METRIC_STORAGE_MB => 0.001,
        self::METRIC_ORDERS => 0.50,
        self::METRIC_PRODUCTS => 0.10,
        self::METRIC_CUSTOMERS => 0.05,
        self::METRIC_EMAILS_SENT => 0.001,
        self::METRIC_BANDWIDTH_GB => 0.05,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected CacheBackendInterface $cache,
        protected WalletService $walletService,
        protected ?FairUsePolicyService $fairUsePolicyService = NULL,
    ) {
    }

    /**
     * Registra uso de una métrica.
     */
    public function record(string $tenantId, string $metric, float $value, array $metadata = []): void
    {
        // 1. Calcular coste.
        $unitPrice = $this->getUnitPrice($metric, $tenantId);
        $cost = $value * $unitPrice;

        // 2. Intentar deducir del Wallet (Prepago).
        $paidFromWallet = FALSE;
        if ($cost > 0) {
            try {
                // Si tiene saldo suficiente, descontamos.
                // Nota: En un sistema real, aquí iría una lógica de configuración "Preferir Wallet".
                if ($this->walletService->getBalance((int) $tenantId) >= $cost) {
                    $paidFromWallet = $this->walletService->debit(
                        (int) $tenantId,
                        $cost,
                        'usage_metering',
                        uniqid('usg_'),
                        "Usage: $metric ($value units)"
                    );
                }
            } catch (\Exception $e) {
                // Fallo silencioso del wallet, registramos como postpago normal.
            }
        }

        // 3. Registrar métrica (marcando si ya se pagó).
        $this->database->insert('tenant_metering')
            ->fields([
                'tenant_id' => $tenantId,
                'metric' => $metric,
                'value' => $value,
                'metadata' => json_encode($metadata + ['prepaid' => $paidFromWallet]),
                'created' => time(),
                'period' => date('Y-m'),
            ])
            ->execute();
    }

    /**
     * Incrementa una métrica.
     */
    public function increment(string $tenantId, string $metric, float $amount = 1, array $metadata = []): void
    {
        $this->record($tenantId, $metric, $amount, $metadata);
    }

    /**
     * Obtiene uso total de un tenant para un período.
     */
    public function getUsage(string $tenantId, ?string $period = NULL): array
    {
        $period = $period ?? date('Y-m');

        // AUDIT-PERF-N08: Cache 5 min por tenant/período.
        $cacheKey = "jaraba_billing:metering:{$tenantId}:{$period}";
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached->data;
        }

        $query = $this->database->select('tenant_metering', 'tm')
            ->fields('tm', ['metric'])
            ->condition('tenant_id', $tenantId)
            ->condition('period', $period)
            ->groupBy('metric');
        $query->addExpression('SUM(value)', 'total');

        $results = $query->execute()->fetchAllKeyed();

        $usage = [];
        foreach ($results as $metric => $total) {
            $price = $this->getUnitPrice($metric, $tenantId);
            $usage[$metric] = [
                'total' => (float) $total,
                'unit_price' => $price,
                'cost' => (float) $total * $price,
            ];
        }

        $result = [
            'tenant_id' => $tenantId,
            'period' => $period,
            'metrics' => $usage,
            'total_cost' => array_sum(array_column($usage, 'cost')),
        ];

        $this->cache->set($cacheKey, $result, time() + 300);

        return $result;
    }

    /**
     * Obtiene uso histórico por mes.
     */
    public function getHistoricalUsage(string $tenantId, int $months = 6): array
    {
        // AUDIT-PERF-N08: Cache 10 min para datos históricos.
        $cacheKey = "jaraba_billing:metering_history:{$tenantId}:{$months}";
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached->data;
        }

        $results = $this->database->select('tenant_metering', 'tm')
            ->fields('tm', ['period', 'metric'])
            ->condition('tenant_id', $tenantId)
            ->groupBy('period')
            ->groupBy('metric')
            ->orderBy('period', 'ASC');
        $results->addExpression('SUM(value)', 'total');

        $data = $results->execute()->fetchAll();

        $history = [];
        foreach ($data as $row) {
            $history[$row->period][$row->metric] = (float) $row->total;
        }

        $result = array_slice($history, -$months, $months, TRUE);

        $this->cache->set($cacheKey, $result, time() + 600);

        return $result;
    }

    /**
     * Calcula factura estimada para un tenant.
     */
    public function calculateBill(string $tenantId, ?string $period = NULL): array
    {
        $usage = $this->getUsage($tenantId, $period);

        $lineItems = [];
        foreach ($usage['metrics'] as $metric => $data) {
            if ($data['total'] > 0) {
                $lineItems[] = [
                    'description' => $this->getMetricDisplayName($metric),
                    'quantity' => $data['total'],
                    'unit_price' => $data['unit_price'],
                    'amount' => round($data['cost'], 2),
                ];
            }
        }

        $subtotal = array_sum(array_column($lineItems, 'amount'));
        $tax = round($subtotal * 0.21, 2); // IVA 21%

        return [
            'tenant_id' => $tenantId,
            'period' => $usage['period'],
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'currency' => $this->resolveTenantCurrency($tenantId),
        ];
    }

    /**
     * Obtiene nombre visible de métrica.
     */
    protected function getMetricDisplayName(string $metric): string
    {
        $names = [
            self::METRIC_API_CALLS => 'Llamadas API',
            self::METRIC_AI_TOKENS => 'Tokens IA',
            self::METRIC_STORAGE_MB => 'Almacenamiento (MB)',
            self::METRIC_ORDERS => 'Pedidos procesados',
            self::METRIC_PRODUCTS => 'Productos activos',
            self::METRIC_CUSTOMERS => 'Clientes',
            self::METRIC_EMAILS_SENT => 'Emails enviados',
            self::METRIC_BANDWIDTH_GB => 'Ancho de banda (GB)',
        ];

        return $names[$metric] ?? ucfirst(str_replace('_', ' ', $metric));
    }

    /**
     * Obtiene proyección de costes para el próximo mes.
     */
    public function getForecast(string $tenantId): array
    {
        $currentUsage = $this->getUsage($tenantId);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) date('m'), (int) date('Y'));
        $currentDay = (int) date('j');
        $remainingDays = $daysInMonth - $currentDay;

        $dailyRate = $currentDay > 0 ? $currentUsage['total_cost'] / $currentDay : 0;
        $projectedTotal = $currentUsage['total_cost'] + ($dailyRate * $remainingDays);

        return [
            'current_spend' => round($currentUsage['total_cost'], 2),
            'daily_rate' => round($dailyRate, 2),
            'projected_total' => round($projectedTotal, 2),
            'days_remaining' => $remainingDays,
            'confidence' => $currentDay > 15 ? 'high' : ($currentDay > 7 ? 'medium' : 'low'),
        ];
    }

    /**
     * Verifica si el tenant está cerca de sus límites de presupuesto.
     */
    public function checkBudgetAlerts(string $tenantId, float $monthlyBudget): array
    {
        $usage = $this->getUsage($tenantId);
        $forecast = $this->getForecast($tenantId);

        $alerts = [];

        // 80% del presupuesto alcanzado.
        if ($usage['total_cost'] >= $monthlyBudget * 0.8) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Has alcanzado el 80% de tu presupuesto mensual',
                'current' => $usage['total_cost'],
                'budget' => $monthlyBudget,
            ];
        }

        // Proyección supera presupuesto.
        if ($forecast['projected_total'] > $monthlyBudget) {
            $overage = $forecast['projected_total'] - $monthlyBudget;
            $alerts[] = [
                'type' => 'critical',
                'message' => "Proyección sugiere que superarás tu presupuesto en €" . round($overage, 2),
                'projected' => $forecast['projected_total'],
                'budget' => $monthlyBudget,
            ];
        }

        return $alerts;
    }

    /**
     * Gets the unit price for a metric, optionally per-tenant tier.
     *
     * Delegates to FairUsePolicyService when available; falls back to
     * built-in defaults. The FALLBACK_UNIT_PRICES constant is used only
     * when the FairUsePolicy ConfigEntity is not yet installed.
     *
     * @param string $metric
     *   The metric key (e.g. 'ai_tokens', 'api_calls').
     * @param int|string $tenantId
     *   The tenant ID (used to resolve tier for tier-specific pricing).
     *
     * @return float
     *   Unit price in EUR.
     */
    public function getUnitPrice(string $metric, int|string $tenantId = 0): float {
        if ($this->fairUsePolicyService) {
            try {
                $tier = $this->resolveTenantTier($tenantId);
                return $this->fairUsePolicyService->getUnitPrice($metric, $tier);
            }
            catch (\Throwable) {
                // Fallback below.
            }
        }

        return self::FALLBACK_UNIT_PRICES[$metric] ?? 0.0;
    }

    /**
     * Resolves the tier key for a tenant.
     */
    protected function resolveTenantTier(int|string $tenantId): string {
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_subscription')) {
            try {
                $subscription = \Drupal::service('ecosistema_jaraba_core.tenant_subscription');
                if (method_exists($subscription, 'getTenantTier')) {
                    return $subscription->getTenantTier((string) $tenantId) ?: 'starter';
                }
            }
            catch (\Throwable) {
                // Fallback below.
            }
        }
        return 'starter';
    }

    /**
     * GAP-CURRENCY: Resolves currency for a specific tenant.
     *
     * @param int|string $tenantId
     *   The tenant ID.
     *
     * @return string
     *   ISO 4217 currency code.
     */
    protected function resolveTenantCurrency(int|string $tenantId): string {
        if (\Drupal::hasService('ecosistema_jaraba_core.currency')) {
            try {
                return \Drupal::service('ecosistema_jaraba_core.currency')->getTenantCurrency($tenantId);
            }
            catch (\Throwable) {
                // Fallback below.
            }
        }
        return 'EUR';
    }

}
