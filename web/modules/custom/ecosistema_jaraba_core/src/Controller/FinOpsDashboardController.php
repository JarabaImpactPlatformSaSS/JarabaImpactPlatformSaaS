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
                    'core/drupal.dialog.ajax',
                ],
            ],
            '#tenants' => $finops_data['tenants'],
            '#totals' => $finops_data['totals'],
            '#projections' => $finops_data['projections'],
            '#alerts' => $finops_data['alerts'],
            '#recommendations' => $finops_data['recommendations'],
            '#help_info' => $this->getHelpInfo(),
            '#data_sources' => $finops_data['data_sources'],
            '#revenue' => $finops_data['revenue'],
            '#net_results' => $finops_data['net_results'],
            '#feature_costs' => $finops_data['feature_costs'],
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

        // Datos de ingresos y resultados netos
        $revenue = $this->getRevenueData($tenants);
        $net_results = $this->calculateNetResults($totals, $revenue);

        // Datos de costes por Feature
        $feature_costs = $this->getFeatureCostsData();

        return [
            'tenants' => $tenants,
            'totals' => $totals,
            'projections' => $projections,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'data_sources' => $data_sources,
            'revenue' => $revenue,
            'net_results' => $net_results,
            'feature_costs' => $feature_costs,
            'timestamp' => time(),
        ];
    }

    /**
     * Get resource usage per tenant.
     * 
     * IMPORTANTE: Solo devuelve tenants REALES de la base de datos.
     * No usa datos ficticios - si no hay tenants, devuelve array vacío.
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

                // Get plan tier for pricing (robusto - no falla si campo no existe)
                $tier = 'basic';
                try {
                    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
                        $plan = $tenant->get('plan')->entity;
                        if ($plan) {
                            $tier = $plan->id() ?: 'basic';
                        }
                    }
                } catch (\Exception $e) {
                    // Plan no disponible, usar basic
                }

                // Calculate costs from config (NOT hardcoded)
                $config = $this->getFinOpsConfig();
                $storage_cost = $storage_mb * $config['price_storage_mb'];
                $api_cost = $api_requests * $config['price_api_request'];
                $cpu_cost = $cpu_hours * $config['price_cpu_hour'];
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
            // Log error pero NO devolver datos ficticios
            \Drupal::logger('ecosistema_jaraba_core')->warning(
                'FinOps: Error loading tenants: @error',
                ['@error' => $e->getMessage()]
            );
            // Devolver array vacío - el template mostrará mensaje apropiado
            return [];
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
        // Leer thresholds desde config (NOT hardcoded)
        $config = $this->getFinOpsConfig();

        $thresholds = [
            'basic' => [
                'warning' => $config['tier_limits']['basic']['warning'],
                'critical' => $config['tier_limits']['basic']['critical'],
            ],
            'professional' => [
                'warning' => $config['tier_limits']['professional']['warning'],
                'critical' => $config['tier_limits']['professional']['critical'],
            ],
            'enterprise' => [
                'warning' => $config['tier_limits']['enterprise']['warning'],
                'critical' => $config['tier_limits']['enterprise']['critical'],
            ],
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
        $config = $this->getFinOpsConfig();
        $daily_cost = $totals['cost_total'];
        $days_in_month = date('t');
        $current_day = date('j');
        $monthly_budget = $config['monthly_budget'];

        $monthly_projected = round(($daily_cost / max($current_day, 1)) * $days_in_month, 2);

        return [
            'daily_average' => round($daily_cost / max($current_day, 1), 2),
            'monthly_projected' => $monthly_projected,
            'monthly_budget' => $monthly_budget,
            'budget_usage_percent' => round(($monthly_projected / $monthly_budget) * 100, 1),
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
        $config = $this->getFinOpsConfig();
        $alerts = [];

        // Budget alert - usar thresholds desde config
        if ($projections['budget_usage_percent'] > $config['critical_threshold']) {
            $alerts[] = [
                'type' => 'critical',
                'title' => $this->t('Budget Alert'),
                'message' => $this->t('Projected costs at @percent% of monthly budget.', [
                    '@percent' => $projections['budget_usage_percent'],
                ]),
            ];
        } elseif ($projections['budget_usage_percent'] > $config['warning_threshold']) {
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
     * Obtiene datos de ingresos por suscripciones.
     *
     * Calcula MRR (Monthly Recurring Revenue) y ARR (Annual Recurring Revenue)
     * basándose en los planes activos de cada tenant.
     *
     * @param array $tenants
     *   Lista de tenants con sus datos.
     *
     * @return array
     *   Datos de ingresos: MRR, ARR, por tier, proyección.
     */
    protected function getRevenueData(array $tenants): array
    {
        $mrr = 0;
        $revenue_by_tier = [
            'basic' => ['count' => 0, 'monthly' => 0],
            'professional' => ['count' => 0, 'monthly' => 0],
            'enterprise' => ['count' => 0, 'monthly' => 0],
        ];

        try {
            $tenant_storage = \Drupal::entityTypeManager()->getStorage('tenant');
            $tenant_entities = $tenant_storage->loadMultiple();

            foreach ($tenant_entities as $tenant) {
                // Obtener plan del tenant
                $plan = NULL;
                try {
                    if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
                        $plan = $tenant->get('plan')->entity;
                    }
                } catch (\Exception $e) {
                    // Plan no disponible
                }

                if ($plan) {
                    $monthly_price = $plan->getPriceMonthly();
                    $tier = $plan->id() ?: 'basic';

                    $mrr += $monthly_price;

                    if (isset($revenue_by_tier[$tier])) {
                        $revenue_by_tier[$tier]['count']++;
                        $revenue_by_tier[$tier]['monthly'] += $monthly_price;
                    }
                }
            }
        } catch (\Exception $e) {
            \Drupal::logger('ecosistema_jaraba_core')->warning(
                'FinOps: Error calculating revenue: @error',
                ['@error' => $e->getMessage()]
            );
        }

        $arr = $mrr * 12;
        $days_in_month = (int) date('t');
        $current_day = (int) date('j');
        $monthly_projected = $current_day > 0 ? ($mrr / $current_day) * $days_in_month : $mrr;

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'monthly_projected' => round($monthly_projected, 2),
            'by_tier' => $revenue_by_tier,
            'active_subscriptions' => array_sum(array_column($revenue_by_tier, 'count')),
        ];
    }

    /**
     * Calcula resultados netos (P&L).
     *
     * @param array $totals
     *   Totales de costes.
     * @param array $revenue
     *   Datos de ingresos.
     *
     * @return array
     *   Resultados netos: actual, proyectado, margen.
     */
    protected function calculateNetResults(array $totals, array $revenue): array
    {
        $current_revenue = $revenue['mrr'];
        $current_costs = $totals['cost_total'];
        $net_current = $current_revenue - $current_costs;

        $projected_revenue = $revenue['monthly_projected'];
        $projected_costs = $totals['cost_total']; // Asumir costes similares
        $net_projected = $projected_revenue - $projected_costs;

        // Margen de beneficio
        $margin_current = $current_revenue > 0
            ? round(($net_current / $current_revenue) * 100, 1)
            : 0;
        $margin_projected = $projected_revenue > 0
            ? round(($net_projected / $projected_revenue) * 100, 1)
            : 0;

        return [
            'revenue_current' => round($current_revenue, 2),
            'costs_current' => round($current_costs, 2),
            'net_current' => round($net_current, 2),
            'margin_current' => $margin_current,
            'revenue_projected' => round($projected_revenue, 2),
            'costs_projected' => round($projected_costs, 2),
            'net_projected' => round($net_projected, 2),
            'margin_projected' => $margin_projected,
            'status' => $net_current >= 0 ? 'profitable' : 'loss',
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
        $config = $this->getFinOpsConfig();

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
                'storage' => $this->t('€@price per MB', ['@price' => $config['price_storage_mb']]),
                'api' => $this->t('€@price per request', ['@price' => $config['price_api_request']]),
                'cpu' => $this->t('€@price per CPU hour', ['@price' => $config['price_cpu_hour']]),
            ],
            'note' => $this->t('Data is collected automatically. API requests are tracked in real-time. Storage is recalculated periodically. Run "drush updb" to create the tracking table.'),
            'settings_url' => '/admin/config/finops',
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

    /**
     * Obtiene la configuración de FinOps desde Config API.
     *
     * Lee los precios unitarios y umbrales desde la configuración,
     * con valores por defecto si no están configurados.
     *
     * @return array
     *   Array con todos los valores de configuración.
     */
    protected function getFinOpsConfig(): array
    {
        static $config_cache = NULL;

        if ($config_cache !== NULL) {
            return $config_cache;
        }

        $config = \Drupal::config('ecosistema_jaraba_core.finops');

        $config_cache = [
            // Precios unitarios
            'price_storage_mb' => (float) ($config->get('price_storage_mb') ?: 0.02),
            'price_api_request' => (float) ($config->get('price_api_request') ?: 0.001),
            'price_cpu_hour' => (float) ($config->get('price_cpu_hour') ?: 0.10),

            // Presupuesto
            'monthly_budget' => (float) ($config->get('monthly_budget') ?: 5000),
            'warning_threshold' => (int) ($config->get('warning_threshold') ?: 75),
            'critical_threshold' => (int) ($config->get('critical_threshold') ?: 90),

            // Límites por tier
            'tier_limits' => [
                'basic' => [
                    'warning' => (float) ($config->get('tier_limits.basic.warning') ?: 50),
                    'critical' => (float) ($config->get('tier_limits.basic.critical') ?: 100),
                ],
                'professional' => [
                    'warning' => (float) ($config->get('tier_limits.professional.warning') ?: 200),
                    'critical' => (float) ($config->get('tier_limits.professional.critical') ?: 500),
                ],
                'enterprise' => [
                    'warning' => (float) ($config->get('tier_limits.enterprise.warning') ?: 1000),
                    'critical' => (float) ($config->get('tier_limits.enterprise.critical') ?: 2500),
                ],
            ],
        ];

        return $config_cache;
    }

    /**
     * Obtiene datos de costes por Feature.
     *
     * Calcula los costes asociados a cada Feature habilitada,
     * basándose en los campos FinOps configurados en la entidad Feature.
     *
     * @return array
     *   Datos de features: por categoría, totales, detalles.
     */
    protected function getFeatureCostsData(): array
    {
        $features = [];
        $by_category = [
            'compute' => ['count' => 0, 'base_cost' => 0, 'label' => $this->t('Compute')],
            'storage' => ['count' => 0, 'base_cost' => 0, 'label' => $this->t('Storage')],
            'ai' => ['count' => 0, 'base_cost' => 0, 'label' => $this->t('AI')],
            'api' => ['count' => 0, 'base_cost' => 0, 'label' => $this->t('API')],
            'bandwidth' => ['count' => 0, 'base_cost' => 0, 'label' => $this->t('Bandwidth')],
        ];
        $total_base_cost = 0;
        $total_unit_cost_potential = 0;

        try {
            $feature_storage = \Drupal::entityTypeManager()->getStorage('feature');
            $feature_entities = $feature_storage->loadMultiple();

            foreach ($feature_entities as $feature) {
                if (!$feature->status()) {
                    continue; // Solo features habilitadas
                }

                $base_cost = $feature->getBaseCostMonthly();
                $unit_cost = $feature->getUnitCost();
                $category = $feature->getCostCategory();
                $usage_metric = $feature->getUsageMetric();

                // Saltar features sin costes configurados
                if ($base_cost <= 0 && $unit_cost <= 0) {
                    continue;
                }

                $features[] = [
                    'id' => $feature->id(),
                    'label' => $feature->label(),
                    'description' => $feature->getDescription(),
                    'category' => $category,
                    'base_cost_monthly' => round($base_cost, 2),
                    'unit_cost' => round($unit_cost, 4),
                    'usage_metric' => $usage_metric,
                    'icon' => $feature->getIcon(),
                ];

                $total_base_cost += $base_cost;
                $total_unit_cost_potential += $unit_cost * 1000; // Estimado: 1000 unidades

                // Agrupar por categoría
                if (isset($by_category[$category])) {
                    $by_category[$category]['count']++;
                    $by_category[$category]['base_cost'] += $base_cost;
                }
            }
        } catch (\Exception $e) {
            \Drupal::logger('ecosistema_jaraba_core')->warning(
                'FinOps: Error loading features: @error',
                ['@error' => $e->getMessage()]
            );
        }

        // Ordenar features por coste base
        usort($features, fn($a, $b) => $b['base_cost_monthly'] <=> $a['base_cost_monthly']);

        return [
            'features' => $features,
            'by_category' => $by_category,
            'totals' => [
                'count' => count($features),
                'base_cost_monthly' => round($total_base_cost, 2),
                'unit_cost_potential' => round($total_unit_cost_potential, 2),
            ],
            'has_costs' => count($features) > 0,
        ];
    }

}
