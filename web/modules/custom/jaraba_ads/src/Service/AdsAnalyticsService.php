<?php

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analítica de campañas publicitarias.
 *
 * ESTRUCTURA:
 * Servicio de analítica que calcula métricas agregadas de campañas
 * publicitarias por tenant, rendimiento por campaña individual y
 * distribución de gasto por plataforma. Depende de EntityTypeManager
 * para consulta de entidades, TenantContextService para aislamiento
 * multi-tenant, y del canal de log dedicado.
 *
 * LÓGICA:
 * Las métricas se calculan en tiempo real consultando las entidades
 * AdCampaign del tenant. Se agregan impresiones, clics, conversiones,
 * gasto y ROAS. La distribución por plataforma muestra el gasto
 * acumulado en cada plataforma de ads para identificar el canal
 * más rentable.
 *
 * RELACIONES:
 * - AdsAnalyticsService -> EntityTypeManager (dependencia)
 * - AdsAnalyticsService -> TenantContextService (dependencia)
 * - AdsAnalyticsService -> AdCampaign entity (consulta)
 * - AdsAnalyticsService <- AdsDashboardController (consumido por)
 * - AdsAnalyticsService <- AdsApiController (consumido por)
 *
 * @package Drupal\jaraba_ads\Service
 */
class AdsAnalyticsService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Canal de log dedicado para el módulo de ads.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de analítica de ads.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
  }

  /**
   * Obtiene métricas agregadas de ads por tenant.
   *
   * ESTRUCTURA: Método público de analítica agregada.
   *
   * LÓGICA: Consulta todas las campañas del tenant y calcula:
   *   total de campañas, campañas activas, gasto total, impresiones,
   *   clics, conversiones, CTR medio, CPC medio y ROAS medio.
   *
   * RELACIONES: Consume AdCampaign storage, filtra por tenant_id.
   *
   * @param int $tenant_id
   *   ID del tenant para filtrar métricas.
   *
   * @return array
   *   Estructura de métricas:
   *   - 'total_campaigns' (int): Total de campañas.
   *   - 'active_campaigns' (int): Campañas activas.
   *   - 'total_spend' (float): Gasto total acumulado en EUR.
   *   - 'total_impressions' (int): Total de impresiones.
   *   - 'total_clicks' (int): Total de clics.
   *   - 'total_conversions' (int): Total de conversiones.
   *   - 'avg_ctr' (float): CTR medio (%).
   *   - 'avg_cpc' (float): CPC medio (EUR).
   *   - 'avg_roas' (float): ROAS medio.
   *   - 'total_budget' (float): Presupuesto total asignado.
   */
  public function getTenantAdMetrics(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');

    $all_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->execute();

    $campaigns = !empty($all_ids) ? $storage->loadMultiple($all_ids) : [];

    $stats = [
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

    $ctr_values = [];
    $cpc_values = [];
    $roas_values = [];

    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $campaign */
    foreach ($campaigns as $campaign) {
      $stats['total_campaigns']++;

      if ($campaign->get('status')->value === 'active') {
        $stats['active_campaigns']++;
      }

      $stats['total_spend'] += (float) ($campaign->get('spend_to_date')->value ?? 0);
      $stats['total_impressions'] += (int) ($campaign->get('impressions')->value ?? 0);
      $stats['total_clicks'] += (int) ($campaign->get('clicks')->value ?? 0);
      $stats['total_conversions'] += (int) ($campaign->get('conversions')->value ?? 0);
      $stats['total_budget'] += (float) ($campaign->get('budget_total')->value ?? 0);

      $ctr = (float) ($campaign->get('ctr')->value ?? 0);
      if ($ctr > 0) {
        $ctr_values[] = $ctr;
      }

      $cpc = (float) ($campaign->get('cpc')->value ?? 0);
      if ($cpc > 0) {
        $cpc_values[] = $cpc;
      }

      $roas = (float) ($campaign->get('roas')->value ?? 0);
      if ($roas > 0) {
        $roas_values[] = $roas;
      }
    }

    // Calcular medias.
    if (!empty($ctr_values)) {
      $stats['avg_ctr'] = round(array_sum($ctr_values) / count($ctr_values), 2);
    }
    if (!empty($cpc_values)) {
      $stats['avg_cpc'] = round(array_sum($cpc_values) / count($cpc_values), 4);
    }
    if (!empty($roas_values)) {
      $stats['avg_roas'] = round(array_sum($roas_values) / count($roas_values), 2);
    }

    return $stats;
  }

  /**
   * Obtiene métricas de rendimiento de una campaña individual.
   *
   * ESTRUCTURA: Método público de análisis por campaña.
   *
   * LÓGICA: Carga la campaña y devuelve todas sus métricas con
   *   información adicional como porcentaje de presupuesto consumido
   *   y estado de ejecución.
   *
   * RELACIONES: Consume AdCampaign storage.
   *
   * @param int $campaign_id
   *   ID de la campaña a analizar.
   *
   * @return array
   *   Estructura de métricas de la campaña, o array vacío si no existe.
   */
  public function getCampaignPerformance(int $campaign_id): array {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');
    /** @var \Drupal\jaraba_ads\Entity\AdCampaign|null $campaign */
    $campaign = $storage->load($campaign_id);

    if (!$campaign) {
      return [];
    }

    return [
      'id' => $campaign->id(),
      'label' => $campaign->label(),
      'platform' => $campaign->get('platform')->value,
      'status' => $campaign->get('status')->value,
      'budget_total' => (float) $campaign->get('budget_total')->value,
      'spend_to_date' => (float) $campaign->get('spend_to_date')->value,
      'budget_utilization' => $campaign->getBudgetUtilization(),
      'impressions' => (int) $campaign->get('impressions')->value,
      'clicks' => (int) $campaign->get('clicks')->value,
      'conversions' => (int) $campaign->get('conversions')->value,
      'ctr' => (float) $campaign->get('ctr')->value,
      'cpc' => (float) $campaign->get('cpc')->value,
      'roas' => (float) $campaign->get('roas')->value,
    ];
  }

  /**
   * Obtiene la distribución de gasto por plataforma de ads.
   *
   * ESTRUCTURA: Método público de análisis por plataforma.
   *
   * LÓGICA: Agrupa el gasto acumulado de todas las campañas del tenant
   *   por plataforma de ads, permitiendo identificar el canal con
   *   mayor inversión y comparar el rendimiento relativo.
   *
   * RELACIONES: Consume AdCampaign storage, filtra por tenant_id.
   *
   * @param int $tenant_id
   *   ID del tenant para filtrar.
   *
   * @return array
   *   Array asociativo con claves de plataforma y valores:
   *   - 'spend' (float): Gasto total en la plataforma.
   *   - 'campaigns' (int): Número de campañas.
   *   - 'impressions' (int): Total de impresiones.
   *   - 'clicks' (int): Total de clics.
   *   - 'conversions' (int): Total de conversiones.
   */
  public function getAdSpendByPlatform(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('ad_campaign');

    $all_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->execute();

    $campaigns = !empty($all_ids) ? $storage->loadMultiple($all_ids) : [];

    $platforms = [
      'google_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'meta_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'linkedin_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
      'tiktok_ads' => ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0],
    ];

    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $campaign */
    foreach ($campaigns as $campaign) {
      $platform = $campaign->get('platform')->value;
      if (!isset($platforms[$platform])) {
        $platforms[$platform] = ['spend' => 0.0, 'campaigns' => 0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0];
      }

      $platforms[$platform]['spend'] += (float) ($campaign->get('spend_to_date')->value ?? 0);
      $platforms[$platform]['campaigns']++;
      $platforms[$platform]['impressions'] += (int) ($campaign->get('impressions')->value ?? 0);
      $platforms[$platform]['clicks'] += (int) ($campaign->get('clicks')->value ?? 0);
      $platforms[$platform]['conversions'] += (int) ($campaign->get('conversions')->value ?? 0);
    }

    return $platforms;
  }

}
