<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API controller para resenas de sesiones de mentoring.
 *
 * REV-PHASE4: Endpoints de resenas de sesiones.
 */
class ReviewApiController extends ControllerBase
{

  private const ALLOWED_CREATE_FIELDS = [
    'session_id',
    'reviewee_id',
    'review_type',
    'overall_rating',
    'punctuality_rating',
    'preparation_rating',
    'communication_rating',
    'comment',
    'private_feedback',
    'would_recommend',
  ];

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
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
      $container->get('logger.channel.jaraba_mentoring'),
    );
  }

  /**
   * GET: Lista resenas aprobadas de una sesion.
   */
  public function list(int $session_id): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('session_review');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('session_id', $session_id)
      ->condition('review_status', 'approved')
      ->sort('created', 'DESC')
      ->execute();

    $reviews = $ids ? $storage->loadMultiple($ids) : [];
    $data = [];
    foreach ($reviews as $review) {
      $data[] = [
        'id' => (int) $review->id(),
        'title' => $review->label() ?? '',
        'comment' => $review->get('comment')->value ?? '',
        'overall_rating' => (int) ($review->get('overall_rating')->value ?? 0),
        'review_type' => $review->get('review_type')->value ?? '',
        'author' => $review->getOwner()?->getDisplayName() ?? 'Anonimo',
        'would_recommend' => (bool) ($review->get('would_recommend')->value ?? FALSE),
        'created' => date('c', (int) $review->get('created')->value),
      ];
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => count($data)]]);
  }

  /**
   * POST: Crea una nueva resena de sesion.
   */
  public function createReview(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'JSON invalido.'], Response::HTTP_BAD_REQUEST);
    }

    $data = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

    if (empty($data['session_id']) || empty($data['overall_rating'])) {
      return new JsonResponse(['error' => 'session_id y overall_rating son obligatorios.'], Response::HTTP_BAD_REQUEST);
    }

    $rating = (int) $data['overall_rating'];
    if ($rating < 1 || $rating > 5) {
      return new JsonResponse(['error' => 'Rating debe ser entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('session_review');
      $values = [
        'session_id' => (int) $data['session_id'],
        'overall_rating' => $rating,
        'uid' => $this->currentUser->id(),
        'reviewer_id' => $this->currentUser->id(),
        'review_status' => 'pending',
      ];

      if (!empty($data['reviewee_id'])) {
        $values['reviewee_id'] = (int) $data['reviewee_id'];
      }
      if (!empty($data['review_type'])) {
        $values['review_type'] = $data['review_type'];
      }

      foreach (['punctuality_rating', 'preparation_rating', 'communication_rating'] as $field) {
        if (!empty($data[$field])) {
          $val = (int) $data[$field];
          if ($val >= 1 && $val <= 5) {
            $values[$field] = $val;
          }
        }
      }

      if (!empty($data['comment'])) {
        $values['comment'] = strip_tags(trim($data['comment']));
      }
      if (!empty($data['private_feedback'])) {
        $values['private_feedback'] = strip_tags(trim($data['private_feedback']));
      }
      if (isset($data['would_recommend'])) {
        $values['would_recommend'] = (bool) $data['would_recommend'];
      }

      $review = $storage->create($values);
      $review->save();

      return new JsonResponse([
        'data' => ['id' => (int) $review->id()],
        'message' => 'Resena enviada. Pendiente de moderacion.',
      ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
      $this->logger->error('Error creating session review: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Error al crear la resena.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
