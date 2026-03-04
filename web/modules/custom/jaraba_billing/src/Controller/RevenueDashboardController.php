<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
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
   * Constructor.
   */
  public function __construct(
    RevenueMetricsService $revenueMetrics,
  ) {
    $this->revenueMetrics = $revenueMetrics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.revenue_metrics'),
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

    return [
      '#theme' => 'revenue_dashboard',
      '#snapshot' => $snapshot,
      '#attached' => [
        'library' => ['jaraba_billing/revenue-dashboard'],
        'drupalSettings' => [
          'revenueDashboard' => $snapshot,
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
