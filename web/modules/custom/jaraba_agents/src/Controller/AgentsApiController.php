<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_agents\Service\AgentMetricsCollectorService;
use Drupal\jaraba_agents\Service\AgentOrchestratorService;
use Drupal\jaraba_agents\Service\ApprovalManagerService;
use Drupal\jaraba_agents\Service\GuardrailsEnforcerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de Agentes Autonomos.
 *
 * Estructura: 10 endpoints JSON para configuracion de agentes,
 *   ejecuciones, aprobaciones humanas y metricas. Sigue el patron
 *   del ecosistema con envelope estandar {data}/{data,meta}/{error}.
 *
 * Logica: Las ejecuciones pasan por el orquestador que verifica
 *   guardrails y solicita aprobaciones cuando es necesario.
 *   Las metricas se agregan desde AgentExecution entities.
 */
class AgentsApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * Estructura: Recibe los 4 servicios del modulo jaraba_agents.
   * Logica: PHP 8.3 promoted properties para asignacion automatica.
   */
  public function __construct(
    protected AgentOrchestratorService $orchestrator,
    protected GuardrailsEnforcerService $guardrails,
    protected AgentMetricsCollectorService $metrics,
    protected ApprovalManagerService $approvalManager,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Estructura: Factory method estatico requerido por ControllerBase.
   * Logica: Resuelve los 4 servicios desde el contenedor DI.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_agents.orchestrator'),
      $container->get('jaraba_agents.guardrails'),
      $container->get('jaraba_agents.metrics'),
      $container->get('jaraba_agents.approval_manager'),
    );
  }

  // ============================================
  // AGENTES
  // ============================================

  /**
   * GET /api/v1/agents — Listar agentes autonomos.
   *
   * Estructura: Endpoint de lectura con filtros y paginacion.
   * Logica: Soporta filtros por agent_type, vertical, is_active y
   *   autonomy_level. Paginacion via limit/offset.
   */
  public function listAgents(Request $request): JsonResponse {
    try {
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
      $offset = max(0, (int) $request->query->get('offset', 0));

      $filters = [];
      $filterable = ['agent_type', 'vertical', 'is_active', 'autonomy_level'];
      foreach ($filterable as $field) {
        $value = $request->query->get($field);
        if ($value !== NULL) {
          $filters[$field] = $value;
        }
      }

      $storage = $this->entityTypeManager()->getStorage('autonomous_agent');
      $query = $storage->getQuery()->accessCheck(TRUE);

      foreach ($filters as $field => $value) {
        $query->condition($field, $value);
      }

      $count_query = clone $query;
      $total = (int) $count_query->count()->execute();

      $ids = $query
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $agents = $storage->loadMultiple($ids);

      $data = [];
      foreach ($agents as $agent) {
        $data[] = $this->serializeAgent($agent);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $total,
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al listar los agentes autonomos.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agents/{autonomous_agent} — Detalle de un agente.
   *
   * Estructura: Endpoint de lectura individual.
   * Logica: Carga la entidad por ID y retorna {data} o {error} 404.
   */
  public function showAgent(int $autonomous_agent): JsonResponse {
    try {
      $agent = $this->entityTypeManager()
        ->getStorage('autonomous_agent')
        ->load($autonomous_agent);

      if (!$agent) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Agente autonomo no encontrado.'),
        ], 404);
      }

      return new JsonResponse([
        'data' => $this->serializeAgent($agent),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener el agente autonomo.'),
      ], 500);
    }
  }

  /**
   * PATCH /api/v1/agents/{autonomous_agent} — Actualizar configuracion.
   *
   * Estructura: Endpoint de escritura parcial (PATCH).
   * Logica: Solo actualiza los campos enviados en el body.
   *   Los guardrails se revalidan tras cada cambio de configuracion.
   */
  public function updateAgentConfig(Request $request, int $autonomous_agent): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (empty($content)) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('El cuerpo de la solicitud no puede estar vacio.'),
        ], 422);
      }

      $agent = $this->entityTypeManager()
        ->getStorage('autonomous_agent')
        ->load($autonomous_agent);

      if (!$agent) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Agente autonomo no encontrado.'),
        ], 404);
      }

      $updatable = [
        'name',
        'objective',
        'capabilities',
        'guardrails',
        'autonomy_level',
        'llm_model',
        'temperature',
        'max_actions_per_run',
        'requires_approval',
        'is_active',
      ];

      foreach ($updatable as $field) {
        if (isset($content[$field])) {
          $agent->set($field, $content[$field]);
        }
      }

      $violations = $this->guardrails->validateConfig($agent);
      if (!empty($violations)) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('La configuracion viola las restricciones de guardrails.'),
          'violations' => $violations,
        ], 422);
      }

      $agent->save();

      return new JsonResponse([
        'data' => $this->serializeAgent($agent),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al actualizar la configuracion del agente.'),
      ], 500);
    }
  }

  // ============================================
  // EJECUCIONES
  // ============================================

  /**
   * POST /api/v1/agents/{autonomous_agent}/execute — Ejecutar agente.
   *
   * Estructura: Endpoint POST que inicia una ejecucion.
   * Logica: Delega al orquestador que verifica guardrails, crea
   *   la entidad AgentExecution y solicita aprobacion si es necesario.
   *   Usa store() en lugar de create() (API-NAMING-001).
   */
  public function executeAgent(Request $request, int $autonomous_agent): JsonResponse {
    try {
      $agent = $this->entityTypeManager()
        ->getStorage('autonomous_agent')
        ->load($autonomous_agent);

      if (!$agent) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Agente autonomo no encontrado.'),
        ], 404);
      }

      $content = json_decode($request->getContent(), TRUE) ?? [];
      $trigger_type = $content['trigger_type'] ?? 'user_request';
      $trigger_data = $content['trigger_data'] ?? [];

      $execution = $this->orchestrator->store($agent, $trigger_type, $trigger_data);

      return new JsonResponse([
        'data' => [
          'execution_id' => (int) $execution->id(),
        ],
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al ejecutar el agente autonomo.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agents/{autonomous_agent}/executions — Listar ejecuciones.
   *
   * Estructura: Endpoint de lectura con filtro por status y paginacion.
   * Logica: Retorna las ejecuciones del agente indicado con posibilidad
   *   de filtrar por estado (pending, running, completed, failed).
   */
  public function listExecutions(Request $request, int $autonomous_agent): JsonResponse {
    try {
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
      $offset = max(0, (int) $request->query->get('offset', 0));

      $storage = $this->entityTypeManager()->getStorage('agent_execution');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('agent_id', $autonomous_agent);

      $status = $request->query->get('status');
      if ($status !== NULL) {
        $query->condition('status', $status);
      }

      $count_query = clone $query;
      $total = (int) $count_query->count()->execute();

      $ids = $query
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $executions = $storage->loadMultiple($ids);

      $data = [];
      foreach ($executions as $execution) {
        $data[] = $this->serializeExecution($execution);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $total,
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al listar las ejecuciones del agente.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agents/executions/{agent_execution} — Detalle de ejecucion.
   *
   * Estructura: Endpoint de lectura individual de ejecucion.
   * Logica: Carga la entidad AgentExecution por ID.
   */
  public function showExecution(int $agent_execution): JsonResponse {
    try {
      $execution = $this->entityTypeManager()
        ->getStorage('agent_execution')
        ->load($agent_execution);

      if (!$execution) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Ejecucion de agente no encontrada.'),
        ], 404);
      }

      return new JsonResponse([
        'data' => $this->serializeExecution($execution),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener la ejecucion del agente.'),
      ], 500);
    }
  }

  // ============================================
  // APROBACIONES
  // ============================================

  /**
   * GET /api/v1/agents/approvals — Listar aprobaciones pendientes.
   *
   * Estructura: Endpoint de lectura con paginacion.
   * Logica: Retorna todas las aprobaciones en estado pending
   *   ordenadas por fecha de creacion descendente.
   */
  public function listPendingApprovals(Request $request): JsonResponse {
    try {
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
      $offset = max(0, (int) $request->query->get('offset', 0));

      $storage = $this->entityTypeManager()->getStorage('agent_approval');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'pending');

      $count_query = clone $query;
      $total = (int) $count_query->count()->execute();

      $ids = $query
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $approvals = $storage->loadMultiple($ids);

      $data = [];
      foreach ($approvals as $approval) {
        $data[] = $this->serializeApproval($approval);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $total,
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al listar las aprobaciones pendientes.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/agents/approvals/{agent_approval}/approve — Aprobar accion.
   *
   * Estructura: Endpoint POST que cambia estado a approved.
   * Logica: Delega al ApprovalManagerService que actualiza la entidad,
   *   registra el revisor y reanuda la ejecucion del agente.
   */
  public function approveAction(Request $request, int $agent_approval): JsonResponse {
    try {
      $approval = $this->entityTypeManager()
        ->getStorage('agent_approval')
        ->load($agent_approval);

      if (!$approval) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Aprobacion no encontrada.'),
        ], 404);
      }

      $content = json_decode($request->getContent(), TRUE) ?? [];
      $notes = $content['notes'] ?? NULL;

      $result = $this->approvalManager->approve($approval, $notes);

      return new JsonResponse([
        'data' => $this->serializeApproval($result),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al aprobar la accion del agente.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/agents/approvals/{agent_approval}/reject — Rechazar accion.
   *
   * Estructura: Endpoint POST que cambia estado a rejected.
   * Logica: Delega al ApprovalManagerService que actualiza la entidad
   *   y detiene la ejecucion del agente asociada.
   */
  public function rejectAction(Request $request, int $agent_approval): JsonResponse {
    try {
      $approval = $this->entityTypeManager()
        ->getStorage('agent_approval')
        ->load($agent_approval);

      if (!$approval) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Aprobacion no encontrada.'),
        ], 404);
      }

      $content = json_decode($request->getContent(), TRUE) ?? [];
      $notes = $content['notes'] ?? NULL;

      $result = $this->approvalManager->reject($approval, $notes);

      return new JsonResponse([
        'data' => $this->serializeApproval($result),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al rechazar la accion del agente.'),
      ], 500);
    }
  }

  // ============================================
  // METRICAS
  // ============================================

  /**
   * GET /api/v1/agents/metrics — Metricas agregadas de ejecuciones.
   *
   * Estructura: Endpoint de lectura con filtros opcionales.
   * Logica: Acepta agent_id para filtrar por agente y days para
   *   limitar el rango temporal. Delega al AgentMetricsCollectorService.
   */
  public function getMetrics(Request $request): JsonResponse {
    try {
      $agent_id = $request->query->get('agent_id');
      $days = (int) $request->query->get('days', 30);

      $result = $this->metrics->collect(
        $agent_id ? (int) $agent_id : NULL,
        $days,
      );

      return new JsonResponse([
        'data' => $result,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener las metricas de agentes.'),
      ], 500);
    }
  }

  // ============================================
  // SERIALIZACION
  // ============================================

  /**
   * Serializa una entidad AutonomousAgent para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Extrae los campos relevantes de la entidad y los
   *   convierte a un array asociativo con tipos correctos.
   */
  protected function serializeAgent(object $agent): array {
    return [
      'id' => (int) $agent->id(),
      'name' => $agent->get('name')->value ?? '',
      'agent_type' => $agent->get('agent_type')->value ?? '',
      'vertical' => $agent->get('vertical')->value ?? '',
      'objective' => $agent->get('objective')->value ?? '',
      'autonomy_level' => $agent->get('autonomy_level')->value ?? '',
      'llm_model' => $agent->get('llm_model')->value ?? '',
      'temperature' => $agent->get('temperature')->value !== NULL ? (float) $agent->get('temperature')->value : NULL,
      'max_actions_per_run' => $agent->get('max_actions_per_run')->value !== NULL ? (int) $agent->get('max_actions_per_run')->value : NULL,
      'is_active' => (bool) ($agent->get('is_active')->value ?? FALSE),
      'created' => $agent->get('created')->value ?? NULL,
      'changed' => $agent->get('changed')->value ?? NULL,
    ];
  }

  /**
   * Serializa una entidad AgentExecution para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Convierte campos numericos a sus tipos nativos.
   *   El agent_id se extrae como target_id de la referencia.
   */
  protected function serializeExecution(object $execution): array {
    return [
      'id' => (int) $execution->id(),
      'agent_id' => $execution->get('agent_id')->target_id ? (int) $execution->get('agent_id')->target_id : NULL,
      'trigger_type' => $execution->get('trigger_type')->value ?? '',
      'started_at' => $execution->get('started_at')->value ?? NULL,
      'completed_at' => $execution->get('completed_at')->value ?? NULL,
      'status' => $execution->get('status')->value ?? '',
      'tokens_used' => $execution->get('tokens_used')->value !== NULL ? (int) $execution->get('tokens_used')->value : NULL,
      'cost_estimate' => $execution->get('cost_estimate')->value !== NULL ? (float) $execution->get('cost_estimate')->value : NULL,
      'human_feedback' => $execution->get('human_feedback')->value ?? NULL,
      'created' => $execution->get('created')->value ?? NULL,
    ];
  }

  /**
   * Serializa una entidad AgentApproval para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Los campos de referencia (execution_id, agent_id,
   *   reviewed_by) se extraen como target_id.
   */
  protected function serializeApproval(object $approval): array {
    return [
      'id' => (int) $approval->id(),
      'execution_id' => $approval->get('execution_id')->target_id ? (int) $approval->get('execution_id')->target_id : NULL,
      'agent_id' => $approval->get('agent_id')->target_id ? (int) $approval->get('agent_id')->target_id : NULL,
      'action_description' => $approval->get('action_description')->value ?? '',
      'reasoning' => $approval->get('reasoning')->value ?? '',
      'risk_assessment' => $approval->get('risk_assessment')->value ?? '',
      'status' => $approval->get('status')->value ?? '',
      'reviewed_by' => $approval->get('reviewed_by')->target_id ? (int) $approval->get('reviewed_by')->target_id : NULL,
      'reviewed_at' => $approval->get('reviewed_at')->value ?? NULL,
      'expires_at' => $approval->get('expires_at')->value ?? NULL,
      'created' => $approval->get('created')->value ?? NULL,
    ];
  }

}
