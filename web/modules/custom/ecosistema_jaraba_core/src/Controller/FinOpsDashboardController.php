<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\FinOpsTrackingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the FinOps (Cost Optimization) Dashboard.
 *
 * PROPÓSITO:
 * Proporciona visualización de métricas de coste por tenant:
 * - Uso de recursos por tenant (storage real, API requests trackeados)
 * - Proyecciones mensuales de coste
 * - Alertas de sobregasto
 * - Recomendaciones de optimización
 *
 * FUENTES DE DATOS:
 * - Storage: Calculado desde archivos y nodos reales
 * - API Requests: Trackeados via RequestTrackingSubscriber
 * - CPU: Estimado desde actividad
 */
class FinOpsDashboardController extends ControllerBase
{

    /**
     * Servicio de tracking FinOps.
     */
    protected ?FinOpsTrackingService $finopsTracking = NULL;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);

        try {
            $instance->finopsTracking = $container->get('ecosistema_jaraba_core.finops_tracking');
        } catch (\Exception $e) {
            // Service may not be available yet
        }

        return $instance;
    }

    /**
     * Renders the FinOps dashboard page.
     *
     * @return array
     *   A render array for the FinOps dashboard.
     */
    public function dashboard()
    {
        $finops_data = $this->getFinOpsData();

        return [
            '#theme' => 'finops_dashboard',
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/finops-dashboard',
                ],
            ],
            '#tenants' => $finops_data['tenants'],
            '#totals' => $finops_data['totals'],
            '#projections' => $finops_data['projections'],
            '#alerts' => $finops_data['alerts'],
            '#recommendations' => $finops_data['recommendations'],
            '#help_info' => $this->getHelpInfo(),
            '#data_sources' => $finops_data['data_sources'],
            '#last_updated' => date('Y-m-d H:i:s'),
            '#cache' => [
                'max-age' => 300, // Cache for 5 minutes
            ],
        ];
    }

    /**
     * API endpoint for FinOps data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with FinOps metrics.
     */
    public function finopsApi()
    {
        $finops_data = $this->getFinOpsData();
        return new JsonResponse($finops_data);
    }

    /**
     * Gets FinOps data including costs and usage metrics.
     *
     * @return array
     *   Array with tenants, totals, projections, and alerts.
     */
    protected function getFinOpsData(): array
    {
        $tenants = $this->getTenantUsage();
        $totals = $this->calculateTotals($tenants);
        $projections = $this->calculateProjections($totals);
        $alerts = $this->checkCostAlerts($tenants, $projections);
        $recommendations = $this->getOptimizationRecommendations($tenants);
        $data_sources = $this->getDataSourceInfo($tenants);

        return [
            'tenants' => $tenants,
            'totals' => $totals,
            'projections' => $projections,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'data_sources' => $data_sources,
            'timestamp' => time(),
        ];
    }

    /**
     * Get resource usage per tenant.
     */
    protected function getTenantUsage(): array
    {
        $tenants = [];

        try {
            $tenant_storage = \Drupal::entityTypeManager()->getStorage('tenant');
            $tenant_entities = $tenant_storage->loadMultiple();

            foreach ($tenant_entities as $tenant) {
                $tenant_id = $tenant->id();
                $tenant_name = $tenant->label();

                // Calculate storage usage (content nodes, files, etc.)
                $storage_mb = $this->calculateTenantStorage($tenant_id);

                // Calculate API requests (from logs or state)
                $api_requests = $this->getTenantApiRequests($tenant_id);

                // Calculate estimated CPU time
                $cpu_hours = $this->estimateCpuUsage($api_requests, $storage_mb);

                // Get plan tier for pricing
                $plan = $tenant->get('plan')->entity;
                $tier = $plan ? $plan->id() : 'basic';

                // Calculate costs
                $storage_cost = $storage_mb * 0.02; // €0.02 per MB
                $api_cost = $api_requests * 0.001; // €0.001 per request
                $cpu_cost = $cpu_hours * 0.10; // €0.10 per CPU hour
                $total_cost = $storage_cost + $api_cost + $cpu_cost;

                $tenants[] = [
                    'id' => $tenant_id,
                    'name' => $tenant_name,
                    'tier' => $tier,
                    'storage_mb' => round($storage_mb, 2),
                    'api_requests' => $api_requests,
                    'cpu_hours' => round($cpu_hours, 2),
                    'costs' => [
                        'storage' => round($storage_cost, 2),
                        'api' => round($api_cost, 2),
                        'cpu' => round($cpu_cost, 2),
                        'total' => round($total_cost, 2),
                    ],
                    'status' => $this->getTenantCostStatus($total_cost, $tier),
                ];
            }
        } catch (\Exception $e) {
            // Return sample data if tenant entity not available
            $tenants = $this->getSampleTenantData();
        }

        // Sort by total cost descending
        usort($tenants, fn($a, $b) => $b['costs']['total'] <=> $a['costs']['total']);

        return $tenants;
    }

    /**
     * Calculate storage usage for a tenant.
     * 
     * FUENTE: Datos reales si FinOpsTrackingService disponible,
     * estimación basada en nodos si no.
     */
    protected function calculateTenantStorage(string $tenant_id): float
    {
        // Intentar usar servicio de tracking real
        if ($this->finopsTracking) {
            $storage = $this->finopsTracking->getStorageUsage($tenant_id);
            if ($storage > 0) {
                return $storage;
            }
        }

        // Fallback: Estimar basado en content count
        try {
            $node_count = \Drupal::entityQuery('node')
                ->accessCheck(FALSE)
                ->count()
                ->execute();

            // Estimar 0.5MB por nodo (promedio)
            // NOTA: Esta es una estimación, no datos reales por tenant
            return max(10, $node_count * 0.5 / 3); // Dividir entre tenants estimados
        } catch (\Exception $e) {
            return 50.0; // Valor por defecto
        }
    }

    /**
     * Get API request count for a tenant.
     * 
     * FUENTE: Datos reales de tabla finops_usage_log si disponible,
     * estimación desde State API si no.
     */
    protected function getTenantApiRequests(string $tenant_id): int
    {
        // Intentar usar servicio de tracking real
        if ($this->finopsTracking) {
            // Obtener requests del último mes
            $since = strtotime('-30 days');
            $requests = $this->finopsTracking->getApiRequestCount($tenant_id, $since);
            if ($requests > 0) {
                return $requests;
            }
        }

        // Fallback: State API (puede ser real si hay datos previos)
        $state = \Drupal::state();
        $key = "finops_api_count_{$tenant_id}";
        $requests = $state->get($key, 0);

        // Si no hay datos, estimar basado en tenant existente
        if ($requests === 0) {
            // NOTA: Esto es estimación, se irá reemplazando con datos reales
            $requests = 500; // Base inicial
        }

        return $requests;
    }

    /**
     * Estimate CPU usage based on activity.
     */
    protected function estimateCpuUsage(int $api_requests, float $storage_mb): float
    {
        // Estimate: 0.001 CPU hours per request + 0.0001 per MB storage
        return ($api_requests * 0.001) + ($storage_mb * 0.0001);
    }

    /**
     * Get cost status (normal, warning, critical).
     */
    protected function getTenantCostStatus(float $cost, string $tier): string
    {
        $thresholds = [
            'basic' => ['warning' => 50, 'critical' => 100],
            'professional' => ['warning' => 200, 'critical' => 500],
            'enterprise' => ['warning' => 1000, 'critical' => 2500],
        ];

        $limits = $thresholds[$tier] ?? $thresholds['basic'];

        if ($cost >= $limits['critical']) {
            return 'critical';
        } elseif ($cost >= $limits['warning']) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * Calculate totals across all tenants.
     */
    protected function calculateTotals(array $tenants): array
    {
        $totals = [
            'storage_mb' => 0,
            'api_requests' => 0,
            'cpu_hours' => 0,
            'cost_storage' => 0,
            'cost_api' => 0,
            'cost_cpu' => 0,
            'cost_total' => 0,
            'tenant_count' => count($tenants),
        ];

        foreach ($tenants as $tenant) {
            $totals['storage_mb'] += $tenant['storage_mb'];
            $totals['api_requests'] += $tenant['api_requests'];
            $totals['cpu_hours'] += $tenant['cpu_hours'];
            $totals['cost_storage'] += $tenant['costs']['storage'];
            $totals['cost_api'] += $tenant['costs']['api'];
            $totals['cost_cpu'] += $tenant['costs']['cpu'];
            $totals['cost_total'] += $tenant['costs']['total'];
        }

        // Round all values
        foreach ($totals as $key => $value) {
            if ($key !== 'tenant_count') {
                $totals[$key] = round($value, 2);
            }
        }

        return $totals;
    }

    /**
     * Calculate cost projections.
     */
    protected function calculateProjections(array $totals): array
    {
        $daily_cost = $totals['cost_total'];
        $days_in_month = date('t');
        $current_day = date('j');

        return [
            'daily_average' => round($daily_cost / max($current_day, 1), 2),
            'monthly_projected' => round(($daily_cost / max($current_day, 1)) * $days_in_month, 2),
            'monthly_budget' => 5000.00, // Configurable
            'budget_usage_percent' => round((($daily_cost / max($current_day, 1)) * $days_in_month / 5000) * 100, 1),
            'trend' => $this->calculateCostTrend(),
        ];
    }

    /**
     * Calculate cost trend (up, down, stable).
     */
    protected function calculateCostTrend(): string
    {
        $state = \Drupal::state();
        $previous = $state->get('finops_previous_daily_cost', 0);
        $current = $state->get('finops_current_daily_cost', 0);

        if ($previous === 0) {
            return 'stable';
        }

        $change = (($current - $previous) / $previous) * 100;

        if ($change > 10) {
            return 'up';
        } elseif ($change < -10) {
            return 'down';
        }
        return 'stable';
    }

    /**
     * Check for cost alerts.
     */
    protected function checkCostAlerts(array $tenants, array $projections): array
    {
        $alerts = [];

        // Budget alert
        if ($projections['budget_usage_percent'] > 90) {
            $alerts[] = [
                'type' => 'critical',
                'title' => $this->t('Budget Alert'),
                'message' => $this->t('Projected costs at @percent% of monthly budget.', [
                    '@percent' => $projections['budget_usage_percent'],
                ]),
            ];
        } elseif ($projections['budget_usage_percent'] > 75) {
            $alerts[] = [
                'type' => 'warning',
                'title' => $this->t('Budget Warning'),
                'message' => $this->t('Approaching @percent% of monthly budget.', [
                    '@percent' => $projections['budget_usage_percent'],
                ]),
            ];
        }

        // High-cost tenant alerts
        foreach ($tenants as $tenant) {
            if ($tenant['status'] === 'critical') {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => $this->t('High Cost Tenant'),
                    'message' => $this->t('@tenant is exceeding cost thresholds (€@cost).', [
                        '@tenant' => $tenant['name'],
                        '@cost' => $tenant['costs']['total'],
                    ]),
                ];
            }
        }

        // Trend alert
        if ($projections['trend'] === 'up') {
            $alerts[] = [
                'type' => 'warning',
                'title' => $this->t('Cost Trend'),
                'message' => $this->t('Costs are trending upward. Review usage patterns.'),
            ];
        }

        return $alerts;
    }

    /**
     * Get optimization recommendations.
     */
    protected function getOptimizationRecommendations(array $tenants): array
    {
        $recommendations = [];

        foreach ($tenants as $tenant) {
            // High storage usage
            if ($tenant['storage_mb'] > 200) {
                $recommendations[] = [
                    'tenant' => $tenant['name'],
                    'type' => 'storage',
                    'title' => $this->t('Optimize Storage'),
                    'message' => $this->t('Consider archiving old content for @tenant.', [
                        '@tenant' => $tenant['name'],
                    ]),
                    'potential_savings' => round($tenant['costs']['storage'] * 0.3, 2),
                ];
            }

            // High API usage
            if ($tenant['api_requests'] > 5000) {
                $recommendations[] = [
                    'tenant' => $tenant['name'],
                    'type' => 'api',
                    'title' => $this->t('Implement Caching'),
                    'message' => $this->t('Add caching layer for @tenant API calls.', [
                        '@tenant' => $tenant['name'],
                    ]),
                    'potential_savings' => round($tenant['costs']['api'] * 0.5, 2),
                ];
            }
        }

        // Calculate total potential savings
        $total_savings = array_sum(array_column($recommendations, 'potential_savings'));

        if (!empty($recommendations)) {
            array_unshift($recommendations, [
                'type' => 'summary',
                'title' => $this->t('Total Potential Savings'),
                'message' => $this->t('€@savings/month if all recommendations applied.', [
                    '@savings' => round($total_savings, 2),
                ]),
                'potential_savings' => $total_savings,
            ]);
        }

        return $recommendations;
    }

    /**
     * Sample tenant data for demo purposes.
     */
    protected function getSampleTenantData(): array
    {
        return [
            [
                'id' => 'aceites-sur',
                'name' => 'Aceites del Sur',
                'tier' => 'professional',
                'storage_mb' => 256.50,
                'api_requests' => 4500,
                'cpu_hours' => 5.2,
                'costs' => [
                    'storage' => 5.13,
                    'api' => 4.50,
                    'cpu' => 0.52,
                    'total' => 10.15,
                ],
                'status' => 'normal',
            ],
            [
                'id' => 'olivos-premium',
                'name' => 'Olivos Premium',
                'tier' => 'enterprise',
                'storage_mb' => 512.00,
                'api_requests' => 12000,
                'cpu_hours' => 15.8,
                'costs' => [
                    'storage' => 10.24,
                    'api' => 12.00,
                    'cpu' => 1.58,
                    'total' => 23.82,
                ],
                'status' => 'normal',
            ],
            [
                'id' => 'cooperativa-norte',
                'name' => 'Cooperativa Norte',
                'tier' => 'basic',
                'storage_mb' => 128.00,
                'api_requests' => 8500,
                'cpu_hours' => 9.0,
                'costs' => [
                    'storage' => 2.56,
                    'api' => 8.50,
                    'cpu' => 0.90,
                    'total' => 11.96,
                ],
                'status' => 'warning',
            ],
        ];
    }

    /**
     * Obtiene información de ayuda para el dashboard.
     *
     * @return array
     *   Array con información de ayuda traducible.
     */
    protected function getHelpInfo(): array
    {
        return [
            'title' => $this->t('About FinOps Dashboard'),
            'description' => $this->t('This dashboard shows cost metrics and resource usage per tenant.'),
            'metrics' => [
                [
                    'name' => $this->t('Storage (MB)'),
                    'source' => $this->t('File system + content estimation'),
                    'realtime' => FALSE,
                ],
                [
                    'name' => $this->t('API Requests'),
                    'source' => $this->t('Tracked automatically via RequestTrackingSubscriber'),
                    'realtime' => TRUE,
                ],
                [
                    'name' => $this->t('CPU Hours'),
                    'source' => $this->t('Estimated from API and storage activity'),
                    'realtime' => FALSE,
                ],
                [
                    'name' => $this->t('Costs'),
                    'source' => $this->t('Calculated from usage × unit prices'),
                    'realtime' => FALSE,
                ],
            ],
            'pricing' => [
                'storage' => $this->t('€0.02 per MB'),
                'api' => $this->t('€0.001 per request'),
                'cpu' => $this->t('€0.10 per CPU hour'),
            ],
            'note' => $this->t('Data is collected automatically. API requests are tracked in real-time. Storage is recalculated periodically. Run "drush updb" to create the tracking table.'),
        ];
    }

    /**
     * Obtiene información sobre las fuentes de datos.
     *
     * @param array $tenants
     *   Lista de tenants.
     *
     * @return array
     *   Información sobre qué datos son reales vs estimados.
     */
    protected function getDataSourceInfo(array $tenants): array
    {
        $has_real_data = FALSE;
        $has_tracking_table = FALSE;

        // Verificar si existe la tabla de tracking
        try {
            $has_tracking_table = \Drupal::database()->schema()->tableExists('finops_usage_log');
        } catch (\Exception $e) {
            // Ignore
        }

        // Verificar si hay datos reales en la tabla
        if ($has_tracking_table) {
            try {
                $count = \Drupal::database()->select('finops_usage_log', 'f')
                    ->countQuery()
                    ->execute()
                    ->fetchField();
                $has_real_data = $count > 0;
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return [
            'tracking_enabled' => $has_tracking_table,
            'has_real_data' => $has_real_data,
            'sources' => [
                'storage' => [
                    'type' => $this->t('Estimated'),
                    'description' => $this->t('Based on file count and content nodes'),
                ],
                'api_requests' => [
                    'type' => $has_real_data ? $this->t('Real') : $this->t('Estimated'),
                    'description' => $has_real_data
                        ? $this->t('Tracked from actual HTTP requests')
                        : $this->t('Will become real once tracking starts'),
                ],
                'cpu_hours' => [
                    'type' => $this->t('Estimated'),
                    'description' => $this->t('Calculated from activity patterns'),
                ],
                'costs' => [
                    'type' => $this->t('Calculated'),
                    'description' => $this->t('Usage × unit price (configurable)'),
                ],
            ],
            'setup_required' => !$has_tracking_table,
            'setup_command' => 'lando drush updb -y && lando drush cr',
        ];
    }

}
