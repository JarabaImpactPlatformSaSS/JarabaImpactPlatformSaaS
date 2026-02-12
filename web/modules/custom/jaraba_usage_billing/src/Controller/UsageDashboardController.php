<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_usage_billing\Entity\UsageAggregate;
use Drupal\jaraba_usage_billing\Service\UsageAggregatorService;
use Drupal\jaraba_usage_billing\Service\UsageAlertService;
use Drupal\jaraba_usage_billing\Service\UsagePricingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del dashboard de uso.
 *
 * Presenta la visualización del consumo de recursos del tenant
 * con gráficos Chart.js, tarjetas de métricas y alertas.
 */
class UsageDashboardController extends ControllerBase {

  public function __construct(
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
      $container->get('jaraba_usage_billing.aggregator'),
      $container->get('jaraba_usage_billing.pricing'),
      $container->get('jaraba_usage_billing.alert'),
      $container->get('logger.channel.jaraba_usage_billing'),
    );
  }

  /**
   * Render del dashboard de uso.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP actual.
   *
   * @return array
   *   Render array con los datos del dashboard.
   */
  public function dashboard(Request $request): array {
    try {
      // Determinar tenant del contexto del usuario.
      $tenantId = (int) $request->query->get('tenant_id', '0');
      $period = $request->query->get('period', 'monthly');

      // Obtener agregados.
      $aggregates = [];
      if ($tenantId > 0) {
        $aggregates = $this->aggregator->getAggregates($tenantId, $period, 30);
      }

      // Preparar datos para gráficos.
      $chartData = $this->buildChartData($aggregates);

      // Obtener métricas de resumen.
      $metrics = $this->buildMetricsSummary($aggregates);

      // Calcular costes.
      $costSummary = [];
      foreach ($metrics as $metricName => $metricData) {
        $cost = $this->pricing->calculateCost(
          $metricName,
          $metricData['total_quantity'],
          $tenantId > 0 ? $tenantId : NULL
        );
        $costSummary[$metricName] = [
          'quantity' => $metricData['total_quantity'],
          'cost' => $cost,
        ];
      }

      // Verificar alertas.
      $alerts = $tenantId > 0
        ? $this->alertService->checkThresholds($tenantId)
        : [];

      return [
        '#theme' => 'usage_dashboard',
        '#tenant_id' => $tenantId,
        '#metrics' => $metrics,
        '#aggregates' => $aggregates,
        '#alerts' => $alerts,
        '#chart_data' => $chartData,
        '#period' => $period,
        '#cost_summary' => $costSummary,
        '#attached' => [
          'library' => ['jaraba_usage_billing/usage-dashboard'],
          'drupalSettings' => [
            'jarabaUsageBilling' => [
              'tenantId' => $tenantId,
              'period' => $period,
              'chartData' => $chartData,
              'costSummary' => $costSummary,
              'alerts' => $alerts,
            ],
          ],
        ],
        '#cache' => [
          'max-age' => 300,
          'contexts' => ['user', 'url.query_args'],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error renderizando dashboard de uso: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        '#markup' => $this->t('Error al cargar el dashboard de uso. Por favor, inténtelo de nuevo.'),
      ];
    }
  }

  /**
   * Construye datos para los gráficos Chart.js.
   *
   * @param array $aggregates
   *   Entidades UsageAggregate.
   *
   * @return array
   *   Datos estructurados para Chart.js.
   */
  protected function buildChartData(array $aggregates): array {
    $labels = [];
    $datasets = [];

    foreach ($aggregates as $aggregate) {
      $metric = $aggregate->get('metric_name')->value;
      $periodStart = (int) $aggregate->get('period_start')->value;
      $label = date('Y-m-d', $periodStart);
      $quantity = (float) $aggregate->get('total_quantity')->value;

      if (!in_array($label, $labels, TRUE)) {
        $labels[] = $label;
      }

      if (!isset($datasets[$metric])) {
        $datasets[$metric] = [
          'label' => $metric,
          'data' => [],
        ];
      }

      $datasets[$metric]['data'][] = $quantity;
    }

    // Ordenar labels cronológicamente.
    sort($labels);

    return [
      'labels' => $labels,
      'datasets' => array_values($datasets),
    ];
  }

  /**
   * Construye resumen de métricas por nombre.
   *
   * @param array $aggregates
   *   Entidades UsageAggregate.
   *
   * @return array
   *   Resumen indexado por metric_name.
   */
  protected function buildMetricsSummary(array $aggregates): array {
    $metrics = [];

    foreach ($aggregates as $aggregate) {
      $name = $aggregate->get('metric_name')->value;
      if (!isset($metrics[$name])) {
        $metrics[$name] = [
          'total_quantity' => 0.0,
          'total_events' => 0,
          'periods' => 0,
        ];
      }

      $metrics[$name]['total_quantity'] += (float) $aggregate->get('total_quantity')->value;
      $metrics[$name]['total_events'] += (int) $aggregate->get('event_count')->value;
      $metrics[$name]['periods']++;
    }

    return $metrics;
  }

}
