<?php

namespace Drupal\jaraba_ab_testing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService;
use Drupal\jaraba_ab_testing\Service\VariantAssignmentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para A/B testing.
 *
 * ESTRUCTURA:
 * Endpoints JSON para asignación de variantes, registro de conversiones
 * y consulta de resultados de experimentos. Usado por el frontend JS
 * y por integraciones externas.
 *
 * LÓGICA:
 * - POST /api/v1/ab-testing/assign: asigna variante a visitante.
 * - POST /api/v1/ab-testing/convert: registra conversión.
 * - GET /api/v1/ab-testing/experiments/{id}/results: obtiene resultados.
 *
 * RELACIONES:
 * - ABTestingApiController -> VariantAssignmentService
 * - ABTestingApiController -> ExperimentAggregatorService
 *
 * @package Drupal\jaraba_ab_testing\Controller
 */
class ABTestingApiController extends ControllerBase {

  /**
   * @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService|null
   */
  protected ?VariantAssignmentService $assignmentService = NULL;

  /**
   * @var \Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService|null
   */
  protected ?ExperimentAggregatorService $aggregator = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->assignmentService = $container->get('jaraba_ab_testing.variant_assignment');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->aggregator = $container->get('jaraba_ab_testing.experiment_aggregator');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    return $instance;
  }

  /**
   * Asigna una variante a un visitante (POST /api/v1/ab-testing/assign).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Body JSON: { "experiment": "machine_name_del_experimento" }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": { "variant_id": int, "variant_key": string, "is_control": bool } }
   */
  public function assignVariant(Request $request): JsonResponse {
    if (!$this->assignmentService) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);
    $experiment_name = $body['experiment'] ?? '';

    if (empty($experiment_name)) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Bad Request', 'detail' => 'Field "experiment" is required.']],
      ], 400);
    }

    $assignment = $this->assignmentService->assignVariant($experiment_name);

    if (!$assignment) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => 'Experiment not found or not running.']],
      ], 404);
    }

    return new JsonResponse(['data' => $assignment]);
  }

  /**
   * Registra una conversión (POST /api/v1/ab-testing/convert).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Body JSON: { "experiment": "machine_name", "revenue": 0.0 }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": { "recorded": true } }
   */
  public function recordConversion(Request $request): JsonResponse {
    if (!$this->assignmentService) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);
    $experiment_name = $body['experiment'] ?? '';
    $revenue = (float) ($body['revenue'] ?? 0.0);

    if (empty($experiment_name)) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Bad Request', 'detail' => 'Field "experiment" is required.']],
      ], 400);
    }

    $recorded = $this->assignmentService->recordConversion($experiment_name, $revenue);

    return new JsonResponse([
      'data' => ['recorded' => $recorded],
    ], $recorded ? 200 : 400);
  }

  /**
   * Obtiene resultados de un experimento (GET /api/v1/ab-testing/experiments/{id}/results).
   *
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Detalle completo con variantes y análisis estadístico.
   */
  public function getResults(int $experiment_id): JsonResponse {
    if (!$this->aggregator) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $detail = $this->aggregator->getExperimentDetail($experiment_id);

    if (empty($detail)) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => 'Experiment not found.']],
      ], 404);
    }

    return new JsonResponse(['data' => $detail]);
  }

}
