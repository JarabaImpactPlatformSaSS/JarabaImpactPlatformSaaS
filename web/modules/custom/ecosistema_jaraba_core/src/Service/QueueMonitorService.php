<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Monitors queue status and collects metrics from all QueueWorkers.
 *
 * GAP-QUEUE-MON: Provides visibility into 31+ queue workers for
 * the admin queue monitoring dashboard.
 */
class QueueMonitorService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly QueueWorkerManagerInterface $queueWorkerManager,
    protected readonly QueueFactory $queueFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets metrics for all registered queue workers.
   *
   * @return array
   *   Array of queue metrics keyed by queue ID.
   */
  public function getAllQueueMetrics(): array {
    $metrics = [];
    $definitions = $this->queueWorkerManager->getDefinitions();

    foreach ($definitions as $queueId => $definition) {
      try {
        $queue = $this->queueFactory->get($queueId);
        $itemCount = $queue->numberOfItems();

        $metrics[$queueId] = [
          'id' => $queueId,
          'label' => $definition['title'] ?? $queueId,
          'pending' => $itemCount,
          'cron_time' => $definition['cron']['time'] ?? 0,
          'module' => $definition['provider'] ?? 'unknown',
          'status' => $this->determineStatus($itemCount),
        ];
      }
      catch (\Exception $e) {
        $metrics[$queueId] = [
          'id' => $queueId,
          'label' => $definition['title'] ?? $queueId,
          'pending' => -1,
          'cron_time' => $definition['cron']['time'] ?? 0,
          'module' => $definition['provider'] ?? 'unknown',
          'status' => 'error',
          'error' => $e->getMessage(),
        ];
      }
    }

    // Sort by pending items descending.
    uasort($metrics, fn(array $a, array $b) => $b['pending'] <=> $a['pending']);

    return $metrics;
  }

  /**
   * Gets summary statistics across all queues.
   *
   * @return array
   *   Summary with totals and alerts.
   */
  public function getSummary(): array {
    $metrics = $this->getAllQueueMetrics();

    $totalPending = 0;
    $totalQueues = count($metrics);
    $errorQueues = 0;
    $warningQueues = 0;
    $healthyQueues = 0;

    foreach ($metrics as $queue) {
      if ($queue['status'] === 'error') {
        $errorQueues++;
      }
      elseif ($queue['status'] === 'warning') {
        $warningQueues++;
      }
      else {
        $healthyQueues++;
      }

      if ($queue['pending'] > 0) {
        $totalPending += $queue['pending'];
      }
    }

    return [
      'total_queues' => $totalQueues,
      'total_pending' => $totalPending,
      'healthy' => $healthyQueues,
      'warning' => $warningQueues,
      'error' => $errorQueues,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
  }

  /**
   * Determines queue health status based on pending item count.
   *
   * @param int $itemCount
   *   Number of pending items.
   *
   * @return string
   *   Status: 'healthy', 'warning', or 'critical'.
   */
  protected function determineStatus(int $itemCount): string {
    if ($itemCount < 0) {
      return 'error';
    }
    if ($itemCount > 1000) {
      return 'critical';
    }
    if ($itemCount > 100) {
      return 'warning';
    }
    return 'healthy';
  }

}
