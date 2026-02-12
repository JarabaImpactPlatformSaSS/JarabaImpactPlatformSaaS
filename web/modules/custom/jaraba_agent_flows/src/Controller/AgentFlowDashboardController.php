<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService;
use Drupal\jaraba_agent_flows\Service\AgentFlowTemplateService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para el dashboard de flujos de agentes IA.
 *
 * PROPOSITO:
 * Renderiza el dashboard principal que muestra flujos del tenant,
 * metricas de ejecucion, ejecuciones recientes y templates disponibles.
 *
 * RUTA: /flujos-agente
 */
class AgentFlowDashboardController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowMetricsService $metricsService
   *   Servicio de metricas de flujos.
   * @param \Drupal\jaraba_agent_flows\Service\AgentFlowTemplateService $templateService
   *   Servicio de templates de flujos.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('jaraba_agent_flows.metrics'),
      $container->get('jaraba_agent_flows.template'),
    );
  }

  /**
   * Renderiza el dashboard de flujos de agente.
   *
   * @return array
   *   Render array con tema jaraba_agent_flow_dashboard.
   */
  public function dashboard(): array {
    $flows = $this->loadFlows();
    $metrics = $this->metricsService->getDashboardMetrics();
    $recentExecutions = $this->loadRecentExecutions();
    $templates = $this->templateService->getTemplates();

    return [
      '#theme' => 'jaraba_agent_flow_dashboard',
      '#flows' => $flows,
      '#metrics' => $metrics,
      '#recent_executions' => $recentExecutions,
      '#templates' => $templates,
      '#attached' => [
        'library' => [
          'jaraba_agent_flows/dashboard',
        ],
        'drupalSettings' => [
          'agentFlowDashboard' => [
            'apiBase' => '/api/v1/agent-flows',
            'refreshInterval' => 30000,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 30,
      ],
    ];
  }

  /**
   * Renderiza la pagina de detalle de un flujo.
   *
   * @param int $agent_flow
   *   ID del flujo de agente.
   *
   * @return array
   *   Render array con tema jaraba_agent_flow_detail.
   */
  public function detail(int $agent_flow): array {
    $flowStorage = $this->entityTypeManager->getStorage('agent_flow');
    $flow = $flowStorage->load($agent_flow);

    if (!$flow) {
      return [
        '#markup' => $this->t('Flujo de agente no encontrado.'),
      ];
    }

    $metrics = $this->metricsService->getFlowMetrics($agent_flow);
    $executions = $this->loadFlowExecutions($agent_flow);

    return [
      '#theme' => 'jaraba_agent_flow_detail',
      '#flow' => [
        'id' => (int) $flow->id(),
        'name' => $flow->label(),
        'description' => $flow->get('description')->value ?? '',
        'status' => $flow->get('flow_status')->value ?? 'draft',
        'trigger_type' => $flow->get('trigger_type')->value ?? 'manual',
        'execution_count' => (int) ($flow->get('execution_count')->value ?? 0),
        'flow_config' => $flow->getDecodedFlowConfig(),
      ],
      '#executions' => $executions,
      '#metrics' => $metrics,
      '#attached' => [
        'library' => [
          'jaraba_agent_flows/dashboard',
        ],
      ],
      '#cache' => [
        'max-age' => 15,
      ],
    ];
  }

  /**
   * Carga los flujos para el dashboard.
   *
   * @return array
   *   Array de datos de flujos.
   */
  protected function loadFlows(): array {
    $flows = [];

    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('changed', 'DESC')
        ->range(0, 50);
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);

        /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlow $entity */
        foreach ($entities as $entity) {
          $flows[] = [
            'id' => (int) $entity->id(),
            'name' => $entity->label(),
            'description' => $entity->get('description')->value ?? '',
            'status' => $entity->get('flow_status')->value ?? 'draft',
            'trigger_type' => $entity->get('trigger_type')->value ?? 'manual',
            'execution_count' => (int) ($entity->get('execution_count')->value ?? 0),
          ];
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_agent_flows')->warning(
        'Error al cargar flujos para el dashboard: @error',
        ['@error' => $e->getMessage()],
      );
    }

    return $flows;
  }

  /**
   * Carga las ejecuciones recientes para el dashboard.
   *
   * @return array
   *   Array de datos de ejecuciones recientes.
   */
  protected function loadRecentExecutions(): array {
    $executions = [];

    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow_execution');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, 10);
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);

        /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution $entity */
        foreach ($entities as $entity) {
          $flowName = '';
          $flowRef = $entity->get('flow_id')->entity;
          if ($flowRef) {
            $flowName = $flowRef->label();
          }

          $startedAt = (int) ($entity->get('started_at')->value ?? 0);

          $executions[] = [
            'id' => (int) $entity->id(),
            'flow_name' => $flowName,
            'status' => $entity->get('execution_status')->value ?? 'pending',
            'duration_ms' => (int) ($entity->get('duration_ms')->value ?? 0),
            'started_at' => $startedAt,
            'started_at_formatted' => $startedAt
              ? \Drupal::service('date.formatter')->format($startedAt, 'short')
              : '',
          ];
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_agent_flows')->warning(
        'Error al cargar ejecuciones recientes: @error',
        ['@error' => $e->getMessage()],
      );
    }

    return $executions;
  }

  /**
   * Carga las ejecuciones de un flujo especifico.
   *
   * @param int $flowId
   *   ID del flujo.
   *
   * @return array
   *   Array de datos de ejecuciones.
   */
  protected function loadFlowExecutions(int $flowId): array {
    $executions = [];

    try {
      $storage = $this->entityTypeManager->getStorage('agent_flow_execution');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->sort('created', 'DESC')
        ->range(0, 20)
        ->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);

        /** @var \Drupal\jaraba_agent_flows\Entity\AgentFlowExecution $entity */
        foreach ($entities as $entity) {
          $startedAt = (int) ($entity->get('started_at')->value ?? 0);

          $executions[] = [
            'id' => (int) $entity->id(),
            'status' => $entity->get('execution_status')->value ?? 'pending',
            'duration_ms' => (int) ($entity->get('duration_ms')->value ?? 0),
            'started_at' => $startedAt,
            'started_at_formatted' => $startedAt
              ? \Drupal::service('date.formatter')->format($startedAt, 'short')
              : '',
            'triggered_by' => $entity->get('triggered_by')->value ?? '',
            'error_message' => $entity->get('error_message')->value ?? '',
          ];
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_agent_flows')->warning(
        'Error al cargar ejecuciones del flujo @flow: @error',
        ['@flow' => $flowId, '@error' => $e->getMessage()],
      );
    }

    return $executions;
  }

}
