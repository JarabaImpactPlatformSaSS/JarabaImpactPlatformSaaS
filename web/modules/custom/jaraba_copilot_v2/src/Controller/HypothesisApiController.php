<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller para CRUD de hipotesis + priorizacion ICE.
 */
class HypothesisApiController extends ControllerBase {

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Hypothesis prioritization service.
   */
  protected HypothesisPrioritizationService $prioritization;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    HypothesisPrioritizationService $prioritization
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->prioritization = $prioritization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_copilot_v2.hypothesis_prioritization'),
    );
  }

  /**
   * GET /api/v1/hypotheses - Lista hipotesis con filtros.
   */
  public function list(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('hypothesis');
      $query = $storage->getQuery()->accessCheck(TRUE);

      // Filtros
      $profileId = $request->query->get('profile');
      if ($profileId) {
        $query->condition('entrepreneur_profile', (int) $profileId);
      }

      $type = $request->query->get('type');
      if ($type && in_array($type, ['DESIRABILITY', 'FEASIBILITY', 'VIABILITY'])) {
        $query->condition('hypothesis_type', $type);
      }

      $status = $request->query->get('status');
      if ($status && in_array($status, ['PENDING', 'IN_PROGRESS', 'VALIDATED', 'INVALIDATED', 'INCONCLUSIVE'])) {
        $query->condition('validation_status', $status);
      }

      $bmcBlock = $request->query->get('bmc_block');
      if ($bmcBlock && in_array($bmcBlock, ['CS', 'VP', 'CH', 'CR', 'RS', 'KR', 'KA', 'KP', 'C$'])) {
        $query->condition('bmc_block', $bmcBlock);
      }

      // Filtrar por usuario actual si no es admin
      if (!$this->currentUser()->hasPermission('administer hypotheses') && !$profileId) {
        $query->condition('user_id', $this->currentUser()->id());
      }

      $query->sort('created', 'DESC');
      $ids = $query->execute();
      $hypotheses = $storage->loadMultiple($ids);

      $data = [];
      foreach ($hypotheses as $hypothesis) {
        $data[] = $this->serializeHypothesis($hypothesis);
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
   * POST /api/v1/hypotheses - Crea una nueva hipotesis.
   */
  public function create(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['statement'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El campo statement es obligatorio.',
        ], 400);
      }

      $validTypes = ['DESIRABILITY', 'FEASIBILITY', 'VIABILITY'];
      if (empty($data['hypothesis_type']) || !in_array($data['hypothesis_type'], $validTypes)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'hypothesis_type debe ser DESIRABILITY, FEASIBILITY o VIABILITY.',
        ], 400);
      }

      $validBlocks = ['CS', 'VP', 'CH', 'CR', 'RS', 'KR', 'KA', 'KP', 'C$'];
      if (empty($data['bmc_block']) || !in_array($data['bmc_block'], $validBlocks)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'bmc_block debe ser uno de: CS, VP, CH, CR, RS, KR, KA, KP, C$.',
        ], 400);
      }

      $storage = $this->entityTypeManager->getStorage('hypothesis');
      $hypothesis = $storage->create([
        'statement' => $data['statement'],
        'hypothesis_type' => $data['hypothesis_type'],
        'bmc_block' => $data['bmc_block'],
        'importance_score' => max(1, min(5, (int) ($data['importance_score'] ?? 3))),
        'evidence_score' => max(1, min(5, (int) ($data['evidence_score'] ?? 1))),
        'validation_status' => 'PENDING',
        'entrepreneur_profile' => $data['entrepreneur_profile'] ?? NULL,
        'suggested_experiment' => $data['suggested_experiment'] ?? NULL,
        'user_id' => $this->currentUser()->id(),
      ]);

      $hypothesis->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeHypothesis($hypothesis),
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
   * GET /api/v1/hypotheses/{id} - Detalle de una hipotesis.
   */
  public function get(string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('hypothesis');
      $hypothesis = $storage->load($id);

      if (!$hypothesis) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Hipotesis no encontrada.',
        ], 404);
      }

      $data = $this->serializeHypothesis($hypothesis);

      // Incluir experimentos asociados
      $experimentStorage = $this->entityTypeManager->getStorage('experiment');
      $experimentIds = $experimentStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('hypothesis', $id)
        ->sort('created', 'DESC')
        ->execute();

      $experiments = [];
      if (!empty($experimentIds)) {
        foreach ($experimentStorage->loadMultiple($experimentIds) as $experiment) {
          $experiments[] = [
            'id' => (int) $experiment->id(),
            'title' => $experiment->get('title')->value,
            'experiment_type' => $experiment->get('experiment_type')->value,
            'status' => $experiment->get('status')->value,
            'decision' => $experiment->get('decision')->value,
            'points_awarded' => (int) ($experiment->get('points_awarded')->value ?? 0),
          ];
        }
      }

      $data['experiments'] = $experiments;

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
   * PATCH /api/v1/hypotheses/{id} - Actualiza campos parcialmente.
   */
  public function update(Request $request, string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('hypothesis');
      $hypothesis = $storage->load($id);

      if (!$hypothesis) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Hipotesis no encontrada.',
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      $allowedFields = [
        'statement', 'hypothesis_type', 'bmc_block',
        'importance_score', 'evidence_score', 'validation_status',
        'suggested_experiment',
      ];

      foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
          if (in_array($field, ['importance_score', 'evidence_score'])) {
            $hypothesis->set($field, max(1, min(5, (int) $data[$field])));
          }
          else {
            $hypothesis->set($field, $data[$field]);
          }
        }
      }

      $hypothesis->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeHypothesis($hypothesis),
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
   * POST /api/v1/hypotheses/prioritize - Ordena por ICE score.
   */
  public function prioritize(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (!empty($data['hypothesis_ids'])) {
        $result = $this->prioritization->prioritize($data['hypothesis_ids']);
      }
      elseif (!empty($data['profile_id'])) {
        $result = $this->prioritization->prioritizeByProfile(
          (int) $data['profile_id'],
          $data['bmc_block'] ?? NULL,
        );
      }
      else {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Proporciona hypothesis_ids o profile_id.',
        ], 400);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'count' => count($result),
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
   * Serializa una hipotesis a array.
   */
  protected function serializeHypothesis($hypothesis): array {
    return [
      'id' => (int) $hypothesis->id(),
      'statement' => $hypothesis->get('statement')->value,
      'hypothesis_type' => $hypothesis->get('hypothesis_type')->value,
      'bmc_block' => $hypothesis->get('bmc_block')->value,
      'importance_score' => (int) ($hypothesis->get('importance_score')->value ?? 0),
      'evidence_score' => (int) ($hypothesis->get('evidence_score')->value ?? 0),
      'validation_status' => $hypothesis->get('validation_status')->value ?? 'PENDING',
      'suggested_experiment' => $hypothesis->get('suggested_experiment')->value,
      'entrepreneur_profile' => $hypothesis->get('entrepreneur_profile')->target_id,
      'user_id' => (int) $hypothesis->getOwnerId(),
      'created' => (int) $hypothesis->get('created')->value,
      'changed' => (int) $hypothesis->get('changed')->value,
    ];
  }

}
