<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\Service\ReviewWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa entregas de webhooks de resenas.
 *
 * Los items son encolados por ReviewWebhookService::dispatch().
 *
 * @QueueWorker(
 *   id = "review_webhook_delivery",
 *   title = @Translation("Entrega de Webhooks de Resenas"),
 *   cron = {"time" = 30}
 * )
 */
class ReviewWebhookDeliveryWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly ReviewWebhookService $webhookService,
    protected readonly LoggerInterface $logger,
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
      $container->get('ecosistema_jaraba_core.review_webhook'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!is_array($data) || empty($data['url'])) {
      $this->logger->warning('Invalid webhook delivery queue item — missing url.');
      return;
    }

    $this->webhookService->deliver($data);
  }

}
