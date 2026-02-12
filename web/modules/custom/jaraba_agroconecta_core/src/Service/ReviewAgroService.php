<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_agroconecta_core\Entity\ReviewAgro;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de reseñas AgroConecta.
 *
 * PROPÓSITO:
 * Centraliza la creación, moderación, cálculo de ratings y consultas
 * de reseñas del marketplace. Incluye verificación de compra y
 * prevención de reseñas duplicadas.
 *
 * FLUJO:
 * 1. createReview() → crea reseña con estado 'pending'
 * 2. moderate() → aprueba, rechaza o marca para revisión
 * 3. addProducerResponse() → el productor responde a la reseña
 * 4. getRatingStats() → calcula promedios y distribución
 */
class ReviewAgroService
{

    /**
     * El logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountInterface $currentUser,
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_agroconecta');
    }

    /**
     * Crea una nueva reseña.
     *
     * Valida que no exista duplicado (mismo usuario + mismo target) y verifica
     * si el usuario realizó la compra del producto reseñado.
     *
     * @param array $data
     *   Datos de la reseña:
     *   - type: (string) product|producer|order
     *   - target_entity_type: (string) Tipo de entidad objetivo
     *   - target_entity_id: (int) ID de la entidad objetivo
     *   - rating: (int) 1-5
     *   - title: (string, opcional) Título
     *   - body: (string) Cuerpo de la reseña
     *   - tenant_id: (int) ID del tenant
     * @param int|null $userId
     *   ID del usuario autor. Si es NULL, usa el usuario actual.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro|null
     *   Reseña creada, o NULL si hay duplicado o error.
     */
    public function createReview(array $data, ?int $userId = NULL): ?ReviewAgro
    {
        $userId = $userId ?? (int) $this->currentUser->id();

        // Prevenir duplicados: mismo usuario + tipo + target.
        if ($this->hasExistingReview($userId, $data['target_entity_type'], (int) $data['target_entity_id'])) {
            $this->logger->warning('Reseña duplicada rechazada: usuario @uid ya reseñó @type:@id.', [
                '@uid' => $userId,
                '@type' => $data['target_entity_type'],
                '@id' => $data['target_entity_id'],
            ]);
            return NULL;
        }

        // Verificar compra si el tipo es 'product'.
        $verifiedPurchase = FALSE;
        if (($data['type'] ?? 'product') === 'product') {
            $verifiedPurchase = $this->verifyPurchase($userId, (int) $data['target_entity_id']);
        }

        try {
            $storage = $this->entityTypeManager->getStorage('review_agro');
            /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $review */
            $review = $storage->create([
                'uid' => $userId,
                'type' => $data['type'] ?? ReviewAgro::TYPE_PRODUCT,
                'target_entity_type' => $data['target_entity_type'],
                'target_entity_id' => $data['target_entity_id'],
                'rating' => max(1, min(5, (int) ($data['rating'] ?? 5))),
                'title' => $data['title'] ?? '',
                'body' => $data['body'],
                'verified_purchase' => $verifiedPurchase,
                'state' => ReviewAgro::STATE_PENDING,
                'tenant_id' => $data['tenant_id'] ?? NULL,
            ]);
            $review->save();

            $this->logger->info('Reseña #@id creada por usuario @uid para @type:@target.', [
                '@id' => $review->id(),
                '@uid' => $userId,
                '@type' => $data['target_entity_type'],
                '@target' => $data['target_entity_id'],
            ]);

            return $review;
        } catch (\Exception $e) {
            $this->logger->error('Error al crear reseña: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Modera una reseña (aprobar, rechazar, marcar).
     *
     * @param int $reviewId
     *   ID de la reseña.
     * @param string $newState
     *   Nuevo estado: approved, rejected, flagged.
     *
     * @return bool
     *   TRUE si la moderación fue exitosa.
     */
    public function moderate(int $reviewId, string $newState): bool
    {
        $validStates = [
            ReviewAgro::STATE_APPROVED,
            ReviewAgro::STATE_REJECTED,
            ReviewAgro::STATE_FLAGGED,
        ];

        if (!in_array($newState, $validStates)) {
            $this->logger->warning('Estado de moderación no válido: @state.', [
                '@state' => $newState,
            ]);
            return FALSE;
        }

        $review = $this->loadReview($reviewId);
        if (!$review) {
            return FALSE;
        }

        $oldState = $review->get('state')->value;
        $review->set('state', $newState);
        $review->save();

        $this->logger->info('Reseña #@id moderada: @old → @new por @uid.', [
            '@id' => $reviewId,
            '@old' => $oldState,
            '@new' => $newState,
            '@uid' => $this->currentUser->id(),
        ]);

        return TRUE;
    }

    /**
     * Añade la respuesta de un productor a una reseña.
     *
     * @param int $reviewId
     *   ID de la reseña.
     * @param string $responseText
     *   Texto de la respuesta.
     * @param int|null $producerUserId
     *   ID del usuario productor. Si NULL, usa el usuario actual.
     *
     * @return bool
     *   TRUE si la respuesta se guardó correctamente.
     */
    public function addProducerResponse(int $reviewId, string $responseText, ?int $producerUserId = NULL): bool
    {
        $review = $this->loadReview($reviewId);
        if (!$review) {
            return FALSE;
        }

        if ($review->hasResponse()) {
            $this->logger->warning('Reseña #@id ya tiene respuesta.', ['@id' => $reviewId]);
            return FALSE;
        }

        $producerUserId = $producerUserId ?? (int) $this->currentUser->id();
        $review->set('response', $responseText);
        $review->set('response_by', $producerUserId);
        $review->save();

        $this->logger->info('Respuesta del productor @uid añadida a reseña #@id.', [
            '@uid' => $producerUserId,
            '@id' => $reviewId,
        ]);

        return TRUE;
    }

    /**
     * Obtiene estadísticas de rating para una entidad.
     *
     * @param string $targetEntityType
     *   Tipo de la entidad objetivo.
     * @param int $targetEntityId
     *   ID de la entidad.
     *
     * @return array
     *   Array con:
     *   - average: (float) Promedio de rating
     *   - count: (int) Número total de reseñas aprobadas
     *   - distribution: (array) Distribución por estrella [1=>N, 2=>N, ...]
     */
    public function getRatingStats(string $targetEntityType, int $targetEntityId): array
    {
        $storage = $this->entityTypeManager->getStorage('review_agro');
        $reviews = $storage->loadByProperties([
            'target_entity_type' => $targetEntityType,
            'target_entity_id' => $targetEntityId,
            'state' => ReviewAgro::STATE_APPROVED,
        ]);

        $stats = [
            'average' => 0.0,
            'count' => 0,
            'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];

        if (empty($reviews)) {
            return $stats;
        }

        $total = 0;
        foreach ($reviews as $review) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $review */
            $rating = (int) $review->get('rating')->value;
            $total += $rating;
            if (isset($stats['distribution'][$rating])) {
                $stats['distribution'][$rating]++;
            }
        }

        $stats['count'] = count($reviews);
        $stats['average'] = round($total / $stats['count'], 1);

        return $stats;
    }

    /**
     * Obtiene reseñas aprobadas para una entidad con paginación.
     *
     * @param string $targetEntityType
     *   Tipo de entidad objetivo.
     * @param int $targetEntityId
     *   ID de la entidad objetivo.
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Offset de paginación.
     *
     * @return array
     *   Array con 'data' (reseñas serializadas) y 'meta' (paginación).
     */
    public function getReviewsForTarget(string $targetEntityType, int $targetEntityId, int $limit = 10, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('review_agro');

        // Contar total.
        $countQuery = $storage->getQuery()
            ->condition('target_entity_type', $targetEntityType)
            ->condition('target_entity_id', $targetEntityId)
            ->condition('state', ReviewAgro::STATE_APPROVED)
            ->accessCheck(FALSE)
            ->count();
        $total = (int) $countQuery->execute();

        // Consultar con paginación.
        $query = $storage->getQuery()
            ->condition('target_entity_type', $targetEntityType)
            ->condition('target_entity_id', $targetEntityId)
            ->condition('state', ReviewAgro::STATE_APPROVED)
            ->accessCheck(FALSE)
            ->sort('created', 'DESC')
            ->range($offset, $limit);
        $ids = $query->execute();

        $reviews = [];
        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $entity */
                $reviews[] = $this->serializeReview($entity);
            }
        }

        return [
            'data' => $reviews,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * Obtiene las reseñas pendientes de moderación.
     *
     * @param int $limit
     *   Límite de resultados.
     *
     * @return array
     *   Array de reseñas serializadas pendientes de moderación.
     */
    public function getPendingReviews(int $limit = 50): array
    {
        $storage = $this->entityTypeManager->getStorage('review_agro');

        $query = $storage->getQuery()
            ->condition('state', ReviewAgro::STATE_PENDING)
            ->accessCheck(FALSE)
            ->sort('created', 'ASC')
            ->range(0, $limit);
        $ids = $query->execute();

        $reviews = [];
        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $entity */
                $reviews[] = $this->serializeReview($entity);
            }
        }

        return $reviews;
    }

    /**
     * Serializa una reseña para respuesta API.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $review
     *   La reseña a serializar.
     *
     * @return array
     *   Datos de la reseña en formato array.
     */
    public function serializeReview(ReviewAgro $review): array
    {
        $data = [
            'id' => (int) $review->id(),
            'type' => $review->get('type')->value,
            'type_label' => $review->getTypeLabel(),
            'target_entity_type' => $review->get('target_entity_type')->value,
            'target_entity_id' => (int) $review->get('target_entity_id')->value,
            'rating' => (int) $review->get('rating')->value,
            'rating_stars' => $review->getRatingStars(),
            'title' => $review->get('title')->value ?? '',
            'body' => $review->get('body')->value,
            'verified_purchase' => (bool) $review->get('verified_purchase')->value,
            'state' => $review->get('state')->value,
            'state_label' => $review->getStateLabel(),
            'author' => [
                'id' => (int) $review->getOwnerId(),
                'name' => $review->getOwner() ? $review->getOwner()->getDisplayName() : '',
            ],
            'created' => date('c', (int) $review->get('created')->value),
        ];

        // Añadir respuesta del productor si existe.
        if ($review->hasResponse()) {
            $data['response'] = [
                'body' => $review->get('response')->value,
                'by' => (int) ($review->get('response_by')->target_id ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Verifica si el usuario ya ha reseñado esta entidad.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $targetEntityType
     *   Tipo de entidad objetivo.
     * @param int $targetEntityId
     *   ID de la entidad objetivo.
     *
     * @return bool
     *   TRUE si ya existe una reseña.
     */
    protected function hasExistingReview(int $userId, string $targetEntityType, int $targetEntityId): bool
    {
        $storage = $this->entityTypeManager->getStorage('review_agro');
        $count = $storage->getQuery()
            ->condition('uid', $userId)
            ->condition('target_entity_type', $targetEntityType)
            ->condition('target_entity_id', $targetEntityId)
            ->accessCheck(FALSE)
            ->count()
            ->execute();
        return $count > 0;
    }

    /**
     * Verifica si el usuario compró el producto.
     *
     * Busca pedidos completados del usuario que contengan el producto.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $productId
     *   ID del producto.
     *
     * @return bool
     *   TRUE si el usuario ha comprado el producto.
     */
    protected function verifyPurchase(int $userId, int $productId): bool
    {
        try {
            // Buscar OrderItemAgro del usuario con este producto.
            $itemStorage = $this->entityTypeManager->getStorage('order_item_agro');
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');

            // Buscar pedidos completados del usuario.
            $orderIds = $orderStorage->getQuery()
                ->condition('uid', $userId)
                ->condition('state', 'completed')
                ->accessCheck(FALSE)
                ->execute();

            if (empty($orderIds)) {
                return FALSE;
            }

            // Buscar items con el producto en esos pedidos.
            $itemCount = $itemStorage->getQuery()
                ->condition('order_id', $orderIds, 'IN')
                ->condition('product_id', $productId)
                ->accessCheck(FALSE)
                ->count()
                ->execute();

            return $itemCount > 0;
        } catch (\Exception $e) {
            // Si la entidad order_item_agro no existe aún, no verificar.
            return FALSE;
        }
    }

    /**
     * Carga una reseña por ID.
     *
     * @param int $reviewId
     *   ID de la reseña.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro|null
     *   La reseña o NULL si no existe.
     */
    protected function loadReview(int $reviewId): ?ReviewAgro
    {
        $storage = $this->entityTypeManager->getStorage('review_agro');
        $review = $storage->load($reviewId);
        if (!$review instanceof ReviewAgro) {
            $this->logger->warning('Reseña #@id no encontrada.', ['@id' => $reviewId]);
            return NULL;
        }
        return $review;
    }

}
