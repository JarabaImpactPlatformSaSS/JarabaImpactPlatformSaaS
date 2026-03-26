<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsHubService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Analytics Hub.
 *
 * Dashboard unificado de analytics para admin/tenant sin salir del SaaS.
 * Reemplaza la necesidad de consultar GA4 externamente.
 */
class AnalyticsHubController extends ControllerBase {

  /**
   * Servicio de Analytics Hub.
   *
   * @var \Drupal\jaraba_analytics\Service\AnalyticsHubService
   */
  protected AnalyticsHubService $analyticsHub;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Constructor.
   */
  public function __construct(
    AnalyticsHubService $analytics_hub,
    ?TenantContextService $tenant_context = NULL,
  ) {
    $this->analyticsHub = $analytics_hub;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.analytics_hub'),
      $container->has('ecosistema_jaraba_core.tenant_context') ? $container->get('ecosistema_jaraba_core.tenant_context') : NULL,
    );
  }

  /**
   * Renderiza el dashboard principal del Analytics Hub.
   *
   * @return array
   *   Render array con #theme => analytics_hub_dashboard.
   */
  public function dashboard(): array {
    $tenantId = 0;
    if ($this->tenantContext !== NULL) {
      try {
        $tenant = $this->tenantContext->getCurrentTenant();
        if ($tenant !== NULL) {
          $tenantId = (int) $tenant->id();
        }
      }
      catch (\Exception $e) {
        // Sin contexto de tenant, usamos 0 (plataforma global).
      }
    }

    $kpis = $this->analyticsHub->getKpis($tenantId);
    $trafficTrend = $this->analyticsHub->getTrafficTrend($tenantId);
    $topPages = $this->analyticsHub->getTopPages($tenantId);
    $deviceBreakdown = $this->analyticsHub->getDeviceBreakdown($tenantId);
    $funnelData = $this->analyticsHub->getFunnelData($tenantId);
    $abExperiments = $this->analyticsHub->getActiveExperiments($tenantId);
    $recentInsights = $this->analyticsHub->getRecentInsights($tenantId);

    return [
      '#theme' => 'analytics_hub_dashboard',
      '#kpis' => $kpis,
      '#traffic_trend' => $trafficTrend,
      '#top_pages' => $topPages,
      '#device_breakdown' => $deviceBreakdown,
      '#funnel_data' => $funnelData,
      '#ab_experiments' => $abExperiments,
      '#recent_insights' => $recentInsights,
      '#attached' => [
        'library' => [
          'jaraba_analytics/analytics-hub',
        ],
        'drupalSettings' => [
          'analyticsHub' => [
            'trafficTrend' => $trafficTrend,
            'deviceBreakdown' => $deviceBreakdown,
            'funnelData' => $funnelData,
            'tenantId' => $tenantId,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'user'],
        'max-age' => 300,
      ],
    ];
  }

}
