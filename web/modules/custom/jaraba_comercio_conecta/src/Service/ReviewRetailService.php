<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class ReviewRetailService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una resena, auto-detectando compra verificada.
   *
   * Logica: Crea entidad comercio_review con los datos proporcionados.
   *   Detecta si el usuario ha comprado el producto/servicio verificando
   *   pedidos anteriores para marcar verified_purchase.
   *
   * @param array $data
   *   Datos de la resena: title, body, rating, entity_type_ref, entity_id_ref, etc.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Entidad resena creada, o null si fallo.
   */
  public function createReview(array $data): ?ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $user_id = $data['user_id'] ?? $this->currentUser->id();
      $verified = $this->detectVerifiedPurchase(
        (int) $user_id,
        $data['entity_type_ref'] ?? '',
        (int) ($data['entity_id_ref'] ?? 0)
      );

      $review = $storage->create(array_merge($data, [
        'user_id' => $user_id,
        'verified_purchase' => $verified,
        'status' => $data['status'] ?? 'pending',
        'helpful_count' => 0,
      ]));
      $review->save();

      $this->logger->info('Resena creada por usuario @uid para @type @eid', [
        '@uid' => $user_id,
        '@type' => $data['entity_type_ref'] ?? '',
        '@eid' => $data['entity_id_ref'] ?? 0,
      ]);

      return $review;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando resena: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Obtiene resenas aprobadas de una entidad con paginacion.
   *
   * @param string $entityType
   *   Tipo de entidad referenciada (product_retail, merchant_profile, etc.).
   * @param int $entityId
   *   ID de la entidad referenciada.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Lista de entidades de resenas aprobadas.
   */
  public function getEntityReviews(string $entityType, int $entityId, int $limit = 20, int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('entity_type_ref', $entityType)
        ->condition('entity_id_ref', $entityId)
        ->condition('status', 'approved')
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      return $ids ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo resenas para @type @id: @e', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene resumen de valoraciones de una entidad.
   *
   * @param string $entityType
   *   Tipo de entidad referenciada.
   * @param int $entityId
   *   ID de la entidad referenciada.
   *
   * @return array
   *   Array con average_rating, total_reviews, y count_by_star (1-5).
   */
  public function getReviewSummary(string $entityType, int $entityId): array {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_type_ref', $entityType)
        ->condition('entity_id_ref', $entityId)
        ->condition('status', 'approved')
        ->execute();

      if (!$ids) {
        return [
          'average_rating' => 0,
          'total_reviews' => 0,
          'count_by_star' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];
      }

      $reviews = $storage->loadMultiple($ids);
      $count_by_star = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
      $total_rating = 0;

      foreach ($reviews as $review) {
        $rating = (int) $review->get('rating')->value;
        if ($rating >= 1 && $rating <= 5) {
          $count_by_star[$rating]++;
          $total_rating += $rating;
        }
      }

      $total_reviews = count($reviews);
      $average_rating = $total_reviews > 0 ? round($total_rating / $total_reviews, 2) : 0;

      return [
        'average_rating' => $average_rating,
        'total_reviews' => $total_reviews,
        'count_by_star' => $count_by_star,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo resumen de resenas para @type @id: @e', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'average_rating' => 0,
        'total_reviews' => 0,
        'count_by_star' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
      ];
    }
  }

  /**
   * Modera una resena: aprobar, rechazar o reportar.
   *
   * @param int $reviewId
   *   ID de la resena.
   * @param string $status
   *   Nuevo estado: approved, rejected, flagged.
   *
   * @return bool
   *   TRUE si se actualizo correctamente.
   */
  public function moderateReview(int $reviewId, string $status): bool {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $review = $storage->load($reviewId);
      if (!$review) {
        return FALSE;
      }

      $valid_statuses = ['pending', 'approved', 'rejected', 'flagged'];
      if (!in_array($status, $valid_statuses)) {
        return FALSE;
      }

      $review->set('status', $status);
      $review->save();

      $this->logger->info('Resena @id moderada a estado @status', [
        '@id' => $reviewId,
        '@status' => $status,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error moderando resena @id: @e', [
        '@id' => $reviewId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Anade respuesta del comerciante a una resena.
   *
   * @param int $reviewId
   *   ID de la resena.
   * @param string $response
   *   Texto de la respuesta del comerciante.
   *
   * @return bool
   *   TRUE si se guardo correctamente.
   */
  public function addMerchantResponse(int $reviewId, string $response): bool {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $review = $storage->load($reviewId);
      if (!$review) {
        return FALSE;
      }

      $review->set('merchant_response', $response);
      $review->set('merchant_response_at', \Drupal::time()->getRequestTime());
      $review->save();

      $this->logger->info('Respuesta de comerciante anadida a resena @id', ['@id' => $reviewId]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error anadiendo respuesta a resena @id: @e', [
        '@id' => $reviewId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Marca una resena como util e incrementa el contador.
   *
   * @param int $reviewId
   *   ID de la resena.
   *
   * @return int
   *   Nuevo valor del contador de utilidad.
   */
  public function markHelpful(int $reviewId): int {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $review = $storage->load($reviewId);
      if (!$review) {
        return 0;
      }

      $current = (int) $review->get('helpful_count')->value;
      $new_count = $current + 1;
      $review->set('helpful_count', $new_count);
      $review->save();

      return $new_count;
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando resena @id como util: @e', [
        '@id' => $reviewId,
        '@e' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Obtiene todas las resenas de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Lista de entidades de resenas del usuario.
   */
  public function getUserReviews(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('comercio_review');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->execute();

      return $ids ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo resenas del usuario @uid: @e', [
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Detecta si un usuario tiene compra verificada del producto.
   *
   * Logica: Busca pedidos completados (delivered) del usuario que
   *   contengan el producto referenciado.
   */
  protected function detectVerifiedPurchase(int $userId, string $entityType, int $entityId): bool {
    if ($entityType !== 'product_retail' || $entityId <= 0) {
      return FALSE;
    }

    try {
      $order_item_storage = $this->entityTypeManager->getStorage('order_item_retail');
      $order_storage = $this->entityTypeManager->getStorage('order_retail');

      $order_ids = $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('customer_uid', $userId)
        ->condition('status', 'delivered')
        ->execute();

      if (!$order_ids) {
        return FALSE;
      }

      $item_count = (int) $order_item_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('order_id', array_values($order_ids), 'IN')
        ->condition('product_id', $entityId)
        ->count()
        ->execute();

      return $item_count > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
