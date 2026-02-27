<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API controller para resenas de cursos del LMS.
 *
 * REV-PHASE4: Endpoints de resenas de cursos.
 */
class CourseReviewApiController extends ControllerBase
{

  private const ALLOWED_CREATE_FIELDS = [
    'course_id',
    'rating',
    'difficulty_rating',
    'content_quality_rating',
    'instructor_rating',
    'title',
    'body',
  ];

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
    protected readonly ?ReviewAggregationService $aggregationService,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('logger.channel.jaraba_lms'),
      $container->has('ecosistema_jaraba_core.review_aggregation')
      ? $container->get('ecosistema_jaraba_core.review_aggregation')
      : NULL,
    );
  }

  /**
   * GET: Lista resenas aprobadas de un curso.
   */
  public function list(int $course_id): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('course_review');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('course_id', $course_id)
      ->condition('review_status', 'approved')
      ->sort('created', 'DESC')
      ->range(0, 20)
      ->execute();

    $reviews = $ids ? $storage->loadMultiple($ids) : [];
    $data = [];
    foreach ($reviews as $review) {
      $data[] = [
        'id' => (int) $review->id(),
        'title' => $review->label(),
        'body' => $review->get('body')->value ?? '',
        'rating' => (int) $review->get('rating')->value,
        'author' => $review->getOwner()?->getDisplayName() ?? 'Anonimo',
        'verified_enrollment' => (bool) $review->get('verified_enrollment')->value,
        'progress_at_review' => (int) ($review->get('progress_at_review')->value ?? 0),
        'created' => date('c', (int) $review->get('created')->value),
      ];
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => count($data)]]);
  }

  /**
   * GET: Estadisticas de rating de un curso.
   */
  public function stats(int $course_id): JsonResponse
  {
    if ($this->aggregationService === NULL) {
      return new JsonResponse(['error' => 'Servicio no disponible.'], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    $stats = $this->aggregationService->getRatingStats('course_review', 'lms_course', $course_id);
    return new JsonResponse(['data' => $stats]);
  }

  /**
   * POST: Crea una nueva resena de curso.
   */
  public function createReview(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'JSON invalido.'], Response::HTTP_BAD_REQUEST);
    }

    $data = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

    if (empty($data['course_id']) || empty($data['rating'])) {
      return new JsonResponse(['error' => 'course_id y rating son obligatorios.'], Response::HTTP_BAD_REQUEST);
    }

    $rating = (int) $data['rating'];
    if ($rating < 1 || $rating > 5) {
      return new JsonResponse(['error' => 'Rating debe ser entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('course_review');
      $values = [
        'course_id' => (int) $data['course_id'],
        'rating' => $rating,
        'uid' => $this->currentUser->id(),
        'review_status' => 'pending',
      ];

      foreach (['difficulty_rating', 'content_quality_rating', 'instructor_rating'] as $field) {
        if (!empty($data[$field])) {
          $val = (int) $data[$field];
          if ($val >= 1 && $val <= 5) {
            $values[$field] = $val;
          }
        }
      }

      if (!empty($data['title'])) {
        $values['title'] = strip_tags(trim($data['title']));
      }
      if (!empty($data['body'])) {
        $values['body'] = strip_tags(trim($data['body']));
      }

      $review = $storage->create($values);
      $review->save();

      return new JsonResponse([
        'data' => ['id' => (int) $review->id()],
        'message' => 'Resena enviada. Pendiente de moderacion.',
      ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
      $this->logger->error('Error creating course review: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Error al crear la resena.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
