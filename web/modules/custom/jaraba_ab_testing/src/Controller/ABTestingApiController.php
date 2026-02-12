<?php

namespace Drupal\jaraba_ab_testing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService;
use Drupal\jaraba_ab_testing\Service\ExposureTrackingService;
use Drupal\jaraba_ab_testing\Service\ResultCalculationService;
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
 * - POST /api/v1/ab-testing/exposures/record: registra exposición.
 * - POST /api/v1/ab-testing/exposures/convert: registra conversión de exposición.
 * - GET /api/v1/ab-testing/experiments/{id}/exposures: lista exposiciones.
 * - POST /api/v1/ab-testing/experiments/{id}/results/calculate: calcula resultados.
 * - POST /api/v1/ab-testing/experiments/{id}/auto-stop: verifica auto-parada.
 * - POST /api/v1/ab-testing/experiments/{id}/declare-winner: declara ganador.
 *
 * RELACIONES:
 * - ABTestingApiController -> VariantAssignmentService
 * - ABTestingApiController -> ExperimentAggregatorService
 * - ABTestingApiController -> ExposureTrackingService
 * - ABTestingApiController -> ResultCalculationService
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
   * @var \Drupal\jaraba_ab_testing\Service\ExposureTrackingService|null
   */
  protected ?ExposureTrackingService $exposureTracking = NULL;

  /**
   * @var \Drupal\jaraba_ab_testing\Service\ResultCalculationService|null
   */
  protected ?ResultCalculationService $resultCalculation = NULL;

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

    try {
      $instance->exposureTracking = $container->get('jaraba_ab_testing.exposure_tracking');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->resultCalculation = $container->get('jaraba_ab_testing.result_calculation');
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

  /**
   * Registra una exposición de visitante a variante (POST /api/v1/ab-testing/exposures/record).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Body JSON: { "experiment_id": int, "variant_id": string, "visitor_id": string, "context": {} }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": { "id": int, "experiment_id": int, "variant_id": string, ... } }
   */
  public function recordExposure(Request $request): JsonResponse {
    if (!$this->exposureTracking) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);
    $experimentId = (int) ($body['experiment_id'] ?? 0);
    $variantId = $body['variant_id'] ?? '';
    $visitorId = $body['visitor_id'] ?? '';
    $context = $body['context'] ?? [];

    if (empty($experimentId) || empty($variantId) || empty($visitorId)) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Bad Request', 'detail' => 'Fields "experiment_id", "variant_id" and "visitor_id" are required.']],
      ], 400);
    }

    $result = $this->exposureTracking->recordExposure($experimentId, $variantId, $visitorId, $context);

    if (empty($result)) {
      return new JsonResponse([
        'errors' => [['status' => 500, 'title' => 'Internal Server Error', 'detail' => 'Could not record exposure.']],
      ], 500);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * Lista las exposiciones de un experimento (GET /api/v1/ab-testing/experiments/{id}/exposures).
   *
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": [ ... ] }
   */
  public function listExposures(int $experiment_id): JsonResponse {
    if (!$this->exposureTracking) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $exposures = $this->exposureTracking->getExposuresForExperiment($experiment_id);

    $data = [];
    foreach ($exposures as $exposure) {
      $data[] = [
        'id' => (int) $exposure->id(),
        'experiment_id' => (int) $exposure->get('experiment_id')->target_id,
        'variant_id' => $exposure->get('variant_id')->value,
        'visitor_id' => $exposure->get('visitor_id')->value,
        'exposed_at' => (int) $exposure->get('exposed_at')->value,
        'converted' => (bool) $exposure->get('converted')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Calcula resultados estadísticos de un experimento (POST .../results/calculate).
   *
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": [ ... ] }
   */
  public function calculateResults(int $experiment_id): JsonResponse {
    if (!$this->resultCalculation) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $results = $this->resultCalculation->calculateResults($experiment_id);

    if (empty($results)) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => 'No data available for calculation.']],
      ], 404);
    }

    return new JsonResponse(['data' => $results]);
  }

  /**
   * Verifica auto-parada de un experimento (POST .../auto-stop).
   *
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": { "should_stop": bool } }
   */
  public function checkAutoStop(int $experiment_id): JsonResponse {
    if (!$this->resultCalculation) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $shouldStop = $this->resultCalculation->checkAutoStop($experiment_id);

    return new JsonResponse(['data' => ['should_stop' => $shouldStop]]);
  }

  /**
   * Declara la variante ganadora de un experimento (POST .../declare-winner).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Body JSON: { "variant_id": string }
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   { "data": { "declared": bool } }
   */
  public function declareWinner(Request $request, int $experiment_id): JsonResponse {
    if (!$this->resultCalculation) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);
    $variantId = $body['variant_id'] ?? '';

    if (empty($variantId)) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Bad Request', 'detail' => 'Field "variant_id" is required.']],
      ], 400);
    }

    $declared = $this->resultCalculation->declareWinner($experiment_id, $variantId);

    return new JsonResponse([
      'data' => ['declared' => $declared],
    ], $declared ? 200 : 400);
  }

}
