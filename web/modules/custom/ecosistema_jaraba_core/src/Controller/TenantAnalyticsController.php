<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\TenantAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('ecosistema_jaraba_core.tenant_analytics')
        );
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
