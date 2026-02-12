<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Service\AgentFlowExecutionService;
use Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService;
use Drupal\jaraba_agent_flows\Service\AgentFlowTemplateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para flujos de agentes IA.
 *
 * PROPOSITO:
 * Proporciona endpoints REST para listar, consultar, ejecutar
 * flujos y obtener templates y ejecuciones.
 *
 * ENDPOINTS:
 * - GET  /api/v1/agent-flows                         — Listar flujos.
 * - GET  /api/v1/agent-flows/{flow_id}               — Obtener flujo.
 * - POST /api/v1/agent-flows/{flow_id}/execute        — Ejecutar flujo.
 * - GET  /api/v1/agent-flows/{flow_id}/executions     — Listar ejecuciones.
 * - GET  /api/v1/agent-flows/templates                — Listar templates.
 */
class AgentFlowApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowExecutionService $executionService
   *   Servicio de ejecucion de flujos.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService $metricsService
   *   Servicio de metricas de flujos.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowTemplateService $templateService
   *   Servicio de templates de flujos.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected AgentFlowExecutionService $executionService,
    protected AgentFlowMetricsService $metricsService,
    protected AgentFlowTemplateService $templateService,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_agent_flows.execution'),
      $container->get('jaraba_agent_flows.metrics'),
      $container->get('jaraba_agent_flows.template'),
    );
  }

  /**
   * GET /api/v1/agent-flows.
   *
   * Lista todos los flujos de agente, opcionalmente filtrados por
   * tenant_id y/o status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de flujos.
   */
  public function listFlows(Request $request): JsonResponse {
    try {
      $tenantId = $request->query->get('tenant_id');
      $status = $request->query->get('status');

      $storage = $this->entityTypeManager->getStorage('agent_flow');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('changed', 'DESC');

      if ($tenantId) {
        $query->condition('tenant_id', (int) $tenantId);
      }

      if ($status) {
        $query->condition('flow_status', $status);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return new JsonResponse([
          'flows' => [],
          'total' => 0,
        ]);
      }

      $entities = $storage->loadMultiple($ids);
      $flows = [];

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow $entity */
      foreach ($entities as $entity) {
        $flows[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'description' => $entity->get('description')->value ?? '',
          'flow_status' => $entity->get('flow_status')->value,
          'trigger_type' => $entity->get('trigger_type')->value,
          'execution_count' => (int) ($entity->get('execution_count')->value ?? 0),
          'tenant_id' => $entity->get('tenant_id')->target_id ? (int) $entity->get('tenant_id')->target_id : NULL,
          'created' => (int) $entity->get('created')->value,
          'changed' => (int) $entity->get('changed')->value,
        ];
      }

      return new JsonResponse([
        'flows' => $flows,
        'total' => count($flows),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error al listar flujos.',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agent-flows/{flow_id}.
   *
   * Obtiene un flujo especifico con sus metricas.
   *
   * @param int $flow_id
   *   ID del flujo.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Datos del flujo.
   */
  public function getFlow(int $flow_id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow');

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow|null $entity */
      $entity = $storage->load($flow_id);

      if (!$entity) {
        return new JsonResponse([
          'error' => 'Flujo no encontrado.',
        ], 404);
      }

      $metrics = $this->metricsService->getFlowMetrics($flow_id);

      return new JsonResponse([
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
        'description' => $entity->get('description')->value ?? '',
        'flow_status' => $entity->get('flow_status')->value,
        'trigger_type' => $entity->get('trigger_type')->value,
        'trigger_config' => $entity->getDecodedTriggerConfig(),
        'flow_config' => $entity->getDecodedFlowConfig(),
        'execution_count' => (int) ($entity->get('execution_count')->value ?? 0),
        'last_execution' => (int) ($entity->get('last_execution')->value ?? 0),
        'tenant_id' => $entity->get('tenant_id')->target_id ? (int) $entity->get('tenant_id')->target_id : NULL,
        'created' => (int) $entity->get('created')->value,
        'changed' => (int) $entity->get('changed')->value,
        'metrics' => $metrics,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error al obtener flujo.',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * POST /api/v1/agent-flows/{flow_id}/execute.
   *
   * Ejecuta un flujo de agente.
   *
   * @param int $flow_id
   *   ID del flujo a ejecutar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la ejecucion.
   */
  public function executeFlow(int $flow_id): JsonResponse {
    try {
      $executionId = $this->executionService->executeFlow($flow_id);

      if ($executionId === NULL) {
        return new JsonResponse([
          'error' => 'No se pudo ejecutar el flujo. Verifica que existe y esta activo.',
        ], 400);
      }

      $result = $this->executionService->getExecutionResult($executionId);

      return new JsonResponse([
        'execution_id' => $executionId,
        'flow_id' => $flow_id,
        'status' => $result['execution_status'] ?? 'unknown',
        'duration_ms' => $result['duration_ms'] ?? 0,
        'result' => $result,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error al ejecutar flujo.',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agent-flows/{flow_id}/executions.
   *
   * Lista las ejecuciones de un flujo.
   *
   * @param int $flow_id
   *   ID del flujo.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de ejecuciones.
   */
  public function getExecutions(int $flow_id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow_execution');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flow_id)
        ->sort('created', 'DESC')
        ->range(0, 50);
      $ids = $query->execute();

      if (empty($ids)) {
        return new JsonResponse([
          'executions' => [],
          'total' => 0,
        ]);
      }

      $entities = $storage->loadMultiple($ids);
      $executions = [];

      /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution $entity */
      foreach ($entities as $entity) {
        $executions[] = [
          'id' => (int) $entity->id(),
          'execution_status' => $entity->get('execution_status')->value,
          'started_at' => (int) ($entity->get('started_at')->value ?? 0),
          'completed_at' => (int) ($entity->get('completed_at')->value ?? 0),
          'duration_ms' => (int) ($entity->get('duration_ms')->value ?? 0),
          'triggered_by' => $entity->get('triggered_by')->value ?? '',
          'error_message' => $entity->get('error_message')->value ?? '',
          'created' => (int) $entity->get('created')->value,
        ];
      }

      return new JsonResponse([
        'executions' => $executions,
        'total' => count($executions),
        'flow_id' => $flow_id,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error al obtener ejecuciones.',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET /api/v1/agent-flows/templates.
   *
   * Lista los templates de flujo disponibles.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de templates.
   */
  public function getTemplates(Request $request): JsonResponse {
    try {
      $vertical = $request->query->get('vertical');
      $templates = $this->templateService->getTemplates($vertical);

      return new JsonResponse([
        'templates' => $templates,
        'total' => count($templates),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error al obtener templates.',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

}
