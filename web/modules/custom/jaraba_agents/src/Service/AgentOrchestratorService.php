<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de orquestacion del ciclo de vida de agentes autonomos.
 *
 * ESTRUCTURA:
 *   Router central que gestiona la ejecucion de agentes IA,
 *   verificando guardrails, gestionando aprobaciones humanas y
 *   registrando metricas de rendimiento.
 *
 * LOGICA:
 *   Implementa 5 niveles de autonomia (L0-L4). Para niveles
 *   L2+ las acciones configuradas en requires_approval pasan por
 *   el flujo de aprobacion humana antes de ejecutarse.
 *   Transiciones de estado validas:
 *     running  -> completed, failed, paused, cancelled
 *     paused   -> running, cancelled
 *     completed, failed, cancelled -> (terminal, sin transiciones)
 */
class AgentOrchestratorService {

  /**
   * Mapa de transiciones de estado validas para ejecuciones.
   *
   * Cada clave es un estado origen y su valor es un array de estados
   * destino permitidos. Los estados terminales tienen arrays vacios.
   */
  protected const STATUS_TRANSITIONS = [
    'running' => ['completed', 'failed', 'paused', 'cancelled'],
    'paused' => ['running', 'cancelled'],
    'completed' => [],
    'failed' => [],
    'cancelled' => [],
  ];

  /**
   * Construye el servicio de orquestacion de agentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param \Drupal\jaraba_agents\Service\GuardrailsEnforcerService $guardrails
   *   Servicio de verificacion de guardrails de seguridad.
   * @param \Drupal\jaraba_agents\Service\AgentMetricsCollectorService $metrics
   *   Servicio de recoleccion de metricas de agentes.
   * @param \Drupal\jaraba_agents\Service\ApprovalManagerService $approvalManager
   *   Servicio de gestion de aprobaciones humanas.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly GuardrailsEnforcerService $guardrails,
    protected readonly AgentMetricsCollectorService $metrics,
    protected readonly ApprovalManagerService $approvalManager,
  ) {}

  /**
   * Inicia la ejecucion de un agente autonomo.
   *
   * Crea una entidad AgentExecution con estado 'running'. Verifica
   * guardrails antes de cada accion. Para agentes L2+ con acciones
   * que requieren aprobacion, crea una entidad AgentApproval.
   *
   * @param int $agentId
   *   ID de la entidad AutonomousAgent a ejecutar.
   * @param string $triggerType
   *   Tipo de trigger: 'user_request', 'schedule', 'event', 'webhook'.
   * @param array $triggerData
   *   Datos adicionales del trigger (contexto, parametros, etc.).
   *
   * @return array
   *   Array con ['success' => true, 'execution_id' => int] o
   *   ['success' => false, 'error' => string] en caso de fallo.
   */
  public function execute(int $agentId, string $triggerType = 'user_request', array $triggerData = []): array {
    try {
      // Cargar la entidad del agente autonomo.
      $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
      $agent = $agentStorage->load($agentId);

      if (!$agent) {
        $this->logger->error('Agente no encontrado: @id', ['@id' => $agentId]);
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Agente autonomo con ID @id no encontrado.', ['@id' => $agentId]),
        ];
      }

      // Verificar guardrails globales antes de iniciar.
      $guardrailResult = $this->guardrails->enforce($agent);
      if (!$guardrailResult['passed']) {
        $this->logger->warning('Guardrails bloquearon ejecucion del agente @id: @violations', [
          '@id' => $agentId,
          '@violations' => implode(', ', $guardrailResult['violations']),
        ]);
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('La ejecucion fue bloqueada por guardrails de seguridad.'),
          'violations' => $guardrailResult['violations'],
        ];
      }

