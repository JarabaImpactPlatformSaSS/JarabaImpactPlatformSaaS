<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_analytics\Service\AnalyticsAggregatorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AUDIT-PERF-N11: Processes daily analytics aggregation asynchronously.
 *
 * Replaces synchronous processing in jaraba_analytics_cron().
 * Each queue item represents the full daily aggregation job.
 *
 * @QueueWorker(
 *   id = "jaraba_analytics_daily_aggregation",
 *   title = @Translation("Daily analytics metrics aggregation"),
 *   cron = {"time" = 120}
 * )
 */
class DailyAggregationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AnalyticsAggregatorService $aggregator,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_analytics.analytics_aggregator'),
      $container->get('logger.channel.jaraba_analytics'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $date = $data['date'] ?? date('Y-m-d', strtotime('-1 day'));

    try {
      $this->aggregator->aggregateDailyMetrics($date);
      $this->logger->info('QueueWorker: Daily aggregation completed for @date.', [
        '@date' => $date,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('QueueWorker: Daily aggregation failed for @date: @msg', [
        '@date' => $date,
        '@msg' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
