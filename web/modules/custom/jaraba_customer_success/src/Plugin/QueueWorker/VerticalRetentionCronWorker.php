<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for vertical retention cron operations.
 *
 * @QueueWorker(
 *   id = "jaraba_vertical_retention_cron",
 *   title = @Translation("Vertical Retention cron operations"),
 *   cron = {"time" = 120}
 * )
 */
class VerticalRetentionCronWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected VerticalRetentionService $retentionService,
    protected SeasonalChurnService $seasonalChurnService,
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
      $container->get('jaraba_customer_success.vertical_retention'),
      $container->get('jaraba_customer_success.seasonal_churn'),
      $container->get('logger.channel.jaraba_customer_success'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $operation = $data['operation'] ?? '';

    match ($operation) {
      'vertical_evaluation' => $this->retentionService->runBatchEvaluation(),
      'seasonal_predictions' => $this->seasonalChurnService->runMonthlyPredictions(),
      default => $this->logger->warning('Unknown vertical retention operation: @op', ['@op' => $operation]),
    };
  }

}
