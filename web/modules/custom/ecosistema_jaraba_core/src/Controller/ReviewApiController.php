<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAnalyticsService;
use Drupal\ecosistema_jaraba_core\Service\ReviewHelpfulnessService;
use Drupal\ecosistema_jaraba_core\Service\ReviewModerationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API REST para el sistema de resenas.
 *
 * Endpoints:
 * - POST /api/v1/reviews/{type}/{id}/vote — Voto de utilidad (B-01)
 * - GET  /api/v1/reviews/{type}/list — Lista filtrada/ordenada (B-02)
 * - POST /api/v1/reviews/{type}/{id}/response — Respuesta propietario (B-04)
 * - GET  /api/v1/reviews/analytics — Dashboard metricas (B-05)
 * - GET  /api/v1/reviews/analytics/export — CSV export (B-05)
 */
class ReviewApiController extends ControllerBase
{

  /**
   * Campo de estado por tipo de entidad.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'state',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
    'content_comment' => 'review_status',
  ];

  /**
   * Campo de rating por tipo de entidad.
   */
  private const RATING_FIELD_MAP = [
    'comercio_review' => 'rating',
    'review_agro' => 'rating',
    'review_servicios' => 'rating',
    'session_review' => 'overall_rating',
    'course_review' => 'rating',
  ];

  /**
   * Campo de target ID por tipo de entidad.
   */
  private const TARGET_ID_FIELD_MAP = [
    'comercio_review' => 'entity_id_ref',
    'review_agro' => 'target_entity_id',
    'review_servicios' => 'provider_id',
    'session_review' => 'session_id',
    'course_review' => 'course_id',
  ];

  /**
   * Campo de respuesta del propietario.
   */
  private const RESPONSE_FIELD_MAP = [
    'comercio_review' => 'merchant_response',
    'review_agro' => 'response',
    'review_servicios' => 'provider_response',
    'course_review' => 'instructor_response',
  ];

  /**
   * Campo de fecha de respuesta.
   */
  private const RESPONSE_DATE_FIELD_MAP = [
    'comercio_review' => 'merchant_response_date',
    'review_agro' => 'response_by',
    'review_servicios' => 'response_date',
    'course_review' => 'instructor_response_date',
  ];

