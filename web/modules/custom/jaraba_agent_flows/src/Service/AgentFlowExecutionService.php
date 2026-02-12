<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Entity\AgentFlow;
use Drupal\jaraba_agent_flows\Entity\AgentFlowExecution;
use Drupal\jaraba_agent_flows\Entity\AgentFlowStepLog;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal de ejecucion de flujos de agentes IA.
 *
 * PROPOSITO:
 * Orquesta la ejecucion de un AgentFlow creando entidades de ejecucion,
 * delegando al workflow_executor de jaraba_ai_agents y registrando
 * cada paso en AgentFlowStepLog.
 *
 * USO:
 * @code
 * $executionId = $this->executionService->executeFlow(42);
 * $result = $this->executionService->getExecutionResult($executionId);
 * @endcode
 */
class AgentFlowExecutionService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param object $workflowExecutor
   *   El servicio de ejecucion de workflows de jaraba_ai_agents.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $workflowExecutor,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Ejecuta un flujo de agente por su ID.
   *
   * Crea una entidad AgentFlowExecution, delega la ejecucion al
   * workflow executor, registra pasos en AgentFlowStepLog y
   * actualiza el estado de la ejecucion al finalizar.
   *
   * @param int $flowId
   *   ID del flujo de agente a ejecutar.
   *
   * @return int|null
   *   ID de la ejecucion creada, o NULL si el flujo no existe o falla.
   */
  public function executeFlow(int $flowId): ?int {
    try {
      $flowStorage = $this->entityTypeManager->getStorage('agent_flow');

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow|null $flow */
      $flow = $flowStorage->load($flowId);

      if (!$flow) {
        $this->logger->warning('Flujo de agente no encontrado: @id', ['@id' => $flowId]);
        return NULL;
      }

      // Solo ejecutar flujos activos.
      if ($flow->get('flow_status')->value !== AgentFlow::STATUS_ACTIVE) {
        $this->logger->notice('Intento de ejecutar flujo no activo: @id (estado: @status)', [
          '@id' => $flowId,
          '@status' => $flow->get('flow_status')->value,
        ]);
        return NULL;
      }

      $startTime = (int) (microtime(TRUE) * 1000);
      $now = \Drupal::time()->getRequestTime();

      // Crear entidad de ejecucion.
      $executionStorage = $this->entityTypeManager->getStorage('agent_flow_execution');

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution $execution */
      $execution = $executionStorage->create([
        'flow_id' => $flowId,
        'execution_status' => AgentFlowExecution::STATUS_RUNNING,
        'started_at' => $now,
        'triggered_by' => 'manual',
        'tenant_id' => $flow->get('tenant_id')->target_id,
      ]);
      $execution->save();

      $executionId = (int) $execution->id();

      // Obtener configuracion del flujo.
      $flowConfig = $flow->getDecodedFlowConfig();
      $steps = $flowConfig['steps'] ?? [];

      // Ejecutar cada paso.
      $stepResults = [];
      $overallSuccess = TRUE;

      foreach ($steps as $order => $stepDef) {
        $stepStartTime = (int) (microtime(TRUE) * 1000);
        $stepName = $stepDef['name'] ?? 'step_' . $order;
        $stepType = $stepDef['type'] ?? 'unknown';
        $stepInput = $stepDef['params'] ?? [];

        // Inyectar resultado del paso anterior como input.
        if (!empty($stepResults)) {
          $stepInput['previous_output'] = end($stepResults);
        }

        try {
          // Delegar al workflow executor.
          $stepOutput = $this->workflowExecutor->executeStep($stepType, $stepInput);
          $stepDuration = (int) (microtime(TRUE) * 1000) - $stepStartTime;
          $stepResults[] = $stepOutput;

          // Registrar paso exitoso.
          $this->logStep($executionId, $stepName, $stepType, $order, $stepInput, $stepOutput, $stepDuration, AgentFlowStepLog::STATUS_SUCCESS);
        }
        catch (\Exception $stepException) {
          $stepDuration = (int) (microtime(TRUE) * 1000) - $stepStartTime;
          $overallSuccess = FALSE;

          // Registrar paso fallido.
          $this->logStep(
            $executionId,
            $stepName,
            $stepType,
            $order,
            $stepInput,
            [],
            $stepDuration,
            AgentFlowStepLog::STATUS_FAILED,
            $stepException->getMessage(),
          );

          $this->logger->error('Paso @step del flujo @flow fallo: @error', [
            '@step' => $stepName,
            '@flow' => $flowId,
            '@error' => $stepException->getMessage(),
          ]);

          // Abortar flujo en caso de error.
          break;
        }
      }

      // Actualizar ejecucion con resultado final.
      $endTime = (int) (microtime(TRUE) * 1000);
      $totalDuration = $endTime - $startTime;

      $execution->set('execution_status', $overallSuccess
        ? AgentFlowExecution::STATUS_COMPLETED
        : AgentFlowExecution::STATUS_FAILED);
      $execution->set('completed_at', \Drupal::time()->getRequestTime());
      $execution->set('duration_ms', $totalDuration);
      $execution->set('result', json_encode($stepResults, JSON_THROW_ON_ERROR));

      if (!$overallSuccess) {
        $execution->set('error_message', 'Uno o mas pasos del flujo fallaron.');
      }

      $execution->save();

      // Actualizar contadores del flujo.
      $currentCount = (int) ($flow->get('execution_count')->value ?? 0);
      $flow->set('execution_count', $currentCount + 1);
      $flow->set('last_execution', \Drupal::time()->getRequestTime());
      $flow->save();

      $this->logger->info('Flujo @flow ejecutado (ejecucion @exec): @status en @duration ms', [
        '@flow' => $flowId,
        '@exec' => $executionId,
        '@status' => $overallSuccess ? 'completado' : 'fallido',
        '@duration' => $totalDuration,
      ]);

      return $executionId;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al ejecutar flujo @flow: @message', [
        '@flow' => $flowId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene el resultado de una ejecucion.
   *
   * @param int $executionId
   *   ID de la ejecucion.
   *
   * @return array
   *   Array con los datos de la ejecucion o un array con 'error'.
   */
  public function getExecutionResult(int $executionId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow_execution');

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution|null $execution */
      $execution = $storage->load($executionId);

      if (!$execution) {
        return ['error' => 'Ejecucion no encontrada.'];
      }

      // Obtener pasos del log.
      $stepLogStorage = $this->entityTypeManager->getStorage('agent_flow_step_log');
      $stepIds = $stepLogStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('execution_id', $executionId)
        ->sort('step_order', 'ASC')
        ->execute();

      $steps = [];
      if (!empty($stepIds)) {
        $stepEntities = $stepLogStorage->loadMultiple($stepIds);
        foreach ($stepEntities as $step) {
          $steps[] = [
            'step_name' => $step->get('step_name')->value,
            'step_type' => $step->get('step_type')->value,
            'step_order' => (int) $step->get('step_order')->value,
            'step_status' => $step->get('step_status')->value,
            'duration_ms' => (int) ($step->get('duration_ms')->value ?? 0),
            'error_detail' => $step->get('error_detail')->value,
          ];
        }
      }

      return [
        'execution_id' => (int) $execution->id(),
        'flow_id' => $execution->get('flow_id')->target_id ? (int) $execution->get('flow_id')->target_id : NULL,
        'execution_status' => $execution->get('execution_status')->value,
        'started_at' => (int) ($execution->get('started_at')->value ?? 0),
        'completed_at' => (int) ($execution->get('completed_at')->value ?? 0),
        'duration_ms' => (int) ($execution->get('duration_ms')->value ?? 0),
        'result' => $execution->getDecodedResult(),
        'error_message' => $execution->get('error_message')->value,
        'steps' => $steps,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener resultado de ejecucion @id: @message', [
        '@id' => $executionId,
        '@message' => $e->getMessage(),
      ]);
      return ['error' => 'Error al obtener resultado de la ejecucion.'];
    }
  }

  /**
   * Registra un paso de ejecucion en AgentFlowStepLog.
   *
   * @param int $executionId
   *   ID de la ejecucion.
   * @param string $stepName
   *   Nombre del paso.
   * @param string $stepType
   *   Tipo de paso.
   * @param int $order
   *   Orden del paso.
   * @param array $input
   *   Datos de entrada.
   * @param array $output
   *   Datos de salida.
   * @param int $durationMs
   *   Duracion en milisegundos.
   * @param string $status
   *   Estado del paso.
   * @param string|null $errorDetail
   *   Detalle del error si fallo.
   */
  protected function logStep(
    int $executionId,
    string $stepName,
    string $stepType,
    int $order,
    array $input,
    array $output,
    int $durationMs,
    string $status,
    ?string $errorDetail = NULL,
  ): void {
    try {
      $stepLogStorage = $this->entityTypeManager->getStorage('agent_flow_step_log');
      $stepLog = $stepLogStorage->create([
        'execution_id' => $executionId,
        'step_name' => $stepName,
        'step_type' => $stepType,
        'step_order' => $order,
        'input_data' => json_encode($input, JSON_THROW_ON_ERROR),
        'output_data' => json_encode($output, JSON_THROW_ON_ERROR),
        'duration_ms' => $durationMs,
        'step_status' => $status,
        'error_detail' => $errorDetail,
      ]);
      $stepLog->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Error al registrar paso @step de ejecucion @exec: @message', [
        '@step' => $stepName,
        '@exec' => $executionId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