      // Crear entidad de ejecucion con estado 'running'.
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $execution = $executionStorage->create([
        'agent_id' => $agentId,
        'status' => 'running',
        'trigger_type' => $triggerType,
        'trigger_data' => json_encode($triggerData, JSON_THROW_ON_ERROR),
        'started_at' => date('Y-m-d\TH:i:s'),
        'tokens_used' => 0,
        'cost' => 0.0,
        'actions_taken' => json_encode([], JSON_THROW_ON_ERROR),
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $agent->get('tenant_id')->target_id ?? NULL,
      ]);
      $execution->save();

      $executionId = (int) $execution->id();

      // Obtener nivel de autonomia del agente.
      $autonomyLevel = $this->guardrails->getLevel($agent);

      // Para agentes L2+ verificar si la accion requiere aprobacion humana.
      $levelNumeric = (int) substr($autonomyLevel, 1);
      if ($levelNumeric >= 2) {
        $requiresApproval = $this->guardrails->check($agent, $triggerType, $triggerData);
        if (!empty($requiresApproval['requires_approval'])) {
          $approvalResult = $this->approvalManager->requestApproval(
            $executionId,
            $agentId,
            (string) new TranslatableMarkup('Ejecucion automatica del agente @name - Trigger: @trigger', [
              '@name' => $agent->label(),
              '@trigger' => $triggerType,
            ]),
            $requiresApproval['reason'] ?? '',
            'medium',
          );

          if ($approvalResult['success']) {
            // Pausar ejecucion mientras espera aprobacion.
            $this->pause($executionId);

            $this->logger->info('Ejecucion @exec del agente @agent requiere aprobacion humana.', [
              '@exec' => $executionId,
              '@agent' => $agentId,
            ]);

            return [
              'success' => TRUE,
              'execution_id' => $executionId,
              'status' => 'awaiting_approval',
              'approval_id' => $approvalResult['approval_id'],
              'message' => (string) new TranslatableMarkup('La ejecucion requiere aprobacion humana antes de continuar.'),
            ];
          }
        }
      }

      // Registrar metricas iniciales.
      $this->metrics->record($executionId, [
        'tokens_used' => 0,
        'cost' => 0.0,
        'duration' => 0,
        'status' => 'running',
      ]);

      $this->logger->info('Ejecucion @exec iniciada para agente @agent (trigger: @trigger).', [
        '@exec' => $executionId,
        '@agent' => $agentId,
        '@trigger' => $triggerType,
      ]);

      return [
        'success' => TRUE,
        'execution_id' => $executionId,
        'status' => 'running',
        'message' => (string) new TranslatableMarkup('Ejecucion del agente iniciada correctamente.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al ejecutar agente @id: @message', [
        '@id' => $agentId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error interno al iniciar la ejecucion del agente.'),
      ];
    }
  }

  /**
   * Pausa una ejecucion en curso.
   *
   * Transicion valida: running -> paused.
   *
   * @param int $executionId
   *   ID de la ejecucion a pausar.
   *
   * @return array
   *   Array con ['success' => true] o ['success' => false, 'error' => string].
   */
  public function pause(int $executionId): array {
    return $this->transitionStatus($executionId, 'paused');
  }

  /**
   * Reanuda una ejecucion pausada.
   *
   * Transicion valida: paused -> running.
   *
   * @param int $executionId
   *   ID de la ejecucion a reanudar.
   *
   * @return array
   *   Array con ['success' => true] o ['success' => false, 'error' => string].
   */
  public function resume(int $executionId): array {
    return $this->transitionStatus($executionId, 'running');
  }

  /**
   * Cancela una ejecucion.
   *
   * Transiciones validas: running -> cancelled, paused -> cancelled.
   *
   * @param int $executionId
   *   ID de la ejecucion a cancelar.
   *
   * @return array
   *   Array con ['success' => true] o ['success' => false, 'error' => string].
   */
  public function cancel(int $executionId): array {
    return $this->transitionStatus($executionId, 'cancelled');
  }

  /**
   * Obtiene el estado actual de una ejecucion.
   *
   * @param int $executionId
   *   ID de la ejecucion a consultar.
   *
   * @return array
   *   Array con status, actions_taken, tokens_used, cost, o error.
   */
  public function getStatus(int $executionId): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $execution = $executionStorage->load($executionId);

