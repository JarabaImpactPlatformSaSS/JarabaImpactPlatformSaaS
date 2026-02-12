<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller para CRUD de perfiles de emprendedor + DIME scores.
 */
class EntrepreneurApiController extends ControllerBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * GET /api/v1/entrepreneurs - Lista perfiles.
   */
  public function list(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $query = $storage->getQuery()->accessCheck(TRUE);

      if (!$this->currentUser()->hasPermission('administer entrepreneur profiles')) {
        $query->condition('user_id', $this->currentUser()->id());
      }

      $query->sort('created', 'DESC');
      $ids = $query->execute();
      $profiles = $storage->loadMultiple($ids);

      $data = [];
      foreach ($profiles as $profile) {
        $data[] = $this->serializeProfile($profile);
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
   * POST /api/v1/entrepreneurs - Crea perfil.
   */
  public function create(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data['name'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El campo name es obligatorio.',
        ], 400);
      }

      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profile = $storage->create([
        'name' => $data['name'],
        'carril' => $data['carril'] ?? 'IMPULSO',
        'phase' => $data['phase'] ?? 'INVENTARIO',
        'sector' => $data['sector'] ?? '',
        'idea_description' => $data['idea_description'] ?? '',
        'nivel_tecnico' => $data['nivel_tecnico'] ?? '',
        'program_start_date' => $data['program_start_date'] ?? date('Y-m-d\TH:i:s'),
        'impact_points' => 0,
        'dime_score' => 0,
        'user_id' => $this->currentUser()->id(),
      ]);

      $profile->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeProfile($profile),
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
   * GET /api/v1/entrepreneurs/{id} - Detalle del perfil.
   */
  public function get(string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profile = $storage->load($id);

      if (!$profile) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Perfil no encontrado.',
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeProfile($profile),
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
   * PATCH /api/v1/entrepreneurs/{id} - Actualiza perfil.
   */
  public function update(Request $request, string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profile = $storage->load($id);

      if (!$profile) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Perfil no encontrado.',
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      $allowedFields = [
        'name', 'carril', 'phase', 'sector',
        'idea_description', 'nivel_tecnico', 'detected_blockages',
      ];

      foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
          $value = $data[$field];
          if ($field === 'detected_blockages' && is_array($value)) {
            $value = json_encode($value);
          }
          $profile->set($field, $value);
        }
      }

      $profile->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeProfile($profile),
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
   * POST /api/v1/entrepreneurs/{id}/dime - Submit DIME scores.
   */
  public function submitDime(Request $request, string $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
      $profile = $storage->load($id);

      if (!$profile) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Perfil no encontrado.',
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);

      $digital = max(0, min(5, (int) ($data['digital'] ?? 0)));
      $idea = max(0, min(5, (int) ($data['idea'] ?? 0)));
      $mercado = max(0, min(5, (int) ($data['mercado'] ?? 0)));
      $emocional = max(0, min(5, (int) ($data['emocional'] ?? 0)));
      $total = $digital + $idea + $mercado + $emocional;

      $profile->set('dime_digital', $digital);
      $profile->set('dime_idea', $idea);
      $profile->set('dime_mercado', $mercado);
      $profile->set('dime_emocional', $emocional);
      $profile->set('dime_score', $total);

      // Asignar carril basado en DIME total
      $carril = $total >= 10 ? 'ACELERA' : 'IMPULSO';
      $profile->set('carril', $carril);

      $profile->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'dime_score' => $total,
          'dime_digital' => $digital,
          'dime_idea' => $idea,
          'dime_mercado' => $mercado,
          'dime_emocional' => $emocional,
          'carril' => $carril,
        ],
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
   * Serializa un perfil a array.
   */
  protected function serializeProfile($profile): array {
    $blockages = $profile->get('detected_blockages')->value;
    $blockagesArray = [];
    if ($blockages) {
      $decoded = json_decode($blockages, TRUE);
      $blockagesArray = is_array($decoded) ? $decoded : [];
    }

    return [
      'id' => (int) $profile->id(),
      'name' => $profile->get('name')->value,
      'carril' => $profile->get('carril')->value,
      'dime_score' => (int) ($profile->get('dime_score')->value ?? 0),
      'dime_digital' => (int) ($profile->get('dime_digital')->value ?? 0),
      'dime_idea' => (int) ($profile->get('dime_idea')->value ?? 0),
      'dime_mercado' => (int) ($profile->get('dime_mercado')->value ?? 0),
      'dime_emocional' => (int) ($profile->get('dime_emocional')->value ?? 0),
      'phase' => $profile->get('phase')->value,
      'impact_points' => (int) ($profile->get('impact_points')->value ?? 0),
      'sector' => $profile->get('sector')->value,
      'idea_description' => $profile->get('idea_description')->value,
      'nivel_tecnico' => $profile->get('nivel_tecnico')->value,
      'detected_blockages' => $blockagesArray,
      'program_start_date' => $profile->get('program_start_date')->value,
      'user_id' => (int) $profile->getOwnerId(),
      'created' => (int) $profile->get('created')->value,
      'changed' => (int) $profile->get('changed')->value,
    ];
  }

}
