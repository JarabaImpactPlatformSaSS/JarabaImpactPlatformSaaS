<?php

namespace Drupal\jaraba_ads\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ads\Service\AdsAnalyticsService;
use Drupal\jaraba_ads\Service\CampaignManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Ads Dashboard page.
 *
 * ESTRUCTURA:
 * Controller principal del módulo jaraba_ads que renderiza el dashboard
 * de campañas publicitarias con métricas consolidadas, distribución
 * de gasto por plataforma y listado de campañas activas.
 *
 * LOGICA:
 * Obtiene el tenant_id desde TenantContextService (try-catch para
 * tolerancia si el servicio no está disponible), consulta las métricas
 * agregadas y por plataforma a través de AdsAnalyticsService, carga
 * las campañas del tenant, y ensambla el render array con la plantilla
 * Twig y la biblioteca CSS/JS.
 *
 * RELACIONES:
 * - AdsDashboardController -> AdsAnalyticsService (métricas)
 * - AdsDashboardController -> CampaignManagerService (campañas)
 * - AdsDashboardController -> TenantContextService (contexto tenant)
 * - AdsDashboardController -> jaraba_ads_dashboard template (renderizado)
 * - AdsDashboardController <- jaraba_ads.routing.yml (ruta /admin/ads)
 */
class AdsDashboardController extends ControllerBase {

  /**
   * Servicio de analítica de ads.
   *
   * @var \Drupal\jaraba_ads\Service\AdsAnalyticsService|null
   */
  protected ?AdsAnalyticsService $adsAnalytics = NULL;

  /**
   * Servicio de gestión de campañas.
   *
   * @var \Drupal\jaraba_ads\Service\CampaignManagerService|null
   */
  protected ?CampaignManagerService $campaignManager = NULL;

  /**
   * Servicio de contexto de tenant.
   *
   * @var object|null
   */
  protected $tenantContext = NULL;

