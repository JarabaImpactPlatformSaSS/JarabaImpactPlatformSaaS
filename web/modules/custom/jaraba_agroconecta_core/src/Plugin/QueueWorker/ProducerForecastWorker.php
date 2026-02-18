<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_agroconecta_core\Service\DemandForecasterService;
use Drupal\jaraba_agroconecta_core\Service\MarketSpyService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AUDIT-PERF-N11: Processes producer forecast and price alerts asynchronously.
 *
 * Replaces N+1 synchronous loops in jaraba_agroconecta_core_cron().
 * Each queue item represents one producer to process.
 *
 * @QueueWorker(
 *   id = "jaraba_agroconecta_producer_forecast",
 *   title = @Translation("Producer forecast and price alert processing"),
 *   cron = {"time" = 60}
 * )
 */
class ProducerForecastWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected DemandForecasterService $forecaster,
    protected MarketSpyService $marketSpy,
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
      $container->get('jaraba_agroconecta_core.demand_forecaster'), // AUDIT-CONS-N05: canonical prefix
      $container->get('jaraba_agroconecta_core.market_spy'), // AUDIT-CONS-N05: canonical prefix
      $container->get('logger.channel.jaraba_agroconecta_core'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $producerId = (int) ($data['producer_id'] ?? 0);
    $operation = $data['operation'] ?? '';

    if ($producerId <= 0) {
      return;
    }

    switch ($operation) {
      case 'forecast':
        $this->forecaster->getDemandTrends($producerId, 3);
        $this->logger->info('QueueWorker: Demand forecast updated for producer @id.', [
          '@id' => $producerId,
        ]);
        break;

      case 'price_alerts':
        $alerts = $this->marketSpy->getPriceAlerts($producerId);
        if (!empty($alerts)) {
          $this->logger->info('QueueWorker: @count price alerts for producer @id.', [
            '@count' => count($alerts),
            '@id' => $producerId,
          ]);
        }
        break;
    }
  }

}
