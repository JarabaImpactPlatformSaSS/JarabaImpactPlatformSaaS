<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\TenantAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ecosistema_jaraba_core\Service\UsageLimitsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para analytics del Tenant.
 *
 * Proporciona endpoints JSON para gráficos del dashboard.
 */
class TenantAnalyticsController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
        protected TenantAnalyticsService $analytics,
        protected ?UsageLimitsService $usageLimits = NULL,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('ecosistema_jaraba_core.tenant_analytics'),
            $container->has('ecosistema_jaraba_core.usage_limits') ? $container->get('ecosistema_jaraba_core.usage_limits') : NULL
        );
    }

    /**
     * Renders the per-tenant analytics HTML dashboard (S2-04).
     *
     * @return array
     *   Render array.
     */
    public function analyticsPage(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return [
                '#markup' => $this->t('No tenant context available.'),
            ];
        }

        $tenantId = $tenant->id();

        // Gather data from existing services.
        $salesData = $this->analytics->getSalesTrend($tenantId, 30);
        $mrrData = $this->analytics->getMrrTrend($tenantId, 6);
        $customersData = $this->analytics->getCustomersTrend($tenantId, 28);
        $topProducts = $this->analytics->getTopProducts($tenantId, 5);

        // Usage vs limits.
        $usage = [];
        if ($this->usageLimits) {
            try {
                $usage = $this->usageLimits->checkAllLimits($tenantId);
            }
            catch (\Exception $e) {
                // Non-critical.
            }
        }

        // AI tokens consumed this month.
        $aiTokens = 0;
        if (\Drupal::hasService('jaraba_ai_agents.observability')) {
            try {
                $obs = \Drupal::service('jaraba_ai_agents.observability');
                $stats = $obs->getStats($tenantId, 30);
                $aiTokens = ($stats['total_input_tokens'] ?? 0) + ($stats['total_output_tokens'] ?? 0);
            }
            catch (\Exception $e) {
                // Non-critical.
            }
        }

        return [
            '#theme' => 'tenant_analytics_dashboard',
            '#tenant_name' => $tenant->label() ?? $this->t('Tenant'),
            '#metrics' => [
                'sales_total' => $salesData['total'] ?? 0,
                'sales_average' => $salesData['average'] ?? 0,
                'mrr_current' => $mrrData['current'] ?? 0,
                'mrr_growth' => $mrrData['growth'] ?? 0,
                'ai_tokens' => $aiTokens,
            ],
            '#sales_chart' => $salesData,
            '#mrr_chart' => $mrrData,
            '#customers_chart' => $customersData,
            '#top_products' => $topProducts,
            '#usage_limits' => $usage,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/tenant-dashboard',
                ],
                'drupalSettings' => [
                    'tenantAnalytics' => [
                        'apiBase' => '/api/v1/tenant/analytics',
                    ],
                ],
            ],
        ];
    }

    /**
     * API endpoint para datos de tendencias de ventas.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La solicitud HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos JSON para el gráfico.
     */
    public function salesTrend(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant found'], 403);
        }

        $days = (int) $request->query->get('days', 30);
        $days = min(90, max(7, $days)); // Entre 7 y 90 días.

        $data = $this->analytics->getSalesTrend($tenant->id(), $days);

        return new JsonResponse([
            'success' => TRUE,
            'chart' => [
                'type' => 'line',
                'labels' => $data['labels'],
                'datasets' => [
                    [
                        'label' => (string) $this->t('Ventas (€)'),
                        'data' => $data['data'],
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => TRUE,
                        'tension' => 0.4,
                    ],
                ],
            ],
            'summary' => [
                'total' => $data['total'],
                'average' => $data['average'],
            ],
        ]);
    }

    /**
     * API endpoint para datos de MRR.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos JSON para el gráfico.
     */
    public function mrrTrend(): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant found'], 403);
        }

        $data = $this->analytics->getMrrTrend($tenant->id(), 6);

        return new JsonResponse([
            'success' => TRUE,
            'chart' => [
                'type' => 'bar',
                'labels' => $data['labels'],
                'datasets' => [
                    [
                        'label' => 'MRR (€)',
                        'data' => $data['data'],
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(99, 102, 241, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(192, 132, 252, 0.8)',
                            'rgba(216, 180, 254, 0.8)',
                        ],
                        'borderRadius' => 8,
                    ],
                ],
            ],
            'summary' => [
                'current' => $data['current'],
                'growth' => $data['growth'],
            ],
        ]);
    }

    /**
     * API endpoint para datos de clientes.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos JSON para el gráfico.
     */
    public function customersTrend(): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant found'], 403);
        }

        $data = $this->analytics->getCustomersTrend($tenant->id(), 28);

        return new JsonResponse([
            'success' => TRUE,
            'chart' => [
                'type' => 'bar',
                'labels' => $data['labels'],
                'datasets' => $data['datasets'],
            ],
        ]);
    }

    /**
     * API endpoint para productos top.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos JSON para la tabla.
     */
    public function topProducts(): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant found'], 403);
        }

        $products = $this->analytics->getTopProducts($tenant->id(), 5);

        return new JsonResponse([
            'success' => TRUE,
            'products' => $products,
        ]);
    }

}
