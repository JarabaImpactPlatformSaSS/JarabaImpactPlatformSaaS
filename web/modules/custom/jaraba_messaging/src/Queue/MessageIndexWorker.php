<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Queue;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes messages for search index (Qdrant semantic indexing).
 *
 * @QueueWorker(
 *   id = "jaraba_messaging_message_index",
 *   title = @Translation("Message search indexing"),
 *   cron = {"time" = 30}
 * )
 */
class MessageIndexWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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
      $container->get('logger.channel.jaraba_messaging'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (empty($data['message_id']) || empty($data['tenant_id'])) {
      return;
    }

    // Skip confidential messages — GDPR compliance.
    if (!empty($data['is_confidential'])) {
      $this->logger->debug('Skipping confidential message @id from search index.', [
        '@id' => $data['message_id'],
      ]);
      return;
    }

    // Future: Send to Qdrant for semantic vector indexing.
    // For now, log the indexing attempt as a placeholder.
    $this->logger->debug('Message @id queued for search indexing (tenant @tenant).', [
      '@id' => $data['message_id'],
      '@tenant' => $data['tenant_id'],
    ]);
  }

}
