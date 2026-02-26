<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for AI Observability API.
 */
class ObservabilityApiController extends ControllerBase
{

    /**
     * The observability service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService
     */
    protected AIObservabilityService $observability;

    /**
     * The tenant context service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * GAP-AUD-024: Cost alert service for per-tenant attribution.
     */
    protected ?object $costAlertService;

    /**
     * GAP-AUD-024: Tenant metering service for billing integration.
     */
    protected ?object $tenantMetering;

    /**
     * Constructs an ObservabilityApiController.
     */
    public function __construct(
        AIObservabilityService $observability,
        TenantContextService $tenantContext,
        ?object $costAlertService = NULL,
        ?object $tenantMetering = NULL,
    ) {
        $this->observability = $observability;
        $this->tenantContext = $tenantContext;
        $this->costAlertService = $costAlertService;
        $this->tenantMetering = $tenantMetering;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.observability'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->has('ecosistema_jaraba_core.cost_alert')
                ? $container->get('ecosistema_jaraba_core.cost_alert')
                : NULL,
            $container->has('jaraba_billing.tenant_metering')
                ? $container->get('jaraba_billing.tenant_metering')
                : NULL,
        );
    }

    /**
     * Gets overall statistics.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Statistics response.
     */
    public function getStats(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'day');
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

        $stats = $this->observability->getStats($period, $tenantId);

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'data' => $stats,
        ]);
    }

    /**
     * Gets cost breakdown by tier.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Cost breakdown response.
     */
    public function getCostByTier(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');

        $costs = $this->observability->getCostByTier($period);

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'data' => $costs,
            'total' => round(array_sum($costs), 4),
        ]);
    }

    /**
     * Gets usage breakdown by agent.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Usage breakdown response.
     */
    public function getUsageByAgent(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');

        $usage = $this->observability->getUsageByAgent($period);

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'data' => $usage,
        ]);
    }

    /**
     * Gets model routing savings.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Savings response.
     */
    public function getSavings(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');

        $savings = $this->observability->getSavings($period);

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'data' => $savings,
        ]);
    }

    /**
     * Gets complete dashboard data.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Dashboard data response.
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');

        return new JsonResponse([
            'success' => TRUE,
            'period' => $period,
            'stats' => $this->observability->getStats($period),
            'cost_by_tier' => $this->observability->getCostByTier($period),
            'usage_by_agent' => $this->observability->getUsageByAgent($period),
            'savings' => $this->observability->getSavings($period),
        ]);
    }

    /**
     * GAP-AUD-004: Gets usage breakdown by region/tenant.
     */
    public function getUsageByRegion(Request $request): JsonResponse
    {
        $tenantId = $request->query->get('tenant_id', '');
        $days = (int) $request->query->get('days', 30);

        $data = $this->observability->getUsageByRegion($tenantId, $days);

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
        ]);
    }

    /**
     * GAP-AUD-004: Exports usage data as CSV download.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $tenantId = $request->query->get('tenant_id', '');
        $days = (int) $request->query->get('days', 30);

        $csv = $this->observability->exportCsv($tenantId, $days);

        $response = new \Symfony\Component\HttpFoundation\Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="ai-usage-export.csv"');

        return $response;
    }

    /**
     * GAP-AUD-004: Gets daily usage trend for sparklines.
     */
    public function getTrend(Request $request): JsonResponse
    {
        $tenantId = $request->query->get('tenant_id', '');
        $days = (int) $request->query->get('days', 7);

        $data = $this->observability->getUsageTrend($tenantId, $days);

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
        ]);
    }

    /**
     * GAP-AUD-024: Per-tenant AI cost attribution and budget alerts.
     *
     * Returns the current tenant's AI usage, cost breakdown by tier/agent,
     * budget alert status, and metering data for billing integration.
     */
    public function getCostAttribution(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenant ? (string) $tenant->id() : '';

        if (empty($tenantId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'No tenant context available.',
            ], 403);
        }

        $days = (int) $request->query->get('days', 30);

        // Get AI usage stats for this tenant.
        $stats = $this->observability->getStats($tenantId);
        $byAgent = $this->observability->getUsageByAgent($tenantId);
        $trend = $this->observability->getUsageTrend($tenantId, min($days, 30));
        $savings = $this->observability->getSavings($tenantId);

        // Cost alert thresholds.
        $alertData = [];
        if ($this->costAlertService !== NULL) {
            try {
                $totalTokens = (int) ($stats['total_input_tokens'] ?? 0) + (int) ($stats['total_output_tokens'] ?? 0);
                $alertData = $this->costAlertService->checkThresholds($tenantId, $totalTokens);
            }
            catch (\Exception $e) {
                $alertData = ['level' => 'unknown', 'error' => $e->getMessage()];
            }
        }

        // Metering data for billing.
        $meteringData = [];
        if ($this->tenantMetering !== NULL) {
            try {
                $meteringData = $this->tenantMetering->getUsage($tenantId, 'month');
            }
            catch (\Exception $e) {
                $meteringData = ['error' => $e->getMessage()];
            }
        }

        return new JsonResponse([
            'success' => TRUE,
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'stats' => $stats,
            'by_agent' => $byAgent,
            'trend' => $trend,
            'savings' => $savings,
            'budget_alert' => $alertData,
            'metering' => $meteringData,
        ]);
    }

}
