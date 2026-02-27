<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Controller;

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
 * API controller para resenas de servicios.
 *
 * REV-PHASE4: Endpoints de resenas de ServiciosConecta.
 */
class ReviewApiController extends ControllerBase
{

  private const ALLOWED_CREATE_FIELDS = [
    'provider_id',
    'booking_id',
    'rating',
    'title',
    'comment',
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
      $container->get('logger.channel.jaraba_servicios_conecta'),
      $container->has('ecosistema_jaraba_core.review_aggregation')
      ? $container->get('ecosistema_jaraba_core.review_aggregation')
      : NULL,
    );
  }

  /**
   * GET: Lista resenas aprobadas de un proveedor.
   */
  public function list(int $provider_id): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('review_servicios');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('status', 'approved')
      ->sort('created', 'DESC')
      ->range(0, 20)
      ->execute();

    $reviews = $ids ? $storage->loadMultiple($ids) : [];
    $data = [];
    foreach ($reviews as $review) {
      $data[] = [
        'id' => (int) $review->id(),
        'title' => $review->get('title')->value ?? '',
        'comment' => $review->get('comment')->value ?? '',
        'rating' => (int) $review->get('rating')->value,
        'author' => $review->getOwner()?->getDisplayName() ?? 'Anonimo',
        'created' => date('c', (int) $review->get('created')->value),
      ];
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => count($data)]]);
  }

  /**
   * GET: Estadisticas de rating de un proveedor.
   */
  public function stats(int $provider_id): JsonResponse
  {
    if ($this->aggregationService === NULL) {
      return new JsonResponse(['error' => 'Servicio no disponible.'], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    $stats = $this->aggregationService->getRatingStats('review_servicios', 'provider_profile', $provider_id);
    return new JsonResponse(['data' => $stats]);
  }

  /**
   * POST: Crea una nueva resena de servicio.
   */
  public function createReview(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'JSON invalido.'], Response::HTTP_BAD_REQUEST);
    }

    $data = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

    if (empty($data['provider_id']) || empty($data['rating'])) {
      return new JsonResponse(['error' => 'provider_id y rating son obligatorios.'], Response::HTTP_BAD_REQUEST);
    }

    $rating = (int) $data['rating'];
    if ($rating < 1 || $rating > 5) {
      return new JsonResponse(['error' => 'Rating debe ser entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('review_servicios');
      $values = [
        'provider_id' => (int) $data['provider_id'],
        'rating' => $rating,
        'reviewer_uid' => $this->currentUser->id(),
        'status' => 'pending',
      ];

      if (!empty($data['booking_id'])) {
        $values['booking_id'] = (int) $data['booking_id'];
      }
      if (!empty($data['title'])) {
        $values['title'] = strip_tags(trim($data['title']));
      }
      if (!empty($data['comment'])) {
        $values['comment'] = strip_tags(trim($data['comment']));
      }

      $review = $storage->create($values);
      $review->save();

      return new JsonResponse([
        'data' => ['id' => (int) $review->id()],
        'message' => 'Resena enviada. Pendiente de moderacion.',
      ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
      $this->logger->error('Error creating servicios review: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Error al crear la resena.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
