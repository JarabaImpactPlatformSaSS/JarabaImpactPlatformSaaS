<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;
use Drupal\jaraba_copilot_v2\Service\LearningCardService;
use Drupal\jaraba_copilot_v2\Service\TestCardGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for experiments (library + lifecycle).
 */
class ExperimentApiController extends ControllerBase {

  /**
   * Impact points per decision type.
   */
  const IMPACT_POINTS = [
    'PERSEVERE' => 100,
    'PIVOT' => 75,
    'ZOOM_IN' => 75,
    'ZOOM_OUT' => 75,
    'KILL' => 50,
  ];

  protected ExperimentLibraryService $experimentLibrary;
  protected FeatureUnlockService $featureUnlock;
  protected LearningCardService $learningCard;
  protected TestCardGeneratorService $testCardGenerator;

  /**
   * Constructor.
   */
  public function __construct(
    ExperimentLibraryService $experimentLibrary,
    FeatureUnlockService $featureUnlock,
    EntityTypeManagerInterface $entityTypeManager,
    LearningCardService $learningCard,
    TestCardGeneratorService $testCardGenerator
  ) {
    $this->experimentLibrary = $experimentLibrary;
    $this->featureUnlock = $featureUnlock;
    $this->entityTypeManager = $entityTypeManager;
    $this->learningCard = $learningCard;
    $this->testCardGenerator = $testCardGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.experiment_library'),
      $container->get('jaraba_copilot_v2.feature_unlock'),
      $container->get('entity_type.manager'),
      $container->get('jaraba_copilot_v2.learning_card'),
      $container->get('jaraba_copilot_v2.test_card_generator'),
    );
  }

  /**
   * GET /api/v1/copilot/experiments - Library catalog (existing). (AUDIT-CONS-N07)
   */
  public function list(Request $request): JsonResponse {
    $category = $request->query->get('category');
    $experiments = $this->experimentLibrary->getAvailableExperiments(NULL, $category);

    return new JsonResponse([
      'success' => TRUE,
      'experiments' => $experiments,
      'count' => count($experiments),
    ]);
  }

  /**
   * POST /api/v1/copilot/experiments/suggest - Suggest experiments (existing). (AUDIT-CONS-N07)
   */
  public function suggest(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $hypothesisType = $data['hypothesis_type'] ?? 'DESIRABILITY';
    $bmcBlock = $data['bmc_block'] ?? 'VP';

    $suggestions = $this->experimentLibrary->suggestExperiments($hypothesisType, $bmcBlock);

    return new JsonResponse([
      'success' => TRUE,
      'suggestions' => $suggestions,
      'count' => count($suggestions),
    ]);
  }

  /**
   * GET /api/v1/experiments - Lista experimentos del usuario con filtros.
   */
  public function listUserExperiments(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $query = $storage->getQuery()->accessCheck(TRUE);

      $profileId = $request->query->get('profile');
      if ($profileId) {
        $query->condition('entrepreneur_profile', (int) $profileId);
      }

      $status = $request->query->get('status');
      if ($status && in_array($status, ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])) {
        $query->condition('status', $status);
      }

      $hypothesisId = $request->query->get('hypothesis');
      if ($hypothesisId) {
        $query->condition('hypothesis', (int) $hypothesisId);
      }

      if (!$this->currentUser()->hasPermission('administer experiments') && !$profileId) {
        $query->condition('user_id', $this->currentUser()->id());
      }

      $query->sort('created', 'DESC');
      $ids = $query->execute();
      $experiments = $storage->loadMultiple($ids);

      $data = [];
      foreach ($experiments as $experiment) {
        $data[] = $this->serializeExperiment($experiment);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'count' => count($data),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * POST /api/v1/experiments - Crea Test Card vinculada a hipotesis.
   */
  public function store(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['title'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El campo title es obligatorio.',
        ], 400);
      }

      if (empty($data['experiment_type'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El campo experiment_type es obligatorio.',
        ], 400);
      }

      $storage = $this->entityTypeManager->getStorage('experiment');
      $experiment = $storage->create([
        'title' => $data['title'],
        'experiment_type' => $data['experiment_type'],
        'hypothesis' => $data['hypothesis_id'] ?? NULL,
        'entrepreneur_profile' => $data['entrepreneur_profile'] ?? NULL,
        'plan' => $data['plan'] ?? '',
        'metrics' => $data['metrics'] ?? '',
        'success_criteria' => $data['success_criteria'] ?? '',
        'failure_criteria' => $data['failure_criteria'] ?? '',
        'start_date' => $data['start_date'] ?? NULL,
        'end_date' => $data['end_date'] ?? NULL,
        'status' => 'PLANNED',
        'user_id' => $this->currentUser()->id(),
      ]);

      $experiment->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeExperiment($experiment),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET /api/v1/experiments/{id} - Detalle completo Test+Learning Card.
   */
  public function get(string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $experiment = $storage->load($id);

      if (!$experiment) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Experimento no encontrado.',
        ], 404);
      }

      $data = $this->serializeExperiment($experiment, TRUE);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * POST /api/v1/experiments/{id}/start - Cambia status a IN_PROGRESS.
   */
  public function start(string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $experiment = $storage->load($id);

      if (!$experiment) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Experimento no encontrado.',
        ], 404);
      }

      $currentStatus = $experiment->get('status')->value;
      if ($currentStatus !== 'PLANNED') {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Solo experimentos en estado PLANNED pueden iniciarse. Estado actual: ' . $currentStatus,
        ], 400);
      }

      $experiment->set('status', 'IN_PROGRESS');
      if (!$experiment->get('start_date')->value) {
        $experiment->set('start_date', date('Y-m-d\TH:i:s'));
      }
      $experiment->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeExperiment($experiment),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * PATCH /api/v1/experiments/{id}/result - Learning Card + decision + impact points.
   */
  public function recordResult(Request $request, string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment');
      $experiment = $storage->load($id);

      if (!$experiment) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Experimento no encontrado.',
        ], 404);
      }

      $currentStatus = $experiment->get('status')->value;
      if (!in_array($currentStatus, ['IN_PROGRESS', 'PLANNED'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Solo experimentos en progreso o planeados pueden registrar resultados.',
        ], 400);
      }

      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['decision'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El campo decision es obligatorio (PERSEVERE, PIVOT, ZOOM_IN, ZOOM_OUT, KILL).',
        ], 400);
      }

      $validDecisions = array_keys(self::IMPACT_POINTS);
      $decision = strtoupper($data['decision']);
      if (!in_array($decision, $validDecisions)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'decision debe ser: ' . implode(', ', $validDecisions),
        ], 400);
      }

      // Actualizar Learning Card fields
      $experiment->set('status', 'COMPLETED');
      $experiment->set('decision', $decision);
      $experiment->set('end_date', date('Y-m-d\TH:i:s'));

      if (isset($data['observations'])) {
        $experiment->set('observations', $data['observations']);
      }
      if (isset($data['metrics_results'])) {
        $experiment->set('metrics_results', $data['metrics_results']);
      }
      if (isset($data['customer_learning'])) {
        $experiment->set('customer_learning', $data['customer_learning']);
      }
      if (isset($data['problem_learning'])) {
        $experiment->set('problem_learning', $data['problem_learning']);
      }
      if (isset($data['solution_learning'])) {
        $experiment->set('solution_learning', $data['solution_learning']);
      }
      if (isset($data['next_steps'])) {
        $experiment->set('next_steps', $data['next_steps']);
      }

      // Determinar resultado basado en decision
      $result = match ($decision) {
        'PERSEVERE' => 'VALIDATED',
        'KILL' => 'INVALIDATED',
        default => 'INCONCLUSIVE',
      };
      $experiment->set('result', $result);

      // Award impact points
      $points = self::IMPACT_POINTS[$decision];
      $experiment->set('points_awarded', $points);
      $experiment->save();

      // Actualizar puntos en perfil del emprendedor
      $this->awardImpactPoints($experiment, $points);

      // Registrar milestone
      $this->recordMilestone($experiment, $decision, $points);

      // Actualizar estado de la hipotesis vinculada
      $this->updateHypothesisStatus($experiment, $result);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeExperiment($experiment, TRUE),
        'points_awarded' => $points,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Registra un milestone en la tabla entrepreneur_milestone.
   */
  protected function recordMilestone($experiment, string $decision, int $points): void {
    try {
      $profileId = $experiment->get('entrepreneur_profile')->target_id;
      if (!$profileId) {
        return;
      }

      $database = \Drupal::database();
      if (!$database->schema()->tableExists('entrepreneur_milestone')) {
        return;
      }

      $title = $experiment->get('title')->value ?? '';
      $database->insert('entrepreneur_milestone')
        ->fields([
          'entrepreneur_id' => (int) $profileId,
          'milestone_type' => 'EXPERIMENT_COMPLETED',
          'description' => "Experimento completado: {$title} (Decision: {$decision})",
          'points_awarded' => $points,
          'related_entity_type' => 'experiment',
          'related_entity_id' => (int) $experiment->id(),
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      // Log but don't fail the request.
    }
  }

  /**
   * Otorga puntos de impacto al perfil del emprendedor.
   */
  protected function awardImpactPoints($experiment, int $points): void {
    try {
      $profileId = $experiment->get('entrepreneur_profile')->target_id;
      if (!$profileId) {
        return;
      }

      $profileStorage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profile = $profileStorage->load($profileId);
      if ($profile) {
        $currentPoints = (int) ($profile->get('impact_points')->value ?? 0);
        $profile->set('impact_points', $currentPoints + $points);
        $profile->save();
      }
    }
    catch (\Exception $e) {
      // Log but don't fail the request
    }
  }

  /**
   * Actualiza el estado de validacion de la hipotesis vinculada.
   */
  protected function updateHypothesisStatus($experiment, string $result): void {
    try {
      $hypothesisId = $experiment->get('hypothesis')->target_id;
      if (!$hypothesisId) {
        return;
      }

      $hypothesisStorage = $this->entityTypeManager->getStorage('hypothesis');
      $hypothesis = $hypothesisStorage->load($hypothesisId);
      if ($hypothesis) {
        $statusMap = [
          'VALIDATED' => 'VALIDATED',
          'INVALIDATED' => 'INVALIDATED',
          'INCONCLUSIVE' => 'INCONCLUSIVE',
        ];
        $hypothesis->set('validation_status', $statusMap[$result] ?? 'INCONCLUSIVE');
        $hypothesis->save();
      }
    }
    catch (\Exception $e) {
      // Log but don't fail the request
    }
  }

  /**
   * Serializa un experimento a array.
   */
  protected function serializeExperiment($experiment, bool $full = FALSE): array {
    $data = [
      'id' => (int) $experiment->id(),
      'title' => $experiment->get('title')->value,
      'experiment_type' => $experiment->get('experiment_type')->value,
      'status' => $experiment->get('status')->value ?? 'PLANNED',
      'decision' => $experiment->get('decision')->value,
      'result' => $experiment->get('result')->value,
      'points_awarded' => (int) ($experiment->get('points_awarded')->value ?? 0),
      'hypothesis_id' => $experiment->get('hypothesis')->target_id,
      'entrepreneur_profile' => $experiment->get('entrepreneur_profile')->target_id,
      'user_id' => (int) $experiment->getOwnerId(),
      'created' => (int) $experiment->get('created')->value,
      'changed' => (int) $experiment->get('changed')->value,
    ];

    if ($full) {
      // Test Card details
      $data['test_card'] = [
        'plan' => $experiment->get('plan')->value,
        'metrics' => $experiment->get('metrics')->value,
        'success_criteria' => $experiment->get('success_criteria')->value,
        'failure_criteria' => $experiment->get('failure_criteria')->value,
        'start_date' => $experiment->get('start_date')->value,
        'end_date' => $experiment->get('end_date')->value,
      ];

      // Learning Card details
      $data['learning_card'] = [
        'observations' => $experiment->get('observations')->value,
        'metrics_results' => $experiment->get('metrics_results')->value,
        'customer_learning' => $experiment->get('customer_learning')->value,
        'problem_learning' => $experiment->get('problem_learning')->value,
        'solution_learning' => $experiment->get('solution_learning')->value,
        'next_steps' => $experiment->get('next_steps')->value,
      ];
    }

    return $data;
  }

}
