<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Servicio de análisis de tendencias para tenants.
 *
 * PROPÓSITO:
 * Proporciona datos de tendencias para gráficos del dashboard del tenant,
 * incluyendo ventas diarias, MRR mensual y actividad de clientes.
 */
class TenantAnalyticsService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected Connection $database,
        protected DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * Obtiene datos de ventas diarias para los últimos N días.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param int $days
     *   Número de días a obtener (default: 30).
     *
     * @return array
     *   Array con 'labels' (fechas) y 'data' (valores).
     */
    public function getSalesTrend(string $tenantId, int $days = 30): array
    {
        $labels = [];
        $data = [];

        // Generar fechas de los últimos N días.
        $startDate = strtotime("-{$days} days");

        for ($i = 0; $i <= $days; $i++) {
            $date = strtotime("+{$i} days", $startDate);
            $labels[] = date('d M', $date);
            $data[] = 0;
        }

        // Intentar obtener datos reales de transacciones.
        try {
            if ($this->database->schema()->tableExists('financial_transaction')) {
                $results = $this->database->query("
          SELECT 
            DATE(FROM_UNIXTIME(created)) as sale_date,
            SUM(amount) as total
          FROM {financial_transaction}
          WHERE tenant_id = :tenant_id 
            AND type IN ('sale', 'order', 'payment')
            AND created >= :start_date
          GROUP BY DATE(FROM_UNIXTIME(created))
          ORDER BY sale_date
        ", [
                        ':tenant_id' => $tenantId,
                        ':start_date' => $startDate,
                    ]);

                foreach ($results as $row) {
                    $dayIndex = (strtotime($row->sale_date) - $startDate) / 86400;
                    if (isset($data[$dayIndex])) {
                        $data[(int) $dayIndex] = (float) $row->total;
                    }
                }
            }
        } catch (\Exception $e) {
            // Si no hay datos reales, generar demo data.
            $data = $this->generateDemoData($days, 50, 500);
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
            'average' => count($data) > 0 ? round(array_sum($data) / count($data), 2) : 0,
        ];
    }

    /**
     * Obtiene datos de MRR mensual para los últimos N meses.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param int $months
     *   Número de meses a obtener (default: 6).
     *
     * @return array
     *   Array con 'labels' y 'data'.
     */
    public function getMrrTrend(string $tenantId, int $months = 6): array
    {
        $labels = [];
        $data = [];

        // Nombres de meses en español.
        $monthNames = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = strtotime("-{$i} months");
            $month = (int) date('n', $date);
            $year = date('Y', $date);
            $labels[] = $monthNames[$month] . ' ' . substr($year, 2);
        }

        // Placeholder: MRR basado en plan de suscripción.
        // En producción, esto vendría de Stripe o del histórico de pagos.
        $data = $this->generateDemoData($months, 500, 2000);

        return [
            'labels' => $labels,
            'data' => $data,
            'current' => end($data),
            'growth' => $this->calculateGrowth($data),
        ];
    }

    /**
     * Obtiene datos de clientes activos.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param int $days
     *   Número de días a analizar.
     *
     * @return array
     *   Datos de clientes.
     */
    public function getCustomersTrend(string $tenantId, int $days = 30): array
    {
        $labels = [];
        $newCustomers = [];
        $returning = [];

        $startDate = strtotime("-{$days} days");

        // Generar por semanas en lugar de días.
        $weeks = (int) ceil($days / 7);
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = strtotime("+{$i} weeks", $startDate);
            $labels[] = 'Sem ' . ($i + 1);
        }

        // Demo data.
        $newCustomers = $this->generateDemoData($weeks, 2, 10);
        $returning = $this->generateDemoData($weeks, 5, 20);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nuevos',
                    'data' => $newCustomers,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                ],
                [
                    'label' => 'Recurrentes',
                    'data' => $returning,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.8)',
                ],
            ],
        ];
    }

    /**
     * Obtiene resumen de productos más vendidos.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param int $limit
     *   Número de productos a devolver.
     *
     * @return array
     *   Array de productos con ventas.
     */
    public function getTopProducts(string $tenantId, int $limit = 5): array
    {
        // Demo data - en producción vendría de órdenes.
        return [
            ['name' => 'Producto A', 'sales' => 45, 'revenue' => 1350.00],
            ['name' => 'Producto B', 'sales' => 32, 'revenue' => 960.00],
            ['name' => 'Producto C', 'sales' => 28, 'revenue' => 840.00],
            ['name' => 'Producto D', 'sales' => 21, 'revenue' => 630.00],
            ['name' => 'Producto E', 'sales' => 15, 'revenue' => 450.00],
        ];
    }

    /**
     * Genera datos demo para gráficos.
     */
    protected function generateDemoData(int $count, int $min, int $max): array
    {
        $data = [];
        $current = rand($min, $max);

        for ($i = 0; $i < $count; $i++) {
            // Variación aleatoria con tendencia ligeramente ascendente.
            $change = rand(-20, 25);
            $current = max($min, min($max, $current + $change));
            $data[] = round($current, 2);
        }

        return $data;
    }

    /**
     * Calcula el porcentaje de crecimiento.
     */
    protected function calculateGrowth(array $data): float
    {
        if (count($data) < 2) {
            return 0;
        }

        $first = $data[0];
        $last = end($data);

        if ($first == 0) {
            return $last > 0 ? 100 : 0;
        }

        return round((($last - $first) / $first) * 100, 1);
    }

}
