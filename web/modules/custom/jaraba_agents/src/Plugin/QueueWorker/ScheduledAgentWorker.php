<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_agents\Service\AgentOrchestratorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes scheduled agent executions (FIX-041).
 *
 * Each queue item contains an agent_id. The worker delegates execution
 * to AgentOrchestratorService with trigger_type='schedule'.
 *
 * @QueueWorker(
 *   id = "jaraba_agents_scheduled_agent",
 *   title = @Translation("Scheduled Agent Execution"),
 *   cron = {"time" = 60}
 * )
 */
class ScheduledAgentWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected AgentOrchestratorService $orchestrator,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('jaraba_agents.orchestrator'),
            $container->get('logger.channel.jaraba_agents'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem($data): void
    {
        if (!isset($data['agent_id'])) {
            $this->logger->warning('Scheduled agent queue item missing required agent_id field.');
            return;
        }

        $agentId = (int) $data['agent_id'];
        $triggerData = $data['trigger_data'] ?? [];

        $this->logger->info('Processing scheduled execution for agent @id.', [
            '@id' => $agentId,
        ]);

        try {
            $result = $this->orchestrator->execute($agentId, 'schedule', $triggerData);

            if ($result['success']) {
                $this->logger->info('Scheduled agent @id executed successfully. Execution ID: @exec_id.', [
                    '@id' => $agentId,
                    '@exec_id' => $result['execution_id'] ?? 'N/A',
                ]);
            }
            else {
                $this->logger->error('Scheduled agent @id execution failed: @error', [
                    '@id' => $agentId,
                    '@error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Exception during scheduled execution of agent @id: @msg', [
                '@id' => $agentId,
                '@msg' => $e->getMessage(),
            ]);
        }
    }

}
