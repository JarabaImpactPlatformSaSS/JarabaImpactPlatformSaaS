<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * B-08: SEO-optimized review listing pages.
 *
 * Generates standalone review pages with structured data (JSON-LD),
 * meta tags, and pagination for search engine indexing.
 */
class ReviewSeoController extends ControllerBase {

  /**
   * Mapeo vertical → review entity type.
   */
  private const VERTICAL_REVIEW_MAP = [
    'comercioconecta' => 'comercio_review',
    'agroconecta' => 'review_agro',
    'serviciosconecta' => 'review_servicios',
    'formacion' => 'course_review',
    'mentoring' => 'session_review',
  ];

  /**
   * Mapeo vertical → target entity type.
   */
  private const VERTICAL_TARGET_MAP = [
    'comercioconecta' => 'merchant_profile',
    'agroconecta' => 'producer_profile',
    'serviciosconecta' => 'provider_profile',
    'formacion' => 'lms_course',
    'mentoring' => 'mentoring_session',
  ];

  /**
   * Mapeo review entity type → rating field name.
   */
  private const RATING_FIELD_MAP = [
    'comercio_review' => 'rating',
    'review_agro' => 'rating',
    'review_servicios' => 'rating',
    'session_review' => 'rating',
    'course_review' => 'rating',
  ];

  /**
   * Mapeo review entity type → status field name.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'status',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
  ];

  /**
   * Mapeo review entity type → target ID field name.
   */
  private const TARGET_ID_FIELD_MAP = [
    'comercio_review' => 'entity_id_ref',
    'review_agro' => 'target_entity_id',
    'review_servicios' => 'provider_id',
    'session_review' => 'session_id',
    'course_review' => 'course_id',
  ];

  public function __construct(
    protected readonly ReviewAggregationService $aggregation,
    EntityTypeManagerInterface $entityTypeManager,
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
    );
  }

  /**
   * SEO reviews page for a vertical.
   *
   * @param string $vertical
   *   Vertical canonical name (e.g., 'comercioconecta').
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Render array.
   */
  public function verticalReviews(string $vertical, Request $request): array {
    $reviewType = self::VERTICAL_REVIEW_MAP[$vertical] ?? NULL;
    $targetType = self::VERTICAL_TARGET_MAP[$vertical] ?? NULL;

    if ($reviewType === NULL || $targetType === NULL) {
      throw new NotFoundHttpException();
    }

    $page = max(1, (int) $request->query->get('page', 1));
    $perPage = 20;
    $statusField = self::STATUS_FIELD_MAP[$reviewType];
    $ratingField = self::RATING_FIELD_MAP[$reviewType];

    try {
      $storage = $this->entityTypeManager()->getStorage($reviewType);

      // Count total.
      $total = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($statusField, 'approved')
        ->count()
        ->execute();

      // Load paginated.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($statusField, 'approved')
        ->sort('created', 'DESC')
        ->range(($page - 1) * $perPage, $perPage)
        ->execute();

      $reviews = $storage->loadMultiple($ids);
    }
    catch (\Exception) {
      $total = 0;
      $reviews = [];
    }

    // Build review items.
    $items = [];
    foreach ($reviews as $review) {
      $rating = 0;
      if ($review->hasField($ratingField)) {
        $rating = (int) ($review->get($ratingField)->value ?? 0);
      }

      $body = '';
      foreach (['body', 'comment', 'review_body'] as $bodyField) {
        if ($review->hasField($bodyField) && !$review->get($bodyField)->isEmpty()) {
          $body = (string) ($review->get($bodyField)->value ?? '');
          break;
        }
      }

      $authorName = '';
      if ($review->hasField('uid') && !$review->get('uid')->isEmpty()) {
        $uid = (int) ($review->get('uid')->target_id ?? 0);
        if ($uid > 0) {
          try {
            $user = $this->entityTypeManager()->getStorage('user')->load($uid);
            $authorName = $user ? $user->getDisplayName() : '';
          }
          catch (\Exception) {}
        }
      }

      $sentiment = '';
      if ($review->hasField('sentiment') && !$review->get('sentiment')->isEmpty()) {
        $sentiment = (string) $review->get('sentiment')->value;
      }

      $items[] = [
        'id' => $review->id(),
        'rating' => $rating,
        'stars' => str_repeat('★', $rating) . str_repeat('☆', max(0, 5 - $rating)),
        'body' => $body,
        'author_name' => $authorName,
        'created' => (int) ($review->get('created')->value ?? 0),
        'sentiment' => $sentiment,
        'verified' => $review->hasField('verified_purchase') && (bool) ($review->get('verified_purchase')->value ?? FALSE),
      ];
    }

    // Aggregate stats.
    $stats = ['average' => 0, 'count' => 0, 'distribution' => []];
    try {
      $stats = $this->aggregation->getRatingStats($reviewType, NULL, 0);
    }
    catch (\Exception) {}

    // JSON-LD AggregateRating.
    $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => $this->getVerticalLabel($vertical),
      'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format((float) ($stats['average'] ?? 0), 1),
        'reviewCount' => (int) ($stats['count'] ?? 0),
        'bestRating' => 5,
        'worstRating' => 1,
      ],
    ];

    $totalPages = (int) ceil((int) $total / $perPage);

    $build = [
      '#theme' => 'review_seo_page',
      '#vertical' => $vertical,
      '#vertical_label' => $this->getVerticalLabel($vertical),
      '#reviews' => $items,
      '#stats' => $stats,
      '#page' => $page,
      '#total_pages' => $totalPages,
      '#total_reviews' => (int) $total,
    ];

    // Attach JSON-LD to head.
    $build['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['type' => 'application/ld+json'],
        '#value' => json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      ],
      'review_seo_jsonld',
    ];

    // Meta tags.
    $metaTitle = $this->t('Reviews @vertical — Page @page', [
      '@vertical' => $this->getVerticalLabel($vertical),
      '@page' => $page,
    ]);
    $metaDesc = $this->t('@count reviews with @avg average rating for @vertical', [
      '@count' => (int) $total,
      '@avg' => number_format((float) ($stats['average'] ?? 0), 1),
      '@vertical' => $this->getVerticalLabel($vertical),
    ]);

    $build['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'description',
          'content' => $metaDesc,
        ],
      ],
      'review_seo_meta_description',
    ];

    $build['#cache'] = [
      'tags' => [$reviewType . '_list'],
      'max-age' => 3600,
    ];

    return $build;
  }

  /**
   * Page title callback.
   */
  public function verticalReviewsTitle(string $vertical): string {
    $label = $this->getVerticalLabel($vertical);
    return $this->t('Reviews — @vertical', ['@vertical' => $label])->__toString();
  }

  /**
   * Get human-readable label for a vertical.
   */
  protected function getVerticalLabel(string $vertical): string {
    $labels = [
      'comercioconecta' => 'Comercio Conecta',
      'agroconecta' => 'Agro Conecta',
      'serviciosconecta' => 'Servicios Conecta',
      'formacion' => 'Formación',
      'mentoring' => 'Mentoring',
    ];
    return $labels[$vertical] ?? ucfirst($vertical);
  }

}
