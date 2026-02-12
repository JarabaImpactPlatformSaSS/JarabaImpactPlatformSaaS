<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_usage_billing\Service\UsageAggregatorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa items de la cola de agregación de uso.
 *
 * @QueueWorker(
 *   id = "jaraba_usage_billing_aggregation",
 *   title = @Translation("Agregación de Uso"),
 *   cron = {"time" = 120}
 * )
 */
class UsageAggregationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected UsageAggregatorService $aggregator,
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
      $container->get('jaraba_usage_billing.aggregator'),
      $container->get('logger.channel.jaraba_usage_billing'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    try {
      $tenantId = $data['tenant_id'] ?? NULL;
      $periodType = $data['period_type'] ?? 'daily';

      switch ($periodType) {
        case 'hourly':
          $count = $this->aggregator->aggregateHourly($tenantId);
          break;

        case 'daily':
          $count = $this->aggregator->aggregateDaily($tenantId);
          break;

        case 'monthly':
          $count = $this->aggregator->aggregateMonthly($tenantId);
          break;

        default:
          $this->logger->warning('Tipo de periodo desconocido en cola: @type', [
            '@type' => $periodType,
          ]);
          return;
      }

      $this->logger->info('Cola de agregación procesada: @count agregados (@period, tenant: @tenant).', [
        '@count' => $count,
        '@period' => $periodType,
        '@tenant' => $tenantId ?? 'todos',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando item de cola de agregación: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
