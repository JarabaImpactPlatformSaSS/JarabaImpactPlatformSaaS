<?php

namespace Drupal\jaraba_ads\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ads\Service\AdsAnalyticsService;
use Drupal\jaraba_ads\Service\CampaignManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * REST API controller for Ads campaigns.
 *
 * ESTRUCTURA:
 * Controller API REST que expone endpoints para consultar campañas
 * publicitarias, métricas consolidadas y distribución de gasto por
 * plataforma en formato JSON. Devuelve respuestas normalizadas con
 * la estructura { data, meta, errors }.
 *
 * LOGICA:
 * Todos los endpoints obtienen el tenant_id del contexto actual y
 * filtran las consultas por tenant para aislamiento multi-tenant.
 * Las respuestas siguen la convención JSON API con data (payload),
 * meta (metadatos como timestamp, tenant_id) y errors (lista vacía
 * cuando no hay errores, con código y mensaje en caso de fallo).
 *
 * RELACIONES:
 * - AdsApiController -> AdsAnalyticsService (métricas)
 * - AdsApiController -> CampaignManagerService (campañas)
 * - AdsApiController -> TenantContextService (contexto tenant)
 * - AdsApiController <- jaraba_ads.routing.yml (rutas /api/v1/ads/*)
 */
class AdsApiController extends ControllerBase {

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
   * Canal de log dedicado.
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
   * Lists all campaigns for the current tenant.
   *
   * ESTRUCTURA: Endpoint GET /api/v1/ads.
   *
   * LOGICA: Obtiene el tenant_id, consulta todas las campañas del tenant
   * y devuelve los datos normalizados en JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with list of campaigns.
   */
  public function listCampaigns(): JsonResponse {
    $tenant_id = $this->resolveTenantId();

    if ($tenant_id <= 0) {
      return $this->errorResponse(
        $this->t('Tenant context not available.')->render(),
        'TENANT_NOT_FOUND',
        403
      );
    }

    if (!$this->campaignManager) {
      return $this->errorResponse(
        $this->t('Campaign service not available.')->render(),
        'SERVICE_UNAVAILABLE',
        503
      );
    }

    try {
      $campaign_entities = $this->campaignManager->getTenantCampaigns($tenant_id);
      $campaigns = [];

      /** @var \Drupal\jaraba_ads\Entity\AdCampaign $campaign */
      foreach ($campaign_entities as $campaign) {
        $campaigns[] = $this->normalizeCampaign($campaign);
      }

      return $this->successResponse($campaigns, [
        'total' => count($campaigns),
        'tenant_id' => $tenant_id,
      ]);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('API listCampaigns error: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return $this->errorResponse(
        $this->t('Error loading campaigns.')->render(),
        'INTERNAL_ERROR',
        500
      );
    }
  }

  /**
   * Gets a single campaign by ID.
   *
   * ESTRUCTURA: Endpoint GET /api/v1/ads/{campaign_id}.
   *
   * LOGICA: Valida que la campaña exista y devuelve sus datos
   * detallados incluyendo métricas de rendimiento.
   *
   * @param int $campaign_id
   *   Campaign entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with campaign data.
   */
  public function getCampaign(int $campaign_id): JsonResponse {
    if (!$this->adsAnalytics) {
      return $this->errorResponse(
        $this->t('Analytics service not available.')->render(),
        'SERVICE_UNAVAILABLE',
        503
      );
    }

    try {
      $performance = $this->adsAnalytics->getCampaignPerformance($campaign_id);

      if (empty($performance)) {
        return $this->errorResponse(
          $this->t('Campaign not found.')->render(),
          'NOT_FOUND',
          404
        );
      }

      return $this->successResponse($performance, [
        'campaign_id' => $campaign_id,
      ]);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('API getCampaign error for @id: @error', [
          '@id' => $campaign_id,
          '@error' => $e->getMessage(),
        ]);
      }
      return $this->errorResponse(
        $this->t('Error loading campaign.')->render(),
        'INTERNAL_ERROR',
        500
      );
    }
  }

  /**
   * Gets aggregated ad metrics for the current tenant.
   *
   * ESTRUCTURA: Endpoint GET /api/v1/ads/metrics.
   *
   * LOGICA: Obtiene el tenant_id y consulta métricas agregadas
   * (impresiones, clics, conversiones, CTR, CPC, ROAS).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with aggregated metrics.
   */
  public function getMetrics(): JsonResponse {
    $tenant_id = $this->resolveTenantId();

    if ($tenant_id <= 0) {
      return $this->errorResponse(
        $this->t('Tenant context not available.')->render(),
        'TENANT_NOT_FOUND',
        403
      );
    }

    if (!$this->adsAnalytics) {
      return $this->errorResponse(
        $this->t('Analytics service not available.')->render(),
        'SERVICE_UNAVAILABLE',
        503
      );
    }

    try {
      $metrics = $this->adsAnalytics->getTenantAdMetrics($tenant_id);

      return $this->successResponse($metrics, [
        'tenant_id' => $tenant_id,
      ]);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('API getMetrics error: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return $this->errorResponse(
        $this->t('Error loading metrics.')->render(),
        'INTERNAL_ERROR',
        500
      );
    }
  }

  /**
   * Gets ad spend broken down by platform.
   *
   * ESTRUCTURA: Endpoint GET /api/v1/ads/spend-by-platform.
   *
   * LOGICA: Obtiene la distribución de gasto publicitario por plataforma
   * (Google Ads, Meta Ads, LinkedIn Ads, TikTok Ads).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with spend breakdown by platform.
   */
  public function getSpendByPlatform(): JsonResponse {
    $tenant_id = $this->resolveTenantId();

    if ($tenant_id <= 0) {
      return $this->errorResponse(
        $this->t('Tenant context not available.')->render(),
        'TENANT_NOT_FOUND',
        403
      );
    }

    if (!$this->adsAnalytics) {
      return $this->errorResponse(
        $this->t('Analytics service not available.')->render(),
        'SERVICE_UNAVAILABLE',
        503
      );
    }

    try {
      $spend = $this->adsAnalytics->getAdSpendByPlatform($tenant_id);

      return $this->successResponse($spend, [
        'tenant_id' => $tenant_id,
      ]);
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->error('API getSpendByPlatform error: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
      return $this->errorResponse(
        $this->t('Error loading spend data.')->render(),
        'INTERNAL_ERROR',
        500
      );
    }
  }

  /**
   * Resolves the current tenant ID from TenantContextService.
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
        $this->logger->warning('API: Could not resolve tenant ID: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return 0;
  }

  /**
   * Normalizes a campaign entity to an array for JSON output.
   *
   * @param \Drupal\jaraba_ads\Entity\AdCampaign $campaign
   *   Campaign entity.
   *
   * @return array
   *   Normalized campaign data.
   */
  protected function normalizeCampaign($campaign): array {
    return [
      'id' => (int) $campaign->id(),
      'label' => $campaign->label(),
      'platform' => $campaign->get('platform')->value ?? '',
      'status' => $campaign->get('status')->value ?? 'draft',
      'budget_daily' => (float) ($campaign->get('budget_daily')->value ?? 0),
      'budget_total' => (float) ($campaign->get('budget_total')->value ?? 0),
      'spend_to_date' => (float) ($campaign->get('spend_to_date')->value ?? 0),
      'budget_utilization' => $campaign->getBudgetUtilization(),
      'impressions' => (int) ($campaign->get('impressions')->value ?? 0),
      'clicks' => (int) ($campaign->get('clicks')->value ?? 0),
      'conversions' => (int) ($campaign->get('conversions')->value ?? 0),
      'ctr' => (float) ($campaign->get('ctr')->value ?? 0),
      'cpc' => (float) ($campaign->get('cpc')->value ?? 0),
      'roas' => (float) ($campaign->get('roas')->value ?? 0),
      'start_date' => $campaign->get('start_date')->value ?? NULL,
      'end_date' => $campaign->get('end_date')->value ?? NULL,
      'created' => $campaign->get('created')->value ?? NULL,
      'changed' => $campaign->get('changed')->value ?? NULL,
    ];
  }

  /**
   * Builds a normalized success JSON response.
   *
   * @param mixed $data
   *   Response payload.
   * @param array $meta
   *   Additional metadata.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  // AUDIT-CONS-N08: Standardized JSON envelope.
  protected function successResponse($data, array $meta = []): JsonResponse {
    $meta['timestamp'] = time();

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => $meta,
    ], 200);
  }

  /**
   * Builds a normalized error JSON response.
   *
   * @param string $message
   *   Error message.
   * @param string $code
   *   Error code.
   * @param int $status
   *   HTTP status code.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  protected function errorResponse(string $message, string $code, int $status): JsonResponse {
    return new JsonResponse([
      'success' => FALSE,
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
    ], $status);
  }

}
