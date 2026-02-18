<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\ReviewAgroService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para reseñas de AgroConecta.
 *
 * PROPÓSITO:
 * Expone endpoints JSON para gestión de reseñas desde frontend.
 *
 * ENDPOINTS PÚBLICOS:
 * - GET  /api/v1/agro/reviews?target_type=X&target_id=Y  → Reseñas por target
 * - GET  /api/v1/agro/reviews/stats?target_type=X&target_id=Y  → Rating stats
 *
 * ENDPOINTS AUTENTICADOS:
 * - POST /api/v1/agro/reviews  → Crear reseña
 * - POST /api/v1/agro/reviews/{id}/respond  → Respuesta del productor
 *
 * ENDPOINTS ADMIN:
 * - GET  /api/v1/agro/reviews/pending  → Cola de moderación
 * - POST /api/v1/agro/reviews/{id}/moderate  → Moderar reseña
 */
class ReviewApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected ReviewAgroService $reviewService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.review_service'), // AUDIT-CONS-N05: canonical prefix
        );
    }

    /**
     * Lista las reseñas aprobadas para una entidad.
     *
     * GET /api/v1/agro/reviews?target_type=product_agro&target_id=123
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con query params.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con reseñas paginadas.
     */
    public function list(Request $request): JsonResponse
    {
        $targetType = $request->query->get('target_type', '');
        $targetId = (int) $request->query->get('target_id', 0);

        if (empty($targetType) || $targetId <= 0) {
            return new JsonResponse([
                'error' => 'Parámetros target_type y target_id son requeridos.',
            ], 400);
        }

        $limit = min((int) $request->query->get('limit', 10), 50);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $result = $this->reviewService->getReviewsForTarget($targetType, $targetId, $limit, $offset);

        return new JsonResponse($result);
    }

    /**
     * Obtiene estadísticas de rating para una entidad.
     *
     * GET /api/v1/agro/reviews/stats?target_type=product_agro&target_id=123
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con average, count y distribución.
     */
    public function stats(Request $request): JsonResponse
    {
        $targetType = $request->query->get('target_type', '');
        $targetId = (int) $request->query->get('target_id', 0);

        if (empty($targetType) || $targetId <= 0) {
            return new JsonResponse([
                'error' => 'Parámetros target_type y target_id son requeridos.',
            ], 400);
        }

        $stats = $this->reviewService->getRatingStats($targetType, $targetId);

        return new JsonResponse(['data' => $stats]);
    }

    /**
     * Crea una nueva reseña.
     *
     * POST /api/v1/agro/reviews
     * Body: { type, target_entity_type, target_entity_id, rating, title, body }
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con el cuerpo JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con la reseña creada o error.
     */
    public function createReview(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data)) {
            return new JsonResponse(['error' => 'Cuerpo de la petición vacío.'], 400);
        }

        // Validar campos requeridos.
        $required = ['target_entity_type', 'target_entity_id', 'rating', 'body'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new JsonResponse([
                    'error' => "Campo '{$field}' es requerido.",
                ], 400);
            }
        }

        // Validar rating.
        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            return new JsonResponse([
                'error' => 'La puntuación debe estar entre 1 y 5.',
            ], 422);
        }

        $review = $this->reviewService->createReview($data);

        if (!$review) {
            return new JsonResponse([
                'error' => 'No se pudo crear la reseña. Es posible que ya exista una reseña para este elemento.',
            ], 409);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Reseña creada correctamente. Pendiente de moderación.',
            'data' => $this->reviewService->serializeReview($review),
        ], 201);
    }

    /**
     * Añade respuesta del productor a una reseña.
     *
     * POST /api/v1/agro/reviews/{review_id}/respond
     * Body: { response }
     *
     * @param int $review_id
     *   ID de la reseña.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function respond(int $review_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['response'])) {
            return new JsonResponse([
                'error' => 'El campo response es requerido.',
            ], 400);
        }

        $success = $this->reviewService->addProducerResponse($review_id, $data['response']);

        if (!$success) {
            return new JsonResponse([
                'error' => 'No se pudo añadir la respuesta. La reseña no existe o ya tiene una respuesta.',
            ], 422);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Respuesta del productor añadida correctamente.',
        ]);
    }

    /**
     * Lista las reseñas pendientes de moderación.
     *
     * GET /api/v1/agro/reviews/pending
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con reseñas pendientes.
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 50), 100);
        $reviews = $this->reviewService->getPendingReviews($limit);

        return new JsonResponse([
            'data' => $reviews,
            'meta' => ['count' => count($reviews)],
        ]);
    }

    /**
     * Modera una reseña (aprobar, rechazar, marcar).
     *
     * POST /api/v1/agro/reviews/{review_id}/moderate
     * Body: { state: "approved"|"rejected"|"flagged" }
     *
     * @param int $review_id
     *   ID de la reseña.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function moderate(int $review_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['state'])) {
            return new JsonResponse([
                'error' => 'El campo state es requerido (approved, rejected, flagged).',
            ], 400);
        }

        $success = $this->reviewService->moderate($review_id, $data['state']);

        if (!$success) {
            return new JsonResponse([
                'error' => 'No se pudo moderar la reseña. Verifica el ID y el estado.',
            ], 422);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Reseña moderada correctamente.',
            'state' => $data['state'],
        ]);
    }

}
