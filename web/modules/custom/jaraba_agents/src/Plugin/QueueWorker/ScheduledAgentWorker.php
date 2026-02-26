<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_agents\Service\AgentOrchestratorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa ejecuciones programadas de agentes autonomos (GAP-05/FIX-041).
 *
 * Estructura: Cada item de cola contiene un agent_id. El worker delega
 *             la ejecucion a AgentOrchestratorService y actualiza los
 *             campos last_run/next_run del agente tras la ejecucion.
 *
 * Logica: Despues de ejecutar (exitosa o no), se registra last_run
 *         con el timestamp actual y se calcula next_run mediante
 *         AutonomousAgent::calculateNextRun(). Para agentes 'one_time',
 *         next_run queda en NULL impidiendo futuras ejecuciones.
 *
 * @QueueWorker(
 *   id = "jaraba_agents_scheduled_agent",
 *   title = @Translation("Scheduled Agent Execution"),
 *   cron = {"time" = 60}
 * )
 */
class ScheduledAgentWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AgentOrchestratorService $orchestrator,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('jaraba_agents.orchestrator'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_agents'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!isset($data['agent_id'])) {
      $this->logger->warning('Scheduled agent queue item missing required agent_id field.');
      return;
    }

    $agentId = (int) $data['agent_id'];
    $triggerData = $data['trigger_data'] ?? [];

    $this->logger->info('GAP-05: Procesando ejecucion programada para agente @id.', [
      '@id' => $agentId,
    ]);

    try {
      $result = $this->orchestrator->execute($agentId, 'schedule', $triggerData);

      if ($result['success']) {
        $this->logger->info('GAP-05: Agente @id ejecutado correctamente. Execution ID: @exec_id.', [
          '@id' => $agentId,
          '@exec_id' => $result['execution_id'] ?? 'N/A',
        ]);
      }
      else {
        $this->logger->error('GAP-05: Ejecucion del agente @id fallida: @error', [
          '@id' => $agentId,
          '@error' => $result['error'] ?? 'Error desconocido',
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('GAP-05: Excepcion durante ejecucion programada del agente @id: @msg', [
        '@id' => $agentId,
        '@msg' => $e->getMessage(),
      ]);
    }

    // GAP-05: Actualizar last_run y calcular next_run SIEMPRE,
    // independientemente del resultado de ejecucion. Un fallo no debe
    // bloquear el ciclo de programacion (se reintentara en el siguiente).
    $this->updateScheduleTimestamps($agentId);
  }

  /**
   * Actualiza last_run y next_run en el agente tras la ejecucion.
   *
   * Estructura: Carga el agente, establece last_run = ahora,
   *             calcula next_run via calculateNextRun() y guarda.
   * Logica: PRESAVE-RESILIENCE-001 — try-catch para que fallos en
   *         la actualizacion de timestamps no rompan el worker.
   *
   * @param int $agentId
   *   ID del agente autonomo.
   */
  protected function updateScheduleTimestamps(int $agentId): void {
    try {
      $agent = $this->entityTypeManager->getStorage('autonomous_agent')->load($agentId);
      if (!$agent) {
        return;
      }

      /** @var \Drupal\jaraba_agents\Entity\AutonomousAgent $agent */
      $now = \Drupal::time()->getRequestTime();
      $agent->set('last_run', $now);

      $nextRun = $agent->calculateNextRun();
      $agent->set('next_run', $nextRun);
      $agent->save();

      $this->logger->debug('GAP-05: Timestamps actualizados para agente @id — last_run=@last, next_run=@next.', [
        '@id' => $agentId,
        '@last' => date('Y-m-d H:i:s', $now),
        '@next' => $nextRun ? date('Y-m-d H:i:s', $nextRun) : 'NULL (one-time completado)',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('GAP-05: Error actualizando timestamps del agente @id: @msg', [
        '@id' => $agentId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
