<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * Constructs an ObservabilityApiController.
     */
    public function __construct(AIObservabilityService $observability, TenantContextService $tenantContext)
    {
        $this->observability = $observability;
        $this->tenantContext = $tenantContext;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.observability'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
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

}
