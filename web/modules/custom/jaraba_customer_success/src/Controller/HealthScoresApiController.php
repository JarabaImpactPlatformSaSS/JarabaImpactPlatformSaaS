<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\jaraba_customer_success\Entity\CsPlaybook;
use Drupal\jaraba_customer_success\Entity\ExpansionSignal;
use Drupal\jaraba_customer_success\Service\HealthScoreCalculatorService;
use Drupal\jaraba_customer_success\Service\ChurnPredictionService;
use Drupal\jaraba_customer_success\Service\PlaybookExecutorService;

/**
 * Controlador REST API para Customer Success.
 *
 * PROPÓSITO:
 * Endpoints JSON para health scores, churn predictions,
 * expansion signals y ejecución de playbooks.
 *
 * DIRECTRICES:
 * - Todos los endpoints devuelven JsonResponse.
 * - Permisos granulares por tipo de dato.
 * - Paginación estándar con limit/offset.
 */
class HealthScoresApiController extends ControllerBase {

  public function __construct(
    protected HealthScoreCalculatorService $healthCalculator,
    protected ChurnPredictionService $churnPrediction,
    protected PlaybookExecutorService $playbookExecutor,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.health_calculator'),
      $container->get('jaraba_customer_success.churn_prediction'),
      $container->get('jaraba_customer_success.playbook_executor'),
    );
  }

  /**
   * GET /api/v1/health-scores — Listar health scores.
   */
  public function list(Request $request): JsonResponse {
    $limit = min(100, (int) $request->query->get('limit', 20));
    $offset = max(0, (int) $request->query->get('offset', 0));
    $category = $request->query->get('category', '');

    $storage = $this->entityTypeManager()->getStorage('customer_health');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('calculated_at', 'DESC')
      ->range($offset, $limit);

    if ($category && in_array($category, ['healthy', 'neutral', 'at_risk', 'critical'])) {
      $query->condition('category', $category);
    }

    $ids = $query->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = $this->serializeHealthScore($entity);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'limit' => $limit,
        'offset' => $offset,
      ],
    ]);
  }

  /**
   * GET /api/v1/health-scores/{tenant_id} — Health score de tenant.
   */
  public function detail(string $tenant_id): JsonResponse {
    $history = $this->healthCalculator->getHistory($tenant_id, 1);

    if (empty($history)) {
      return new JsonResponse(['error' => 'No health score found for tenant.'], 404);
    }

    $entity = reset($history);
    return new JsonResponse(['data' => $this->serializeHealthScore($entity)]);
  }

  /**
   * GET /api/v1/health-scores/{tenant_id}/history — Histórico.
   */
  public function history(string $tenant_id, Request $request): JsonResponse {
    $limit = min(100, (int) $request->query->get('limit', 30));
    $history = $this->healthCalculator->getHistory($tenant_id, $limit);

    $data = [];
    foreach ($history as $entity) {
      $data[] = $this->serializeHealthScore($entity);
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * GET /api/v1/churn-predictions — Listar predicciones.
   */
  public function churnPredictions(Request $request): JsonResponse {
    $risk_level = $request->query->get('risk_level', '');

    $storage = $this->entityTypeManager()->getStorage('churn_prediction');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 50);

    if ($risk_level && in_array($risk_level, ['low', 'medium', 'high', 'critical'])) {
      $query->condition('risk_level', $risk_level);
    }

    $ids = $query->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $tenant = $entity->get('tenant_id')->entity;
      $data[] = [
        'id' => (int) $entity->id(),
        'tenant_id' => $entity->get('tenant_id')->target_id,
        'tenant_name' => $tenant ? $tenant->label() : NULL,
        'probability' => (float) $entity->get('probability')->value,
        'risk_level' => $entity->getRiskLevel(),
        'predicted_churn_date' => $entity->get('predicted_churn_date')->value,
        'risk_factors' => $entity->getRiskFactors(),
        'confidence' => (float) $entity->get('confidence')->value,
        'model_version' => $entity->get('model_version')->value,
        'created' => $entity->get('created')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * GET /api/v1/expansion-signals — Listar señales.
   */
  public function expansionSignals(Request $request): JsonResponse {
    $status = $request->query->get('status', '');

    $storage = $this->entityTypeManager()->getStorage('expansion_signal');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('detected_at', 'DESC')
      ->range(0, 50);

    if ($status && in_array($status, ['new', 'contacted', 'won', 'lost', 'deferred'])) {
      $query->condition('status', $status);
    }

    $ids = $query->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $tenant = $entity->get('tenant_id')->entity;
      $data[] = [
        'id' => (int) $entity->id(),
        'tenant_id' => $entity->get('tenant_id')->target_id,
        'tenant_name' => $tenant ? $tenant->label() : NULL,
        'signal_type' => $entity->getSignalType(),
        'current_plan' => $entity->get('current_plan')->value,
        'recommended_plan' => $entity->get('recommended_plan')->value,
        'potential_arr' => $entity->getPotentialArr(),
        'status' => $entity->getStatus(),
        'detected_at' => $entity->get('detected_at')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * PUT /api/v1/expansion-signals/{expansion_signal} — Actualizar estado.
   */
  public function updateExpansionSignal(ExpansionSignal $expansion_signal, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $new_status = $content['status'] ?? NULL;

    $valid_statuses = ['new', 'contacted', 'won', 'lost', 'deferred'];
    if (!$new_status || !in_array($new_status, $valid_statuses)) {
      return new JsonResponse(['error' => 'Invalid status. Allowed: ' . implode(', ', $valid_statuses)], 400);
    }

    $expansion_signal->set('status', $new_status);
    $expansion_signal->save();

    return new JsonResponse(['data' => ['id' => (int) $expansion_signal->id(), 'status' => $new_status]]);
  }

  /**
   * POST /api/v1/playbooks/{cs_playbook}/execute — Ejecutar playbook.
   */
  public function executePlaybook(CsPlaybook $cs_playbook, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $tenant_id = $content['tenant_id'] ?? NULL;

    if (!$tenant_id) {
      return new JsonResponse(['error' => 'tenant_id is required.'], 400);
    }

    $execution = $this->playbookExecutor->execute($cs_playbook, $tenant_id);

    if (!$execution) {
      return new JsonResponse(['error' => 'Failed to start playbook execution. It may already be running.'], 409);
    }

    return new JsonResponse([
      'data' => [
        'execution_id' => (int) $execution->id(),
        'playbook' => $cs_playbook->getName(),
        'tenant_id' => $tenant_id,
        'status' => 'running',
      ],
    ], 201);
  }

  /**
   * Serializa una entidad CustomerHealth a array.
   */
  protected function serializeHealthScore($entity): array {
    $tenant = $entity->get('tenant_id')->entity;
    $breakdown = $entity->get('score_breakdown')->value;

    return [
      'id' => (int) $entity->id(),
      'tenant_id' => $entity->getTenantId(),
      'tenant_name' => $tenant ? $tenant->label() : NULL,
      'overall_score' => $entity->getOverallScore(),
      'category' => $entity->getCategory(),
      'trend' => $entity->get('trend')->value,
      'engagement_score' => (int) $entity->get('engagement_score')->value,
      'adoption_score' => (int) $entity->get('adoption_score')->value,
      'satisfaction_score' => (int) $entity->get('satisfaction_score')->value,
      'support_score' => (int) $entity->get('support_score')->value,
      'growth_score' => (int) $entity->get('growth_score')->value,
      'churn_probability' => (float) $entity->get('churn_probability')->value,
      'score_breakdown' => $breakdown ? json_decode($breakdown, TRUE) : NULL,
      'calculated_at' => $entity->get('calculated_at')->value,
    ];
  }

}
