<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Entity\AgentFlow;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de triggers para flujos de agentes IA.
 *
 * PROPOSITO:
 * Gestiona la activacion automatica de flujos basada en diferentes
 * mecanismos de trigger: cron (programados), webhooks y eventos.
 *
 * USO:
 * @code
 * // En hook_cron o Drush command:
 * $this->triggerService->processScheduledFlows();
 *
 * // Desde webhook controller:
 * $this->triggerService->processWebhookTrigger('abc123', $payload);
 * @endcode
 */
class AgentFlowTriggerService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowExecutionService $executionService
   *   El servicio de ejecucion de flujos.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AgentFlowExecutionService $executionService,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Procesa flujos programados que deben ejecutarse.
   *
   * Busca flujos activos con trigger_type = 'cron' y evalua si
   * deben ejecutarse segun su configuracion de cron (intervalo,
   * proxima ejecucion).
   */
  public function processScheduledFlows(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('flow_status', AgentFlow::STATUS_ACTIVE)
        ->condition('trigger_type', AgentFlow::TRIGGER_CRON);
      $ids = $query->execute();

      if (empty($ids)) {
        return;
      }

      $flows = $storage->loadMultiple($ids);
      $now = \Drupal::time()->getRequestTime();

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow $flow */
      foreach ($flows as $flow) {
        $triggerConfig = $flow->getDecodedTriggerConfig();
        $intervalSeconds = $triggerConfig['interval_seconds'] ?? 3600;
        $lastExecution = (int) ($flow->get('last_execution')->value ?? 0);

        // Verificar si ha pasado suficiente tiempo desde la ultima ejecucion.
        if (($now - $lastExecution) >= $intervalSeconds) {
          $this->logger->info('Ejecutando flujo programado @flow (intervalo: @interval s)', [
            '@flow' => $flow->id(),
            '@interval' => $intervalSeconds,
          ]);

          $executionId = $this->executionService->executeFlow((int) $flow->id());

          if ($executionId) {
            $this->logger->info('Flujo programado @flow ejecutado: ejecucion @exec', [
              '@flow' => $flow->id(),
              '@exec' => $executionId,
            ]);
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al procesar flujos programados: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Procesa un trigger de webhook.
   *
   * Busca flujos activos con trigger_type = 'webhook' que coincidan
   * con el webhook_id proporcionado y los ejecuta.
   *
   * @param string $webhookId
   *   Identificador del webhook recibido.
   * @param array $payload
   *   Datos del payload del webhook.
   */
  public function processWebhookTrigger(string $webhookId, array $payload): void {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('flow_status', AgentFlow::STATUS_ACTIVE)
        ->condition('trigger_type', AgentFlow::TRIGGER_WEBHOOK);
      $ids = $query->execute();

      if (empty($ids)) {
        $this->logger->notice('No se encontraron flujos para webhook @webhook', [
          '@webhook' => $webhookId,
        ]);
        return;
      }

      $flows = $storage->loadMultiple($ids);

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow $flow */
      foreach ($flows as $flow) {
        $triggerConfig = $flow->getDecodedTriggerConfig();
        $configuredWebhookId = $triggerConfig['webhook_id'] ?? '';

        if ($configuredWebhookId === $webhookId) {
          $this->logger->info('Webhook @webhook coincide con flujo @flow, ejecutando...', [
            '@webhook' => $webhookId,
            '@flow' => $flow->id(),
          ]);

          $executionId = $this->executionService->executeFlow((int) $flow->id());

          if ($executionId) {
            $this->logger->info('Flujo @flow ejecutado por webhook @webhook: ejecucion @exec', [
              '@flow' => $flow->id(),
              '@webhook' => $webhookId,
              '@exec' => $executionId,
            ]);
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al procesar webhook @webhook: @message', [
        '@webhook' => $webhookId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
