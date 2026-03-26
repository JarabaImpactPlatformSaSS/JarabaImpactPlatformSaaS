<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\PredictiveIntegrationService;
use Drupal\jaraba_billing\Service\RevenueMetricsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Revenue dashboard controller — MRR/ARR/churn KPIs.
 *
 * GAP-REVENUE-DASH: Admin-only dashboard for SaaS revenue metrics.
 * CONTROLLER-READONLY-001: No readonly on inherited entityTypeManager.
 * ZERO-REGION-001: Controller returns minimal markup; data via drupalSettings.
 */
class RevenueDashboardController extends ControllerBase {

  /**
   * Revenue metrics service.
   */
  protected RevenueMetricsService $revenueMetrics;

  /**
   * Predictive integration service.
   */
  protected ?PredictiveIntegrationService $predictive;

  /**
   * Constructor.
   */
  public function __construct(
    RevenueMetricsService $revenueMetrics,
    ?PredictiveIntegrationService $predictive = NULL,
  ) {
    $this->revenueMetrics = $revenueMetrics;
    $this->predictive = $predictive;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.revenue_metrics'),
      $container->has('ecosistema_jaraba_core.predictive_integration')
        ? $container->get('ecosistema_jaraba_core.predictive_integration')
        : NULL,
    );
  }

  /**
   * Renders the revenue dashboard page.
   *
   * @return array
   *   Render array with drupalSettings for Chart.js frontend.
   */
  public function page(): array {
    $snapshot = $this->revenueMetrics->getDashboardSnapshot();

    // Predictive intelligence — AI-COVERAGE-001.
    $churnPredictions = [];
    $revenueForecast = [];
    $highRiskTenants = [];
    if ($this->predictive !== NULL) {
      try {
        $revenueForecast = $this->predictive->getRevenueForecast('mrr', 'monthly');
        $highRiskTenants = $this->predictive->getHighRiskTenants(5);
      }
      catch (\Throwable) {
        // Predictive data is enhancement, not critical.
      }
    }

    return [
      '#theme' => 'revenue_dashboard',
      '#snapshot' => $snapshot,
      '#churn_predictions' => $churnPredictions,
      '#revenue_forecast' => $revenueForecast,
      '#high_risk_tenants' => $highRiskTenants,
      '#attached' => [
        'library' => ['jaraba_billing/revenue-dashboard'],
        'drupalSettings' => [
          'revenueDashboard' => $snapshot,
          'predictiveData' => [
            'revenue_forecast' => $revenueForecast,
            'high_risk_tenants' => $highRiskTenants,
          ],
        ],
      ],
    ];
  }

  /**
   * API endpoint: returns revenue snapshot as JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function apiSnapshot(): JsonResponse {
    $snapshot = $this->revenueMetrics->getDashboardSnapshot();
    return new JsonResponse(['data' => $snapshot]);
  }

}