  public function __construct(
    protected readonly ReviewHelpfulnessService $helpfulnessService,
    protected readonly ReviewAnalyticsService $analyticsService,
    protected readonly ReviewModerationService $moderationService,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('ecosistema_jaraba_core.review_helpfulness'),
      $container->get('ecosistema_jaraba_core.review_analytics'),
      $container->get('ecosistema_jaraba_core.review_moderation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * B-01: Voto de utilidad.
   *
   * POST /api/v1/reviews/{review_type}/{review_id}/vote
   * Body: { "helpful": true|false }
   */
  public function vote(string $review_type, int $review_id, Request $request): JsonResponse
  {
    if (!isset(self::STATUS_FIELD_MAP[$review_type])) {
      return new JsonResponse(['error' => 'Unsupported review type.'], 400);
    }

    $content = json_decode($request->getContent(), TRUE);
    $helpful = (bool) ($content['helpful'] ?? TRUE);

    $result = $this->helpfulnessService->vote($review_type, $review_id, $helpful);

    if (!($result['success'] ?? FALSE)) {
      $error = $result['error'] ?? 'unknown';
      $code = $error === 'authentication_required' ? 401 : 500;
      return new JsonResponse(['error' => $error], $code);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'helpful_count' => $result['helpful_count'],
        'not_helpful_count' => $result['not_helpful_count'],
        'wilson_score' => $result['wilson_score'],
        'user_vote' => $result['user_vote'],
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * B-02: Lista filtrada y ordenada de resenas.
   *
   * GET /api/v1/reviews/{review_type}/list?target_id=X&stars=4&sort=helpful&has_photos=1&verified=1&page=0&limit=10
   */
  public function listFiltered(string $review_type, Request $request): JsonResponse
  {
    if (!isset(self::STATUS_FIELD_MAP[$review_type])) {
      return new JsonResponse(['error' => 'Unsupported review type.'], 400);
    }

    $targetId = (int) $request->query->get('target_id', 0);
    $stars = $request->query->get('stars');
    $sort = $request->query->get('sort', 'newest');
    $hasPhotos = $request->query->get('has_photos');
    $verified = $request->query->get('verified');
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
    $sentiment = $request->query->get('sentiment');

    $statusField = self::STATUS_FIELD_MAP[$review_type];
    $ratingField = self::RATING_FIELD_MAP[$review_type] ?? NULL;
    $targetIdField = self::TARGET_ID_FIELD_MAP[$review_type] ?? NULL;

    try {
      $storage = $this->entityTypeManager()->getStorage($review_type);
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition($statusField, 'approved');

      // Filtro por target.
      if ($targetId > 0 && $targetIdField !== NULL) {
        $query->condition($targetIdField, $targetId);
      }

      // Filtro por estrellas.
      if ($stars !== NULL && $ratingField !== NULL) {
        $query->condition($ratingField, (int) $stars);
      }

      // Filtro por fotos.
      if ($hasPhotos !== NULL && (bool) $hasPhotos) {
        $query->condition('photos', '', '<>');
        $query->condition('photos', NULL, 'IS NOT NULL');
      }

      // Filtro por compra verificada.
      if ($verified !== NULL && (bool) $verified) {
        $query->condition('verified_purchase', 1);
      }

      // Filtro por sentiment.
      if ($sentiment !== NULL) {
        $query->condition('sentiment', $sentiment);
      }

      // Ordenacion.
      switch ($sort) {
        case 'helpful':
          $query->sort('helpful_count', 'DESC');
          break;

        case 'highest':
          if ($ratingField !== NULL) {
            $query->sort($ratingField, 'DESC');
          }
          break;

        case 'lowest':
          if ($ratingField !== NULL) {
            $query->sort($ratingField, 'ASC');
          }
          break;

        case 'oldest':
          $query->sort('created', 'ASC');
          break;

        case 'newest':
        default:
          $query->sort('created', 'DESC');
          break;
      }

      // Conteo total antes de paginar.
      $countQuery = clone $query;
      $totalCount = $countQuery->count()->execute();

      // Paginacion.
      $query->range($page * $limit, $limit);
      $ids = $query->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];

      $reviews = [];
      foreach ($entities as $entity) {
        $responseField = self::RESPONSE_FIELD_MAP[$review_type] ?? NULL;
        $reviews[] = [
          'id' => (int) $entity->id(),
          'rating' => method_exists($entity, 'getReviewRating') ? $entity->getReviewRating() : 0,
          'title' => $entity->hasField('title') ? ($entity->get('title')->value ?? '') : '',
          'body' => $entity->hasField('body') ? ($entity->get('body')->value ?? '') : ($entity->hasField('comment') ? ($entity->get('comment')->value ?? '') : ''),
          'helpful_count' => $entity->hasField('helpful_count') ? (int) ($entity->get('helpful_count')->value ?? 0) : 0,
          'verified_purchase' => $entity->hasField('verified_purchase') ? (bool) $entity->get('verified_purchase')->value : FALSE,
          'has_photos' => $entity->hasField('photos') && !$entity->get('photos')->isEmpty(),
          'photos' => method_exists($entity, 'getReviewPhotos') ? $entity->getReviewPhotos() : [],
          'sentiment' => $entity->hasField('sentiment') ? ($entity->get('sentiment')->value ?? NULL) : NULL,
          'created' => (int) ($entity->get('created')->value ?? 0),
          'author' => $entity->hasField('uid') ? ($entity->getOwner()->getDisplayName() ?? '') : '',
          'has_response' => $responseField !== NULL && $entity->hasField($responseField) && !$entity->get($responseField)->isEmpty(),
          'response' => $responseField !== NULL && $entity->hasField($responseField) ? ($entity->get($responseField)->value ?? NULL) : NULL,
        ];
      }

      // Contadores por estrellas para filtros.
      $starCounts = [];
      if ($ratingField !== NULL && $targetId > 0 && $targetIdField !== NULL) {
        for ($s = 1; $s <= 5; $s++) {
          $scQuery = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition($statusField, 'approved')
            ->condition($targetIdField, $targetId)
            ->condition($ratingField, $s)
            ->count();
          $starCounts[$s] = (int) $scQuery->execute();
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $reviews,
        'meta' => [
          'total' => $totalCount,
          'page' => $page,
          'limit' => $limit,
          'pages' => (int) ceil($totalCount / $limit),
          'star_counts' => $starCounts,
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error fetching reviews.'], 500);
    }
  }

  /**
   * B-04: Respuesta del propietario.
   *
   * POST /api/v1/reviews/{review_type}/{review_id}/response
   * Body: { "response": "Gracias por tu resena..." }
   */
  public function ownerResponse(string $review_type, int $review_id, Request $request): JsonResponse
  {
    $responseField = self::RESPONSE_FIELD_MAP[$review_type] ?? NULL;
    if ($responseField === NULL) {
      return new JsonResponse(['error' => 'Response not supported for this review type.'], 400);
    }

    $content = json_decode($request->getContent(), TRUE);
    $responseText = trim($content['response'] ?? '');

    if ($responseText === '') {
      return new JsonResponse(['error' => 'Response text is required.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage($review_type);
      $entity = $storage->load($review_id);
      if ($entity === NULL) {
        return new JsonResponse(['error' => 'Review not found.'], 404);
      }

      // ACCESS-STRICT-001: Verify current user owns the target entity.
      $currentUid = (int) $this->currentUser()->id();
      $isAdmin = $this->currentUser()->hasPermission('administer site configuration');
      if (!$isAdmin) {
        $targetIdField = self::TARGET_ID_FIELD_MAP[$review_type] ?? NULL;
        if ($targetIdField !== NULL && $entity->hasField($targetIdField)) {
          $targetId = (int) ($entity->get($targetIdField)->target_id ?? $entity->get($targetIdField)->value ?? 0);
          if ($targetId > 0) {
            $targetOwnerUid = $this->resolveTargetOwner($review_type, $targetId);
            if ($targetOwnerUid !== NULL && $currentUid !== $targetOwnerUid) {
              return new JsonResponse(['error' => 'Only the owner of the reviewed entity can respond.'], 403);
            }
          }
        }
      }

      if (!$entity->hasField($responseField)) {
        return new JsonResponse(['error' => 'Response field not available.'], 400);
      }

      $entity->set($responseField, $responseText);

      // Fecha de respuesta si existe.
      $responseDateField = self::RESPONSE_DATE_FIELD_MAP[$review_type] ?? NULL;
      if ($responseDateField !== NULL && $entity->hasField($responseDateField)) {
        $entity->set($responseDateField, time());
      }

      // Also set the trait-level fields if present.
      if ($entity->hasField('owner_response')) {
        $entity->set('owner_response', $responseText);
      }
      if ($entity->hasField('owner_response_at')) {
        $entity->set('owner_response_at', time());
      }

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'review_id' => $review_id,
          'response' => $responseText,
          'response_date' => time(),
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error saving response.'], 500);
    }
  }

  /**
   * Resolve the owner UID of the target entity.
   *
   * TARGET_ENTITY_TYPE_MAP maps review types to target entity types.
   *
   * @return int|null
   *   Owner UID, or NULL if not resolvable.
   */
  protected function resolveTargetOwner(string $reviewType, int $targetId): ?int
  {
    $targetEntityTypes = [
      'comercio_review' => 'merchant_profile',
      'review_agro' => 'producer_profile',
      'review_servicios' => 'provider_profile',
      'course_review' => 'lms_course',
    ];

    $targetEntityType = $targetEntityTypes[$reviewType] ?? NULL;
    if ($targetEntityType === NULL) {
      return NULL;
    }

    try {
      $target = $this->entityTypeManager()->getStorage($targetEntityType)->load($targetId);
      if ($target === NULL) {
        return NULL;
      }
      if ($target->hasField('uid') && !$target->get('uid')->isEmpty()) {
        return (int) ($target->get('uid')->target_id ?? 0);
      }
    } catch (\Exception) {
    }

    return NULL;
  }

  /**
   * B-05: Dashboard de analiticas.
   *
   * GET /api/v1/reviews/analytics?vertical=comercio_review&tenant_id=1&days=30
   */
  public function analytics(Request $request): JsonResponse
  {
    $vertical = $request->query->get('vertical');
    $tenantGroupId = $request->query->get('tenant_id') ? (int) $request->query->get('tenant_id') : NULL;
    $days = min(365, max(7, (int) $request->query->get('days', 30)));

    $metrics = $this->analyticsService->getDashboardMetrics($vertical, $tenantGroupId, $days);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $metrics,
      'meta' => [
        'timestamp' => time(),
        'period_days' => $days,
      ],
    ]);
  }

  /**
   * B-05: Export CSV.
   *
   * GET /api/v1/reviews/analytics/export?vertical=comercio_review&tenant_id=1
   */
  public function analyticsExport(Request $request): Response
  {
    $vertical = $request->query->get('vertical');
    $tenantGroupId = $request->query->get('tenant_id') ? (int) $request->query->get('tenant_id') : NULL;

    $types = $vertical !== NULL ? [$vertical] : [
      'comercio_review',
      'review_agro',
      'review_servicios',
      'session_review',
      'course_review',
      'content_comment',
    ];

    $rows = $this->analyticsService->exportCsv($types, $tenantGroupId);

    $response = new StreamedResponse(function () use ($rows) {
      $handle = fopen('php://output', 'w');
      foreach ($rows as $row) {
        fputcsv($handle, $row);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="reviews-export-' . date('Y-m-d') . '.csv"');

    return $response;
  }

  /**
   * Item 19: Report abuse/flag a review.
   *
   * POST /api/v1/reviews/{review_type}/{review_id}/report
   * Body: { "reason": "spam|offensive|fake|other", "details": "..." }
   */
  public function reportAbuse(string $review_type, int $review_id, Request $request): JsonResponse
  {
    if (!isset(self::STATUS_FIELD_MAP[$review_type])) {
      return new JsonResponse(['error' => 'Unsupported review type.'], 400);
    }

    $content = json_decode($request->getContent(), TRUE);
    $reason = $content['reason'] ?? 'other';
    $details = trim($content['details'] ?? '');

    $allowedReasons = ['spam', 'offensive', 'fake', 'other'];
    if (!in_array($reason, $allowedReasons, TRUE)) {
      return new JsonResponse(['error' => 'Invalid reason. Allowed: ' . implode(', ', $allowedReasons)], 400);
    }

    $result = $this->moderationService->reportAbuse(
      $review_type,
      $review_id,
      (int) $this->currentUser()->id(),
      $reason,
      $details,
    );

    if (!($result['success'] ?? FALSE)) {
      $code = ($result['error'] ?? '') === 'already_reported' ? 409 : 500;
      return new JsonResponse(['error' => $result['error'] ?? 'Error reporting abuse.'], $code);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'review_id' => $review_id,
        'reason' => $reason,
        'status' => 'pending_review',
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * Item 20: Edit a review by its author.
   *
   * PATCH /api/v1/reviews/{review_type}/{review_id}
   * Body: { "rating": 4, "body": "Updated text..." }
   */
  public function editReview(string $review_type, int $review_id, Request $request): JsonResponse
  {
    if (!isset(self::STATUS_FIELD_MAP[$review_type])) {
      return new JsonResponse(['error' => 'Unsupported review type.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage($review_type);
      $entity = $storage->load($review_id);
      if ($entity === NULL) {
        return new JsonResponse(['error' => 'Review not found.'], 404);
      }

      // Only the author or admin can edit.
      $currentUid = (int) $this->currentUser()->id();
      $isAdmin = $this->currentUser()->hasPermission('administer site configuration');
      $authorUid = $entity->hasField('uid') ? (int) ($entity->get('uid')->target_id ?? 0) : 0;
      if (!$isAdmin && $currentUid !== $authorUid) {
        return new JsonResponse(['error' => 'Only the review author can edit.'], 403);
      }

      $content = json_decode($request->getContent(), TRUE);

      // Update rating if provided.
      $ratingField = self::RATING_FIELD_MAP[$review_type] ?? NULL;
      if (isset($content['rating']) && $ratingField !== NULL && $entity->hasField($ratingField)) {
        $rating = min(5, max(1, (int) $content['rating']));
        $entity->set($ratingField, $rating);
      }

      // Update body if provided.
      if (isset($content['body'])) {
        $bodyField = $entity->hasField('body') ? 'body' : ($entity->hasField('comment') ? 'comment' : NULL);
        if ($bodyField !== NULL) {
          $entity->set($bodyField, $content['body']);
        }
      }

      // Mark as pending re-moderation after edit.
      $statusField = self::STATUS_FIELD_MAP[$review_type];
      if ($entity->hasField($statusField) && !$isAdmin) {
        $entity->set($statusField, 'pending');
      }

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['review_id' => $review_id, 'status' => 'updated'],
        'meta' => ['timestamp' => time()],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error updating review.'], 500);
    }
  }
  /**
   * Delete a review by its author or admin.
   *
   * DELETE /api/v1/reviews/{review_type}/{review_id}
   */
  public function deleteReview(string $review_type, int $review_id, Request $request): JsonResponse
  {
    if (!isset(self::STATUS_FIELD_MAP[$review_type])) {
      return new JsonResponse(['error' => 'Unsupported review type.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage($review_type);
      $entity = $storage->load($review_id);
      if ($entity === NULL) {
        return new JsonResponse(['error' => 'Review not found.'], 404);
      }

      // Only the author or admin can delete.
      $currentUid = (int) $this->currentUser()->id();
      $isAdmin = $this->currentUser()->hasPermission('administer site configuration');
      $authorUid = $entity->hasField('uid') ? (int) ($entity->get('uid')->target_id ?? 0) : 0;
      if (!$isAdmin && $currentUid !== $authorUid) {
        return new JsonResponse(['error' => 'Only the review author or an admin can delete.'], 403);
      }

      $entity->delete();

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['review_id' => $review_id, 'status' => 'deleted'],
        'meta' => ['timestamp' => time()],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error deleting review.'], 500);
    }
  }

}