  /**
   * Canal de log dedicado para el módulo de ads.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected ?LoggerInterface $logger = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->adsAnalytics = $container->get('jaraba_ads.ads_analytics');
    }
    catch (\Exception $e) {
      // AdsAnalyticsService may not be available yet.
    }

    try {
      $instance->campaignManager = $container->get('jaraba_ads.campaign_manager');
    }
    catch (\Exception $e) {
      // CampaignManagerService may not be available yet.
    }

    try {
      $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    catch (\Exception $e) {
      // TenantContextService may not be available yet.
    }

    try {
      $instance->logger = $container->get('logger.channel.jaraba_ads');
    }
    catch (\Exception $e) {
      // Logger channel may not be available yet.
    }

    return $instance;
  }

  /**
   * Renders the Ads Dashboard page.
   *
   * ESTRUCTURA: Método principal del controller, invocado por la ruta
   * jaraba_ads.dashboard (/admin/ads).
   *
   * LOGICA:
   * 1. Obtiene el tenant_id del contexto actual.
   * 2. Consulta métricas agregadas del tenant.
   * 3. Consulta distribución de gasto por plataforma.
   * 4. Carga las campañas del tenant con datos normalizados.
   * 5. Ensambla el render array con template y library.
   *
   * RELACIONES:
   * - Consume AdsAnalyticsService::getTenantAdMetrics()
   * - Consume AdsAnalyticsService::getAdSpendByPlatform()
   * - Consume CampaignManagerService::getTenantCampaigns()
   * - Produce render array con #theme = 'jaraba_ads_dashboard'
   *
   * @return array
   *   Render array for the Ads Dashboard.
   */
  public function dashboard(): array {
    $tenant_id = $this->resolveTenantId();
    $metrics = $this->loadMetrics($tenant_id);
    $spend_by_platform = $this->loadSpendByPlatform($tenant_id);
    $campaigns = $this->loadCampaigns($tenant_id);

    $active_campaigns = $metrics['active_campaigns'] ?? 0;
    $total_spend = $metrics['total_spend'] ?? 0.0;

    return [
      '#theme' => 'jaraba_ads_dashboard',
      '#campaigns' => $campaigns,
      '#metrics' => $metrics,
      '#spend_by_platform' => $spend_by_platform,
      '#active_campaigns' => $active_campaigns,
      '#total_spend' => $total_spend,
      '#attached' => [
        'library' => [
          'jaraba_ads/ads-dashboard',
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['user'],
        'tags' => ['ad_campaign_list'],
      ],
    ];
  }

  /**
   * Resolves the current tenant ID from TenantContextService.
   *
   * LOGICA: Intenta obtener el tenant_id del servicio de contexto.
   * Si el servicio no está disponible o falla, devuelve 0.
   *
   * @return int
   *   ID del tenant actual o 0 como fallback.
   */
  protected function resolveTenantId(): int {
    if (!$this->tenantContext) {
      return 0;
    }

    try {
      if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
        $tenant_id = $this->tenantContext->getCurrentTenantId();
        return (int) ($tenant_id ?? 0);
      }
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->warning('Ads Dashboard: Could not resolve tenant ID: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return 0;
  }

  /**
   * Loads aggregated metrics for a tenant.
   *
   * @param int $tenant_id
   *   Tenant ID.
   *
   * @return array
   *   Aggregated metrics array.
   */
  protected function loadMetrics(int $tenant_id): array {
    $defaults = [
      'total_campaigns' => 0,
      'active_campaigns' => 0,
      'total_spend' => 0.0,
      'total_impressions' => 0,
      'total_clicks' => 0,
      'total_conversions' => 0,
      'avg_ctr' => 0.0,
      'avg_cpc' => 0.0,
      'avg_roas' => 0.0,
      'total_budget' => 0.0,
    ];

    if (!$this->adsAnalytics || $tenant_id <= 0) {
      return $defaults;
    }

    try {
      return $this->adsAnalytics->getTenantAdMetrics($tenant_id);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('Ads Dashboard: Error loading metrics: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return $defaults;
    }
  }

  /**
   * Loads spend breakdown by platform.
   *
   * @param int $tenant_id
   *   Tenant ID.
   *
   * @return array
   *   Spend by platform array.
   */
  protected function loadSpendByPlatform(int $tenant_id): array {
    $defaults = [
      'google_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'meta_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'linkedin_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'tiktok_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
    ];

    if (!$this->adsAnalytics || $tenant_id <= 0) {
      return $defaults;
    }

    try {
      return $this->adsAnalytics->getAdSpendByPlatform($tenant_id);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('Ads Dashboard: Error loading spend by platform: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return $defaults;
    }
  }

  /**
   * Loads campaigns for a tenant and normalizes their data for the template.
   *
   * @param int $tenant_id
   *   Tenant ID.
   *
   * @return array
   *   Array of normalized campaign data for template rendering.
   */
  protected function loadCampaigns(int $tenant_id): array {
    if (!$this->campaignManager || $tenant_id <= 0) {
      return [];
    }

    try {
      $campaign_entities = $this->campaignManager->getTenantCampaigns($tenant_id);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('Ads Dashboard: Error loading campaigns: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return [];
    }

    $campaigns = [];

    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $campaign */
    foreach ($campaign_entities as $campaign) {
      $budget_total = (float) ($campaign->get('budget_total')->value ?? 0);
      $spend_to_date = (float) ($campaign->get('spend_to_date')->value ?? 0);

      $campaigns[] = [
        'id' => $campaign->id(),
        'label' => $campaign->label(),
        'platform' => $campaign->get('platform')->value ?? 'google_ads',
        'status' => $campaign->get('status')->value ?? 'draft',
        'budget_daily' => (float) ($campaign->get('budget_daily')->value ?? 0),
        'budget_total' => $budget_total,
        'spend_to_date' => $spend_to_date,
        'budget_utilization' => $campaign->getBudgetUtilization(),
        'impressions' => (int) ($campaign->get('impressions')->value ?? 0),
        'clicks' => (int) ($campaign->get('clicks')->value ?? 0),
        'conversions' => (int) ($campaign->get('conversions')->value ?? 0),
        'ctr' => (float) ($campaign->get('ctr')->value ?? 0),
        'cpc' => (float) ($campaign->get('cpc')->value ?? 0),
        'roas' => (float) ($campaign->get('roas')->value ?? 0),
        'start_date' => $campaign->get('start_date')->value ?? '',
        'end_date' => $campaign->get('end_date')->value ?? '',
        'edit_url' => '/admin/content/ad-campaign/' . $campaign->id() . '/edit',
      ];
    }

    return $campaigns;
  }

}
