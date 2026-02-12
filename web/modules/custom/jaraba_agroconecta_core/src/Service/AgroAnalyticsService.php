<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Entity\AlertRuleAgro;
use Drupal\jaraba_agroconecta_core\Entity\AnalyticsDailyAgro;

/**
 * Servicio de analytics para AgroConecta.
 *
 * RESPONSABILIDADES:
 * - Agregación nocturna de métricas diarias (cron job).
 * - Dashboard KPIs con periodos comparativos.
 * - Rankings de productos y productores.
 * - Evaluación de reglas de alerta.
 * - Sparkline data para gráficas.
 */
class AgroAnalyticsService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    // ===================================================
    // Dashboard KPIs
    // ===================================================

    /**
     * Obtiene los KPIs del dashboard para un periodo.
     */
    public function getDashboardData(int $tenantId, string $period = '7d'): array
    {
        $endDate = new \DateTime();
        $startDate = $this->calculateStartDate($period);

        $metrics = $this->getMetricsForPeriod($tenantId, $startDate, $endDate);
        $previousStart = $this->calculatePreviousPeriodStart($startDate, $endDate);
        $previousMetrics = $this->getMetricsForPeriod($tenantId, $previousStart, $startDate);

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'kpis' => [
                'gmv' => $this->buildKpi('GMV', $metrics['gmv'], $previousMetrics['gmv'], '€'),
                'orders' => $this->buildKpi('Pedidos', $metrics['orders_count'], $previousMetrics['orders_count']),
                'aov' => $this->buildKpi('Ticket Medio', $metrics['aov'], $previousMetrics['aov'], '€'),
                'conversion_rate' => $this->buildKpi('Conversión', $metrics['conversion_rate'], $previousMetrics['conversion_rate'], '%'),
                'unique_buyers' => $this->buildKpi('Compradores', $metrics['unique_buyers'], $previousMetrics['unique_buyers']),
                'avg_rating' => $this->buildKpi('Rating', $metrics['avg_rating'], $previousMetrics['avg_rating'], '⭐'),
                'new_users' => $this->buildKpi('Nuevos Usuarios', $metrics['new_users'], $previousMetrics['new_users']),
                'active_producers' => $this->buildKpi('Productores Activos', $metrics['active_producers'], $previousMetrics['active_producers']),
            ],
            'sparklines' => [
                'gmv' => $this->getSparklineData($tenantId, 'gmv', $startDate, $endDate),
                'orders' => $this->getSparklineData($tenantId, 'orders_count', $startDate, $endDate),
            ],
        ];
    }

    // ===================================================
    // Rankings
    // ===================================================

    /**
     * Top productos por ventas.
     */
    public function getTopProducts(int $tenantId, int $limit = 10): array
    {
        // Query products con más ventas (basado en OrderItemAgro).
        $storage = $this->entityTypeManager->getStorage('order_item_agro');
        $query = $storage->getQuery()
            ->accessCheck(FALSE);

        // Agregar productos con mayor cantidad vendida.
        $ids = $query->range(0, $limit)->execute();
        if (empty($ids)) {
            return [];
        }

        $items = $storage->loadMultiple($ids);
        $productSales = [];

        foreach ($items as $item) {
            $productId = $item->get('product_id')->target_id ?? NULL;
            if (!$productId) {
                continue;
            }

            if (!isset($productSales[$productId])) {
                $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
                $productSales[$productId] = [
                    'product_id' => $productId,
                    'name' => $product ? $product->label() : 'Producto #' . $productId,
                    'total_sold' => 0,
                    'revenue' => 0,
                ];
            }

            $qty = (int) ($item->get('quantity')->value ?? 0);
            $price = (float) ($item->get('unit_price')->value ?? 0);
            $productSales[$productId]['total_sold'] += $qty;
            $productSales[$productId]['revenue'] += ($qty * $price);
        }

        usort($productSales, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_slice($productSales, 0, $limit);
    }

    /**
     * Top productores por GMV generado.
     */
    public function getTopProducers(int $tenantId, int $limit = 10): array
    {
        $storage = $this->entityTypeManager->getStorage('suborder_agro');
        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->range(0, 200)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $suborders = $storage->loadMultiple($ids);
        $producerGmv = [];

        foreach ($suborders as $suborder) {
            $producerId = $suborder->get('producer_id')->target_id ?? NULL;
            if (!$producerId) {
                continue;
            }

            if (!isset($producerGmv[$producerId])) {
                $producer = $this->entityTypeManager->getStorage('producer_profile')->load($producerId);
                $producerGmv[$producerId] = [
                    'producer_id' => $producerId,
                    'name' => $producer ? $producer->label() : 'Productor #' . $producerId,
                    'gmv' => 0,
                    'suborders' => 0,
                ];
            }

            $producerGmv[$producerId]['gmv'] += (float) ($suborder->get('subtotal')->value ?? 0);
            $producerGmv[$producerId]['suborders']++;
        }

        usort($producerGmv, fn($a, $b) => $b['gmv'] <=> $a['gmv']);
        return array_slice($producerGmv, 0, $limit);
    }

    // ===================================================
    // Agregación Diaria (Cron)
    // ===================================================

    /**
     * Agrega métricas del día anterior. Llamado por cron.
     */
    public function aggregateDaily(?string $date = NULL): ?array
    {
        $targetDate = $date ?? (new \DateTime('yesterday'))->format('Y-m-d');

        // Verificar si ya existe para evitar duplicados.
        $existing = $this->findDailyRecord(1, $targetDate);
        if ($existing) {
            return ['status' => 'already_exists', 'date' => $targetDate];
        }

        // Contar pedidos del día.
        $orderStorage = $this->entityTypeManager->getStorage('order_agro');
        $dayStart = strtotime($targetDate . ' 00:00:00');
        $dayEnd = strtotime($targetDate . ' 23:59:59');

        $orderIds = $orderStorage->getQuery()
            ->condition('created', [$dayStart, $dayEnd], 'BETWEEN')
            ->accessCheck(FALSE)
            ->execute();

        $ordersCount = count($orderIds);
        $gmv = 0;

        if (!empty($orderIds)) {
            $orders = $orderStorage->loadMultiple($orderIds);
            foreach ($orders as $order) {
                $gmv += (float) ($order->get('total')->value ?? 0);
            }
        }

        $aov = $ordersCount > 0 ? $gmv / $ordersCount : 0;

        // Crear registro.
        $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
        $record = $storage->create([
            'tenant_id' => 1,
            'date' => $targetDate,
            'gmv' => round($gmv, 2),
            'orders_count' => $ordersCount,
            'aov' => round($aov, 2),
            'uid' => 1,
        ]);
        $record->save();

        return [
            'status' => 'created',
            'date' => $targetDate,
            'gmv' => round($gmv, 2),
            'orders' => $ordersCount,
        ];
    }

    // ===================================================
    // Alertas
    // ===================================================

    /**
     * Evalúa todas las reglas de alerta activas.
     */
    public function evaluateAlerts(int $tenantId = 1): array
    {
        $storage = $this->entityTypeManager->getStorage('alert_rule_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $rules = $storage->loadMultiple($ids);
        $todayMetrics = $this->getTodayMetrics($tenantId);
        $avgMetrics = $this->getAverageMetrics($tenantId, 7);

        $triggered = [];

        foreach ($rules as $rule) {
            assert($rule instanceof AlertRuleAgro);
            $metric = $rule->getMetric();
            $condition = $rule->getCondition();
            $threshold = $rule->getThreshold();
            $value = $todayMetrics[$metric] ?? 0;

            $isTriggered = match ($condition) {
                'lt' => $value < $threshold,
                'lte' => $value <= $threshold,
                'gt' => $value > $threshold,
                'gte' => $value >= $threshold,
                'drop_pct' => $this->checkDropPercent($value, $avgMetrics[$metric] ?? 0, $threshold),
                default => FALSE,
            };

            if ($isTriggered) {
                // Actualizar contadores de la regla.
                $rule->set('last_triggered', date('Y-m-d\TH:i:s'));
                $triggerCount = (int) ($rule->get('trigger_count')->value ?? 0);
                $rule->set('trigger_count', $triggerCount + 1);
                $rule->save();

                $triggered[] = [
                    'rule_id' => (int) $rule->id(),
                    'name' => $rule->getName(),
                    'metric' => $metric,
                    'severity' => $rule->getSeverity(),
                    'current_value' => $value,
                    'threshold' => $threshold,
                    'message' => sprintf(
                        '%s: %s = %.2f (umbral: %.2f)',
                        $rule->getName(),
                        $metric,
                        $value,
                        $threshold
                    ),
                ];
            }
        }

        return $triggered;
    }

    /**
     * Obtiene las alertas activas recientes.
     */
    public function getActiveAlerts(int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('alert_rule_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->exists('last_triggered')
            ->sort('last_triggered', 'DESC')
            ->range(0, 20)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $rules = $storage->loadMultiple($ids);
        $alerts = [];

        foreach ($rules as $rule) {
            assert($rule instanceof AlertRuleAgro);
            $alerts[] = [
                'id' => (int) $rule->id(),
                'name' => $rule->getName(),
                'metric' => $rule->getMetric(),
                'severity' => $rule->getSeverity(),
                'threshold' => $rule->getThreshold(),
                'trigger_count' => (int) ($rule->get('trigger_count')->value ?? 0),
                'last_triggered' => $rule->get('last_triggered')->value,
            ];
        }

        return $alerts;
    }

    // ===================================================
    // Métodos internos
    // ===================================================

    protected function getMetricsForPeriod(int $tenantId, \DateTime $start, \DateTime $end): array
    {
        $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('date', $start->format('Y-m-d'), '>=')
            ->condition('date', $end->format('Y-m-d'), '<=')
            ->accessCheck(FALSE)
            ->execute();

        $defaults = [
            'gmv' => 0,
            'orders_count' => 0,
            'aov' => 0,
            'conversion_rate' => 0,
            'unique_buyers' => 0,
            'avg_rating' => 0,
            'new_users' => 0,
            'active_producers' => 0,
            'qr_scans' => 0,
        ];

        if (empty($ids)) {
            return $defaults;
        }

        $records = $storage->loadMultiple($ids);
        $days = count($records);

        foreach ($records as $r) {
            assert($r instanceof AnalyticsDailyAgro);
            $defaults['gmv'] += $r->getGmv();
            $defaults['orders_count'] += $r->getOrdersCount();
            $defaults['unique_buyers'] += (int) ($r->get('unique_buyers')->value ?? 0);
            $defaults['new_users'] += (int) ($r->get('new_users')->value ?? 0);
            $defaults['active_producers'] += (int) ($r->get('active_producers')->value ?? 0);
            $defaults['qr_scans'] += (int) ($r->get('qr_scans')->value ?? 0);
            $defaults['avg_rating'] += (float) ($r->get('avg_rating')->value ?? 0);
            $defaults['conversion_rate'] += $r->getConversionRate();
        }

        // Promedios.
        if ($days > 0) {
            $defaults['aov'] = $defaults['orders_count'] > 0 ? $defaults['gmv'] / $defaults['orders_count'] : 0;
            $defaults['avg_rating'] /= $days;
            $defaults['conversion_rate'] /= $days;
        }

        return $defaults;
    }

    protected function getSparklineData(int $tenantId, string $field, \DateTime $start, \DateTime $end): array
    {
        $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('date', $start->format('Y-m-d'), '>=')
            ->condition('date', $end->format('Y-m-d'), '<=')
            ->sort('date', 'ASC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $records = $storage->loadMultiple($ids);
        $data = [];
        foreach ($records as $r) {
            $data[] = [
                'date' => $r->get('date')->value,
                'value' => (float) ($r->get($field)->value ?? 0),
            ];
        }
        return $data;
    }

    protected function getTodayMetrics(int $tenantId): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        return $this->getMetricsForPeriod($tenantId, $today, $tomorrow);
    }

    protected function getAverageMetrics(int $tenantId, int $days): array
    {
        $end = new \DateTime('yesterday');
        $start = (clone $end)->modify("-{$days} days");
        return $this->getMetricsForPeriod($tenantId, $start, $end);
    }

    protected function buildKpi(string $label, float $current, float $previous, string $suffix = ''): array
    {
        $change = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : 0;
        return [
            'label' => $label,
            'value' => round($current, 2),
            'previous' => round($previous, 2),
            'change_pct' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            'suffix' => $suffix,
        ];
    }

    protected function checkDropPercent(float $current, float $average, float $thresholdPct): bool
    {
        if ($average <= 0) {
            return FALSE;
        }
        $dropPct = (($average - $current) / $average) * 100;
        return $dropPct >= $thresholdPct;
    }

    protected function calculateStartDate(string $period): \DateTime
    {
        return match ($period) {
            '1d' => new \DateTime('yesterday'),
            '7d' => new \DateTime('-7 days'),
            '30d' => new \DateTime('-30 days'),
            '90d' => new \DateTime('-90 days'),
            '365d' => new \DateTime('-365 days'),
            default => new \DateTime('-7 days'),
        };
    }

    protected function calculatePreviousPeriodStart(\DateTime $start, \DateTime $end): \DateTime
    {
        $diff = $start->diff($end);
        return (clone $start)->modify("-{$diff->days} days");
    }

    protected function findDailyRecord(int $tenantId, string $date): ?AnalyticsDailyAgro
    {
        $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('date', $date)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        $record = $storage->load(reset($ids));
        return $record instanceof AnalyticsDailyAgro ? $record : NULL;
    }
}
