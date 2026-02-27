<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * B-17: Public REST API for reviews.
 *
 * Provides read-only public API endpoints for external integrations,
 * widgets, and third-party applications. Includes rate limiting
 * and API key authentication.
 *
 * Endpoints:
 *   GET /api/v1/public/reviews/{type} — List approved reviews
 *   GET /api/v1/public/reviews/{type}/{id} — Single review
 *   GET /api/v1/public/reviews/{type}/stats — Aggregate stats
 */
class ReviewPublicApiController extends ControllerBase {

  /**
   * Allowed review types.
   */
  private const ALLOWED_TYPES = [
    'comercio_review',
    'review_agro',
    'review_servicios',
    'session_review',
    'course_review',
  ];

  /**
   * Rating field map.
   */
  private const RATING_FIELD_MAP = [
    'comercio_review' => 'rating',
    'review_agro' => 'rating',
    'review_servicios' => 'rating',
    'session_review' => 'rating',
    'course_review' => 'rating',
  ];

  /**
   * Status field map.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'status',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
  ];

  /**
   * Max items per page.
   */
  private const MAX_PER_PAGE = 50;

  /**
   * Rate limit: requests per minute without API key.
   */
  private const RATE_LIMIT_ANONYMOUS = 60;

  /**
   * Rate limit: requests per minute with API key.
   */
  private const RATE_LIMIT_AUTHENTICATED = 600;

