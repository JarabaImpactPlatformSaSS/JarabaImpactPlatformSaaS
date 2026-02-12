<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del Dashboard frontend de Insights Hub.
 *
 * PROPOSITO:
 * Renderiza el dashboard unificado de metricas para el usuario final
 * (no admin) y expone el endpoint API de resumen de metricas.
 *
 * FUNCIONALIDADES:
 * - Dashboard frontend con paneles de SEO, Performance, Errors, Uptime
 * - API de resumen de metricas con filtro de rango de fechas
 * - Multi-tenant: datos filtrados por tenant del usuario actual
 *
 * RUTAS:
 * - GET /insights -> dashboard()
 * - GET /api/v1/insights/summary -> apiSummary()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class InsightsDashboardController extends ControllerBase {

  /**
   * El servicio agregador de insights.
   *
   * @var \Drupal\jaraba_insights_hub\Service\InsightsAggregatorService
   */
  protected $insightsAggregator;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->insightsAggregator = $container->get('jaraba_insights_hub.insights_aggregator');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Renderiza el dashboard principal de Insights Hub.
   *
   * Pagina frontend (no admin) que muestra paneles unificados de
   * Search Console, Core Web Vitals, Error Tracking y Uptime.
   *
   * @return array
   *   Render array con #theme => 'page__insights'.
   */
  public function dashboard(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;

    // Obtener resumen de metricas para el dashboard (ultimos 7 dias por defecto).
    $summary = $this->insightsAggregator->getSummary($tenant_id, '7d');

    return [
      '#theme' => 'page__insights',
      '#summary' => $summary,
      '#tenant_id' => $tenant_id,
      '#labels' => [
        'title' => $this->t('Insights Hub'),
        'subtitle' => $this->t('Metricas unificadas de rendimiento, SEO, errores y disponibilidad'),
        'seo_panel' => $this->t('Search Console'),
        'performance_panel' => $this->t('Core Web Vitals'),
        'errors_panel' => $this->t('Error Tracking'),
        'uptime_panel' => $this->t('Uptime Monitor'),
        'date_range_7d' => $this->t('Ultimos 7 dias'),
        'date_range_30d' => $this->t('Ultimos 30 dias'),
        'date_range_90d' => $this->t('Ultimos 90 dias'),
        'no_data' => $this->t('No hay datos disponibles para este periodo.'),
        'settings' => $this->t('Configuracion'),
        'view_all' => $this->t('Ver todo'),
      ],
      '#urls' => [
        'api_summary' => Url::fromRoute('jaraba_insights_hub.api.summary')->toString(),
        'settings' => Url::fromRoute('jaraba_insights_hub.settings')->toString(),
        'admin_overview' => Url::fromRoute('jaraba_insights_hub.admin_overview')->toString(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_insights_hub/dashboard',
        ],
        'drupalSettings' => [
          'jarabaInsightsHub' => [
            'tenantId' => $tenant_id,
            'apiSummaryUrl' => Url::fromRoute('jaraba_insights_hub.api.summary')->toString(),
            'summary' => $summary,
          ],
        ],
      ],
    ];
  }

  /**
   * Endpoint API: Resumen de todas las metricas de Insights Hub.
   *
   * GET /api/v1/insights/summary?date_range=7d|30d|90d
   *
   * Devuelve un resumen agregado de todas las metricas del tenant actual
   * incluyendo Search Console, Web Vitals, Errors y Uptime.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con query param date_range.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {...}}.
   */
  public function apiSummary(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Validar date_range.
      $date_range = $request->query->get('date_range', '7d');
      $allowed_ranges = ['7d', '30d', '90d'];
      if (!in_array($date_range, $allowed_ranges, TRUE)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Rango de fechas invalido. Valores permitidos: @ranges', [
            '@ranges' => implode(', ', $allowed_ranges),
          ]),
        ], 400);
      }

      $summary = $this->insightsAggregator->getSummary($tenant_id, $date_range);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $summary,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener resumen de insights: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener las metricas.'),
      ], 500);
    }
  }

}
