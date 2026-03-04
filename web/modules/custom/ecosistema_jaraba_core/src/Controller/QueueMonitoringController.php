<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\QueueMonitorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Queue Monitoring Dashboard controller.
 *
 * GAP-QUEUE-MON: Admin dashboard to monitor 31+ queue workers,
 * view pending items, and detect stuck/failed queues.
 */
class QueueMonitoringController extends ControllerBase {

  /**
   * Constructor.
   *
   * CONTROLLER-READONLY-001: Do not use readonly for $entityTypeManager.
   */
  public function __construct(
    protected readonly QueueMonitorService $queueMonitor,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.queue_monitor'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * Renders the queue monitoring dashboard.
   *
   * @return array
   *   Render array for the dashboard page.
   */
  public function dashboard(): array {
    $summary = $this->queueMonitor->getSummary();
    $queues = $this->queueMonitor->getAllQueueMetrics();

    // Group queues by module.
    $byModule = [];
    foreach ($queues as $queue) {
      $module = $queue['module'];
      $byModule[$module][] = $queue;
    }
    ksort($byModule);

    return [
      '#theme' => 'queue_monitoring_dashboard',
      '#summary' => $summary,
      '#queues' => $queues,
      '#queues_by_module' => $byModule,
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/queue-monitoring'],
      ],
    ];
  }

  /**
   * Returns queue metrics as JSON (for AJAX refresh).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with all queue metrics.
   */
  public function queueApi(): JsonResponse {
    return new JsonResponse([
      'summary' => $this->queueMonitor->getSummary(),
      'queues' => array_values($this->queueMonitor->getAllQueueMetrics()),
    ]);
  }

}