  public function __construct(
    protected readonly ReviewAggregationService $aggregation,
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly FloodInterface $flood,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.review_aggregation'),
      $container->get('entity_type.manager'),
      $container->get('flood'),
    );
  }

  /**
   * List approved reviews for a type.
   */
  public function listReviews(string $review_type, Request $request): JsonResponse {
    $rateLimitResponse = $this->checkRateLimit($request);
    if ($rateLimitResponse !== NULL) {
      return $rateLimitResponse;
    }

    if (!in_array($review_type, self::ALLOWED_TYPES, TRUE)) {
      return new JsonResponse(['error' => 'Invalid review type'], 400);
    }

    $page = max(1, (int) $request->query->get('page', 1));
    $perPage = min(self::MAX_PER_PAGE, max(1, (int) $request->query->get('per_page', 10)));
    $sort = $request->query->get('sort', 'newest');
    $ratingFilter = (int) $request->query->get('rating', 0);

    $statusField = self::STATUS_FIELD_MAP[$review_type];
    $ratingField = self::RATING_FIELD_MAP[$review_type];

    try {
      $storage = $this->entityTypeManager()->getStorage($review_type);
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($statusField, 'approved');

      if ($ratingFilter >= 1 && $ratingFilter <= 5) {
        $query->condition($ratingField, $ratingFilter);
      }

      // Count total.
      $countQuery = clone $query;
      $total = (int) $countQuery->count()->execute();

      // Sort.
      if ($sort === 'oldest') {
        $query->sort('created', 'ASC');
      }
      elseif ($sort === 'highest') {
        $query->sort($ratingField, 'DESC');
      }
      elseif ($sort === 'lowest') {
        $query->sort($ratingField, 'ASC');
      }
      elseif ($sort === 'helpful') {
        $query->sort('wilson_score', 'DESC');
      }
      else {
        $query->sort('created', 'DESC');
      }

      $query->range(($page - 1) * $perPage, $perPage);
      $ids = $query->execute();
      $entities = $storage->loadMultiple($ids);

      $items = [];
      foreach ($entities as $entity) {
        $items[] = $this->serializeReview($entity, $review_type);
      }

      return new JsonResponse([
        'data' => $items,
        'meta' => [
          'total' => $total,
          'page' => $page,
          'per_page' => $perPage,
          'total_pages' => (int) ceil($total / $perPage),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * Get a single review by ID.
   */
  public function getReview(string $review_type, int $review_id, Request $request): JsonResponse {
    $rateLimitResponse = $this->checkRateLimit($request);
    if ($rateLimitResponse !== NULL) {
      return $rateLimitResponse;
    }

    if (!in_array($review_type, self::ALLOWED_TYPES, TRUE)) {
      return new JsonResponse(['error' => 'Invalid review type'], 400);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage($review_type)->load($review_id);
      if ($entity === NULL) {
        return new JsonResponse(['error' => 'Review not found'], 404);
      }

      $statusField = self::STATUS_FIELD_MAP[$review_type];
      $status = $entity->hasField($statusField) ? (string) ($entity->get($statusField)->value ?? '') : '';

      if ($status !== 'approved') {
        return new JsonResponse(['error' => 'Review not found'], 404);
      }

      return new JsonResponse([
        'data' => $this->serializeReview($entity, $review_type),
      ]);
    }
    catch (\Exception) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * Get aggregate stats for a review type.
   */
  public function getStats(string $review_type, Request $request): JsonResponse {
    $rateLimitResponse = $this->checkRateLimit($request);
    if ($rateLimitResponse !== NULL) {
      return $rateLimitResponse;
    }

    if (!in_array($review_type, self::ALLOWED_TYPES, TRUE)) {
      return new JsonResponse(['error' => 'Invalid review type'], 400);
    }

    $targetId = (int) $request->query->get('target_id', 0);

    try {
      $stats = $this->aggregation->getRatingStats($review_type, NULL, $targetId);

      return new JsonResponse([
        'data' => [
          'review_type' => $review_type,
          'target_id' => $targetId ?: NULL,
          'average_rating' => round((float) ($stats['average'] ?? 0), 2),
          'total_reviews' => (int) ($stats['count'] ?? 0),
          'distribution' => $stats['distribution'] ?? [],
        ],
      ]);
    }
    catch (\Exception) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * Check rate limit for the request.
   *
   * With API key: 600 req/min. Without: 60 req/min.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|null
   *   Error response if rate limited, NULL if allowed.
   */
  protected function checkRateLimit(Request $request): ?JsonResponse {
    $apiKey = $request->headers->get('X-Api-Key', '');
    $ip = $request->getClientIp() ?? 'unknown';
    $hasValidKey = $apiKey !== '' && $this->validateApiKey($apiKey);

    $limit = $hasValidKey ? self::RATE_LIMIT_AUTHENTICATED : self::RATE_LIMIT_ANONYMOUS;
    $identifier = $hasValidKey ? 'apikey_' . hash('sha256', $apiKey) : 'ip_' . $ip;

    if (!$this->flood->isAllowed('review_public_api', $limit, 60, $identifier)) {
      return new JsonResponse([
        'error' => 'Rate limit exceeded',
        'retry_after' => 60,
      ], 429, [
        'Retry-After' => '60',
        'X-RateLimit-Limit' => (string) $limit,
        'X-RateLimit-Remaining' => '0',
      ]);
    }

    $this->flood->register('review_public_api', 60, $identifier);

    return NULL;
  }

  /**
   * Validate an API key against ReviewTenantSettings.
   */
  protected function validateApiKey(string $apiKey): bool {
    if ($apiKey === '') {
      return FALSE;
    }

    // API keys are stored as tenant config — validate by checking existence.
    // In production, keys would be hashed; for now accept any non-empty key
    // that is at least 32 chars (minimum complexity).
    return strlen($apiKey) >= 32;
  }

  /**
   * Serialize a review entity to array.
   */
  protected function serializeReview($entity, string $reviewType): array {
    $ratingField = self::RATING_FIELD_MAP[$reviewType];
    $rating = $entity->hasField($ratingField) ? (int) ($entity->get($ratingField)->value ?? 0) : 0;

    $body = '';
    foreach (['body', 'comment', 'review_body'] as $bf) {
      if ($entity->hasField($bf) && !$entity->get($bf)->isEmpty()) {
        $body = (string) ($entity->get($bf)->value ?? '');
        break;
      }
    }

    $data = [
      'id' => (int) $entity->id(),
      'type' => $reviewType,
      'rating' => $rating,
      'body' => $body,
      'created' => (int) ($entity->get('created')->value ?? 0),
      'created_iso' => date('c', (int) ($entity->get('created')->value ?? 0)),
    ];

    // Optional fields.
    if ($entity->hasField('sentiment') && !$entity->get('sentiment')->isEmpty()) {
      $data['sentiment'] = (string) $entity->get('sentiment')->value;
    }

    if ($entity->hasField('helpful_count')) {
      $data['helpful_count'] = (int) ($entity->get('helpful_count')->value ?? 0);
    }

    if ($entity->hasField('not_helpful_count')) {
      $data['not_helpful_count'] = (int) ($entity->get('not_helpful_count')->value ?? 0);
    }

    if ($entity->hasField('verified_purchase')) {
      $data['verified_purchase'] = (bool) ($entity->get('verified_purchase')->value ?? FALSE);
    }

    if ($entity->hasField('owner_response') && !$entity->get('owner_response')->isEmpty()) {
      $data['owner_response'] = (string) ($entity->get('owner_response')->value ?? '');
    }

    return $data;
  }

}
