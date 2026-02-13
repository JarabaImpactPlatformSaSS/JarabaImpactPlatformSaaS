<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_customer_success\Service\HealthScoreCalculatorService;
use Drupal\jaraba_customer_success\Service\PlaybookExecutorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AUDIT-PERF-N11: Processes customer success operations asynchronously.
 *
 * Replaces synchronous processing in jaraba_customer_success_cron()
 * for health score calculation and playbook execution per tenant.
 *
 * @QueueWorker(
 *   id = "jaraba_customer_success_cron_worker",
 *   title = @Translation("Customer Success cron operations"),
 *   cron = {"time" = 60}
 * )
 */
class CustomerSuccessCronWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HealthScoreCalculatorService $healthCalculator,
    protected PlaybookExecutorService $playbookExecutor,
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
      $container->get('jaraba_customer_success.health_calculator'),
      $container->get('jaraba_customer_success.playbook_executor'),
      $container->get('logger.channel.jaraba_customer_success'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $operation = $data['operation'] ?? '';

    switch ($operation) {
      case 'health_scores':
        $processed = $this->healthCalculator->runScheduledCalculation();
        if ($processed > 0) {
          $this->logger->info('QueueWorker: @count health scores calculated.', [
            '@count' => $processed,
          ]);
        }
        break;

      case 'evaluate_triggers':
        $started = $this->playbookExecutor->evaluateTriggers();
        if ($started > 0) {
          $this->logger->info('QueueWorker: @count playbook executions started.', [
            '@count' => $started,
          ]);
        }
        break;

      case 'advance_executions':
        $advanced = $this->playbookExecutor->advancePendingExecutions();
        if ($advanced > 0) {
          $this->logger->info('QueueWorker: @count playbook steps advanced.', [
            '@count' => $advanced,
          ]);
        }
        break;

      default:
        $this->logger->warning('QueueWorker: unknown operation @op.', [
          '@op' => $operation,
        ]);
    }
  }

}
