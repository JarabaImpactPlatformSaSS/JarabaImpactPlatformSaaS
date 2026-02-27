<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\ReviewAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Dashboard admin de analiticas de resenas.
 *
 * B-05: Review Analytics Dashboard.
 * Pagina: /admin/reports/reviews
 */
class ReviewAnalyticsDashboardController extends ControllerBase {

  public function __construct(
    protected readonly ReviewAnalyticsService $analyticsService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.review_analytics'),
    );
  }

  /**
   * Pagina del dashboard de analiticas de resenas.
   */
  public function dashboard(Request $request): array {
    $vertical = $request->query->get('vertical');
    $days = min(365, max(7, (int) $request->query->get('days', 30)));

    $metrics = $this->analyticsService->getDashboardMetrics($vertical, NULL, $days);

    return [
      '#theme' => 'review_analytics_dashboard',
      '#metrics' => $metrics,
      '#vertical_filter' => $vertical,
      '#days_filter' => $days,
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/review-analytics'],
      ],
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['url.query_args:vertical', 'url.query_args:days'],
      ],
    ];
  }

}
