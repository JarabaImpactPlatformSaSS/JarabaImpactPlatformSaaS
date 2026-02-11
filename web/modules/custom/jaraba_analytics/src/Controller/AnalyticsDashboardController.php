<?php

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
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
     * Constructor.
     */
    public function __construct(AnalyticsService $analytics_service)
    {
        $this->analyticsService = $analytics_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_analytics.analytics_service')
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
        // @todo Detectar tenant del contexto.
        $tenantId = 1;

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
