<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Queue;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_messaging\Service\RetentionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes GDPR retention cleanup in batches.
 *
 * @QueueWorker(
 *   id = "jaraba_messaging_retention_cleanup",
 *   title = @Translation("GDPR retention cleanup"),
 *   cron = {"time" = 60}
 * )
 */
class RetentionCleanupWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RetentionService $retentionService,
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
      $container->get('jaraba_messaging.retention'),
      $container->get('logger.channel.jaraba_messaging'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    try {
      $this->retentionService->runScheduledCleanup();
    }
    catch (\Throwable $e) {
      $this->logger->error('Retention cleanup failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
