<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador de API REST para itinerarios de digitalización.
 *
 * Implementa endpoints /api/v1/paths/* según spec 28.
 */
class PathApiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static();
    }

    /**
     * Lista itinerarios publicados.
     *
     * GET /api/v1/paths
     */
    public function listPaths(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('digitalization_path');

        $query = $storage->getQuery()
            ->condition('status', TRUE)
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        // Filtros
        if ($sector = $request->query->get('sector')) {
            $query->condition('target_sector', $sector);
        }

        if ($maturity = $request->query->get('maturity_level')) {
            $query->condition('target_maturity_level', $maturity);
        }

        // Paginación
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        $countQuery = clone $query;
        $total = $countQuery->count()->execute();

        $ids = $query->range($offset, $limit)->execute();
        $paths = $storage->loadMultiple($ids);

        $items = [];
        foreach ($paths as $path) {
            /** @var \Drupal\jaraba_paths\Entity\DigitalizationPathInterface $path */
            $items[] = [
                'uuid' => $path->uuid(),
                'title' => $path->getTitle(),
                'sector' => $path->getTargetSector(),
                'maturity_level' => $path->getTargetMaturityLevel(),
                'estimated_weeks' => $path->getEstimatedWeeks(),
                'is_featured' => $path->isFeatured(),
            ];
        }

        return new JsonResponse(['success' => TRUE, 'data' => $items, 'meta' => [
                'total' => (int) $total,
                'page' => $page,
                'limit' => $limit,
            ]]);
    }

    /**
     * Obtiene un itinerario por UUID.
     *
     * GET /api/v1/paths/{uuid}
     */
    public function getPath(string $uuid): JsonResponse
    {
        $path = $this->loadByUuid($uuid);

        if (!$path) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Path not found']], 404);
        }

        return new JsonResponse([
            'data' => [
                'uuid' => $path->uuid(),
                'title' => $path->getTitle(),
                'description' => $path->get('description')->value,
                'sector' => $path->getTargetSector(),
                'maturity_level' => $path->getTargetMaturityLevel(),
                'estimated_weeks' => $path->getEstimatedWeeks(),
                'difficulty' => $path->get('difficulty_level')->value,
                'is_featured' => $path->isFeatured(),
            ],
        ]);
    }

    /**
     * Inscribe al usuario actual en un itinerario.
     *
     * POST /api/v1/paths/{uuid}/enroll
     */
    public function enroll(string $uuid): JsonResponse
    {
        $path = $this->loadByUuid($uuid);

        if (!$path) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Path not found']], 404);
        }

        $enrollmentService = \Drupal::service('jaraba_paths.enrollment');
        $result = $enrollmentService->enroll(
            (int) $this->currentUser()->id(),
            (int) $path->id()
        );

        return new JsonResponse([
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Obtiene el progreso de una inscripción.
     *
     * GET /api/v1/enrollments/{uuid}/progress
     */
    public function getProgress(string $uuid): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('path_enrollment');
        $enrollments = $storage->loadByProperties(['uuid' => $uuid]);
        $enrollment = !empty($enrollments) ? reset($enrollments) : NULL;

        if (!$enrollment) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Enrollment not found']], 404);
        }

        return new JsonResponse([
            'data' => [
                'progress_percent' => $enrollment->getProgressPercent(),
                'steps_completed' => (int) $enrollment->get('steps_completed')->value,
                'status' => $enrollment->get('status')->value,
                'xp_earned' => (int) $enrollment->get('xp_earned')->value,
                'streak_days' => (int) $enrollment->get('streak_days')->value,
            ],
        ]);
    }

    /**
     * Marca un paso como completado.
     *
     * POST /api/v1/enrollments/{enrollment_uuid}/steps/{step_id}/complete
     */
    public function completeStep(string $enrollment_uuid, int $step_id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('path_enrollment');
        $enrollments = $storage->loadByProperties(['uuid' => $enrollment_uuid]);
        $enrollment = !empty($enrollments) ? reset($enrollments) : NULL;

        if (!$enrollment) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Enrollment not found']], 404);
        }

        $progressService = \Drupal::service('jaraba_paths.progress');
        $result = $progressService->completeStep((int) $enrollment->id(), $step_id);

        return new JsonResponse([
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Obtiene recomendaciones de paths.
     *
     * GET /api/v1/paths/recommendations
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $sector = $request->query->get('sector', 'general');
        $maturity = $request->query->get('maturity_level');

        $recommendationService = \Drupal::service('jaraba_paths.recommendation');
        $recommendations = $recommendationService->findMatchingPaths($sector, $maturity);

        return new JsonResponse([
            'data' => array_slice($recommendations, 0, 5),
        ]);
    }

    /**
     * Carga path por UUID.
     */
    protected function loadByUuid(string $uuid): ?object
    {
        $storage = $this->entityTypeManager()->getStorage('digitalization_path');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);
        return !empty($entities) ? reset($entities) : NULL;
    }

}
