<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_usage_billing\Service\UsageAggregatorService;
use Drupal\jaraba_usage_billing\Service\UsageAlertService;
use Drupal\jaraba_usage_billing\Service\UsageIngestionService;
use Drupal\jaraba_usage_billing\Service\UsagePricingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API REST para el sistema de usage billing.
 *
 * Expone endpoints para ingesta de eventos, consulta de agregados
 * y datos del dashboard.
 */
class UsageApiController extends ControllerBase {

  public function __construct(
    protected UsageIngestionService $ingestion,
    protected UsageAggregatorService $aggregator,
    protected UsagePricingService $pricing,
    protected UsageAlertService $alertService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_usage_billing.ingestion'),
      $container->get('jaraba_usage_billing.aggregator'),
      $container->get('jaraba_usage_billing.pricing'),
      $container->get('jaraba_usage_billing.alert'),
      $container->get('logger.channel.jaraba_usage_billing'),
    );
  }

  /**
   * POST /api/v1/usage/events - Ingesta un evento de uso.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con los datos del evento en JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta con el resultado de la ingesta.
   */
  public function ingestEvent(Request $request): JsonResponse {
    try {
      $content = $request->getContent();
      $data = json_decode($content, TRUE);

      if (!is_array($data)) {
        return new JsonResponse([
          'error' => 'Invalid JSON payload.',
        ], 400);
      }

      // Soporte para ingesta de lote.
      if (isset($data['events']) && is_array($data['events'])) {
        $count = $this->ingestion->batchIngest($data['events']);
        return new JsonResponse([
          'status' => 'ok',
          'ingested' => $count,
          'total' => count($data['events']),
        ], 201);
      }

      // Ingesta individual.
      $eventId = $this->ingestion->ingestEvent($data);

      if ($eventId === NULL) {
        return new JsonResponse([
          'error' => 'Failed to ingest event. Check required fields: event_type, metric_name, quantity, tenant_id.',
        ], 422);
      }

      return new JsonResponse([
        'status' => 'ok',
        'event_id' => $eventId,
      ], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en API de ingesta: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Internal server error.',
      ], 500);
    }
  }

  /**
   * GET /api/v1/usage/aggregates - Obtiene agregados de uso.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con query params: tenant_id, period, limit.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta con los datos agregados.
   */
  public function getAggregates(Request $request): JsonResponse {
    try {
      $tenantId = (int) $request->query->get('tenant_id', '0');
      $period = $request->query->get('period', 'daily');
      $limit = (int) $request->query->get('limit', '30');

      if ($tenantId <= 0) {
        return new JsonResponse([
          'error' => 'tenant_id is required.',
        ], 400);
      }

      $aggregates = $this->aggregator->getAggregates($tenantId, $period, $limit);

      $result = [];
      foreach ($aggregates as $aggregate) {
        $result[] = [
          'id' => (int) $aggregate->id(),
          'metric_name' => $aggregate->get('metric_name')->value,
          'period_type' => $aggregate->get('period_type')->value,
          'period_start' => (int) $aggregate->get('period_start')->value,
          'period_end' => (int) $aggregate->get('period_end')->value,
          'total_quantity' => (float) $aggregate->get('total_quantity')->value,
          'event_count' => (int) $aggregate->get('event_count')->value,
        ];
      }

      return new JsonResponse([
        'status' => 'ok',
        'tenant_id' => $tenantId,
        'period' => $period,
        'data' => $result,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en API de agregados: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Internal server error.',
      ], 500);
    }
  }

  /**
   * GET /api/v1/usage/dashboard - Datos para el dashboard de uso.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con query params: tenant_id, period.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta con datos completos del dashboard.
   */
  public function getDashboardData(Request $request): JsonResponse {
    try {
      $tenantId = (int) $request->query->get('tenant_id', '0');
      $period = $request->query->get('period', 'monthly');

      if ($tenantId <= 0) {
        return new JsonResponse([
          'error' => 'tenant_id is required.',
        ], 400);
      }

      $aggregates = $this->aggregator->getAggregates($tenantId, $period, 30);

      // Construir resumen de métricas.
      $metricsSummary = [];
      foreach ($aggregates as $aggregate) {
        $name = $aggregate->get('metric_name')->value;
        if (!isset($metricsSummary[$name])) {
          $metricsSummary[$name] = [
            'total_quantity' => 0.0,
            'total_events' => 0,
          ];
        }
        $metricsSummary[$name]['total_quantity'] += (float) $aggregate->get('total_quantity')->value;
        $metricsSummary[$name]['total_events'] += (int) $aggregate->get('event_count')->value;
      }

      // Calcular costes.
      $costBreakdown = [];
      $totalCost = 0.0;
      foreach ($metricsSummary as $metric => $data) {
        $cost = $this->pricing->calculateCost($metric, $data['total_quantity'], $tenantId);
        $costBreakdown[$metric] = [
          'quantity' => $data['total_quantity'],
          'events' => $data['total_events'],
          'cost' => $cost,
        ];
        $totalCost += $cost;
      }

      // Obtener alertas.
      $alerts = $this->alertService->checkThresholds($tenantId);

      // Construir datos de gráficos.
      $chartLabels = [];
      $chartDatasets = [];
      foreach ($aggregates as $aggregate) {
        $metric = $aggregate->get('metric_name')->value;
        $label = date('Y-m-d', (int) $aggregate->get('period_start')->value);

        if (!in_array($label, $chartLabels, TRUE)) {
          $chartLabels[] = $label;
        }

        if (!isset($chartDatasets[$metric])) {
          $chartDatasets[$metric] = [];
        }
        $chartDatasets[$metric][] = (float) $aggregate->get('total_quantity')->value;
      }

      sort($chartLabels);

      return new JsonResponse([
        'status' => 'ok',
        'tenant_id' => $tenantId,
        'period' => $period,
        'metrics' => $metricsSummary,
        'cost_breakdown' => $costBreakdown,
        'total_cost' => round($totalCost, 4),
        'alerts' => $alerts,
        'chart' => [
          'labels' => $chartLabels,
          'datasets' => $chartDatasets,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en API de dashboard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Internal server error.',
      ], 500);
    }
  }

}
