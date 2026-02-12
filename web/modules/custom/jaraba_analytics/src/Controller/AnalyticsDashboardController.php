<?php

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Dashboard de Analytics.
 */
class AnalyticsDashboardController extends ControllerBase
{

    /**
     * Servicio de analytics.
     *
     * @var \Drupal\jaraba_analytics\Service\AnalyticsService
     */
    protected AnalyticsService $analyticsService;

    /**
     * Servicio de contexto de tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(AnalyticsService $analytics_service, TenantContextService $tenant_context)
    {
        $this->analyticsService = $analytics_service;
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_analytics.analytics_service'),
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * Renderiza el dashboard principal de analytics.
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? 1;

        return [
            '#theme' => 'analytics_dashboard',
            '#tenant_id' => $tenantId,
            '#attached' => [
                'library' => [
                    'jaraba_analytics/analytics-dashboard',
                    'jaraba_heatmap/viewer',
                ],
            ],
        ];
    }

}
