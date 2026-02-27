<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GAP-L5-F: Processes autonomous agent heartbeat cycles.
 *
 * Each queue item represents one heartbeat for an active session.
 * Heartbeats are enqueued by AutonomousAgentService::enqueueHeartbeats()
 * which runs on cron. The worker delegates to AutonomousAgentService
 * for the actual execution logic.
 *
 * @QueueWorker(
 *   id = "autonomous_agent_heartbeat",
 *   title = @Translation("Autonomous Agent Heartbeat Worker"),
 *   cron = {"time" = 120}
 * )
 */
class AutonomousAgentHeartbeatWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerInterface $logger,
    protected ?object $autonomousAgent = NULL,
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
      $container->get('logger.channel.jaraba_ai_agents'),
      $container->has('jaraba_ai_agents.autonomous_agent')
        ? $container->get('jaraba_ai_agents.autonomous_agent')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $sessionId = $data['session_id'] ?? 0;

    if ($sessionId <= 0) {
      $this->logger->warning('GAP-L5-F: Heartbeat item missing session_id.');
      return;
    }

    if ($this->autonomousAgent === NULL) {
      $this->logger->warning('GAP-L5-F: AutonomousAgentService not available, skipping heartbeat for session @id.', [
        '@id' => $sessionId,
      ]);
      return;
    }

    try {
      $result = $this->autonomousAgent->executeHeartbeat($sessionId);

      $this->logger->info('GAP-L5-F: Heartbeat for session @id: @status.', [
        '@id' => $sessionId,
        '@status' => $result['status'] ?? 'unknown',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('GAP-L5-F: Heartbeat worker failed for session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