      if (!$execution) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Ejecucion con ID @id no encontrada.', ['@id' => $executionId]),
        ];
      }

      $actionsTaken = json_decode($execution->get('actions_taken')->value ?? '[]', TRUE) ?: [];

      return [
        'success' => TRUE,
        'execution_id' => $executionId,
        'agent_id' => (int) $execution->get('agent_id')->target_id,
        'status' => $execution->get('status')->value,
        'trigger_type' => $execution->get('trigger_type')->value,
        'started_at' => $execution->get('started_at')->value,
        'actions_taken' => $actionsTaken,
        'tokens_used' => (int) ($execution->get('tokens_used')->value ?? 0),
        'cost' => (float) ($execution->get('cost')->value ?? 0.0),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener estado de ejecucion @id: @message', [
        '@id' => $executionId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al consultar el estado de la ejecucion.'),
      ];
    }
  }

  /**
   * Obtiene las ejecuciones activas (running o paused).
   *
   * @param int $limit
   *   Numero maximo de resultados a devolver.
   *
   * @return array
   *   Lista de ejecuciones activas con sus datos basicos.
   */
  public function getActiveExecutions(int $limit = 20): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $query = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['running', 'paused'], 'IN')
        ->sort('started_at', 'DESC')
        ->range(0, $limit);

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $executions = $executionStorage->loadMultiple($ids);
      $results = [];

      foreach ($executions as $execution) {
        $results[] = [
          'execution_id' => (int) $execution->id(),
          'agent_id' => (int) $execution->get('agent_id')->target_id,
          'status' => $execution->get('status')->value,
          'trigger_type' => $execution->get('trigger_type')->value,
          'started_at' => $execution->get('started_at')->value,
          'tokens_used' => (int) ($execution->get('tokens_used')->value ?? 0),
          'cost' => (float) ($execution->get('cost')->value ?? 0.0),
          // AUDIT-CONS-005: tenant_id como entity_reference a group.
          'tenant_id' => $execution->get('tenant_id')->target_id ?? NULL,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener ejecuciones activas: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Realiza una transicion de estado en una ejecucion.
   *
   * Valida que la transicion sea permitida segun STATUS_TRANSITIONS
   * antes de aplicar el cambio.
   *
   * @param int $executionId
   *   ID de la ejecucion.
   * @param string $newStatus
   *   Nuevo estado destino.
   *
   * @return array
   *   Array con ['success' => true] o ['success' => false, 'error' => string].
   */
  protected function transitionStatus(int $executionId, string $newStatus): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $execution = $executionStorage->load($executionId);

      if (!$execution) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Ejecucion con ID @id no encontrada.', ['@id' => $executionId]),
        ];
      }

      $currentStatus = $execution->get('status')->value;

      // Validar transicion de estado.
      $allowedTransitions = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
      if (!in_array($newStatus, $allowedTransitions, TRUE)) {
        $this->logger->warning('Transicion de estado invalida: @current -> @new para ejecucion @id.', [
          '@current' => $currentStatus,
          '@new' => $newStatus,
          '@id' => $executionId,
        ]);
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Transicion de estado no permitida: @current a @new.',
            ['@current' => $currentStatus, '@new' => $newStatus],
          ),
        ];
      }

      $execution->set('status', $newStatus);
      $execution->save();

      $this->logger->info('Ejecucion @id transicionada de @current a @new.', [
        '@id' => $executionId,
        '@current' => $currentStatus,
        '@new' => $newStatus,
      ]);

      return [
        'success' => TRUE,
        'execution_id' => $executionId,
        'previous_status' => $currentStatus,
        'status' => $newStatus,
        'message' => (string) new TranslatableMarkup('Estado de ejecucion actualizado correctamente.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al transicionar estado de ejecucion @id: @message', [
        '@id' => $executionId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al actualizar el estado de la ejecucion.'),
      ];
    }
  }

}
