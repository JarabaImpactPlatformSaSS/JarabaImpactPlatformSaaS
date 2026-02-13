<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_agroconecta_core\Service\AgroAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para analytics de AgroConecta.
 *
 * ENDPOINTS:
 * GET  /api/v1/agro/analytics/dashboard     → KPIs + sparklines
 * GET  /api/v1/agro/analytics/top-products  → Ranking productos
 * GET  /api/v1/agro/analytics/top-producers → Ranking productores
 * POST /api/v1/agro/analytics/aggregate     → Forzar agregación diaria
 * GET  /api/v1/agro/analytics/alerts        → Alertas activas
 * POST /api/v1/agro/analytics/alerts/evaluate → Evaluar alertas ahora
 */
class AnalyticsApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected AgroAnalyticsService $analyticsService,
        protected TenantContextService $tenantContext,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.analytics_service'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Dashboard KPIs con comparativa periodo anterior.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $period = $request->query->get('period', '7d');

        $data = $this->analyticsService->getDashboardData($tenantId, $period);
        return new JsonResponse($data);
    }

    /**
     * Top productos por ventas.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $limit = min((int) $request->query->get('limit', 10), 50);

        $products = $this->analyticsService->getTopProducts($tenantId, $limit);
        return new JsonResponse(['products' => $products, 'total' => count($products)]);
    }

    /**
     * Top productores por GMV.
     */
    public function topProducers(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $limit = min((int) $request->query->get('limit', 10), 50);

        $producers = $this->analyticsService->getTopProducers($tenantId, $limit);
        return new JsonResponse(['producers' => $producers, 'total' => count($producers)]);
    }

    /**
     * Forzar agregación diaria (admin/debug).
     */
    public function aggregate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $date = $data['date'] ?? NULL;

        $result = $this->analyticsService->aggregateDaily($date);
        return new JsonResponse($result);
    }

    /**
     * Alertas activas recientes.
     */
    public function alerts(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);

        $alerts = $this->analyticsService->getActiveAlerts($tenantId);
        return new JsonResponse(['alerts' => $alerts, 'total' => count($alerts)]);
    }

    /**
     * Evaluar reglas de alerta ahora.
     */
    public function evaluateAlerts(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? (int) ($data['tenant_id'] ?? 1);

        $triggered = $this->analyticsService->evaluateAlerts($tenantId);
        return new JsonResponse([
            'triggered' => $triggered,
            'total_triggered' => count($triggered),
        ]);
    }

}
