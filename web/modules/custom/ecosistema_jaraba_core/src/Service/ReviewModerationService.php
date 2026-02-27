<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio transversal de moderacion para todas las entidades de resenas.
 *
 * Centraliza operaciones de moderacion (aprobar, rechazar, marcar) que antes
 * estaban duplicadas en cada servicio vertical. Las operaciones de negocio
 * especificas (crear resena, responder, detectar compra verificada) permanecen
 * en los servicios verticales.
 *
 * REV-PHASE3: Servicio 1 de 5 transversales.
 */
class ReviewModerationService {

  /**
   * Constantes de estado — duplicadas del trait para evitar acceso
   * directo a constantes de trait (prohibido en PHP 8.4).
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_APPROVED = 'approved';
  public const STATUS_REJECTED = 'rejected';
  public const STATUS_FLAGGED = 'flagged';

  /**
   * Mapeo del campo de estado por tipo de entidad.
   *
   * Las entidades existentes usan nombres heterogeneos: status, state,
   * review_status. NO renombramos columnas SQL — normalizamos en servicio.
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
   * Estados validos de moderacion.
   */
  private const VALID_STATUSES = [
    self::STATUS_PENDING,
    self::STATUS_APPROVED,
    self::STATUS_REJECTED,
    self::STATUS_FLAGGED,
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
    protected readonly ReviewAggregationService $aggregationService,
  ) {}

  /**
   * Cambia el estado de moderacion de una resena.
   *
   * Si el nuevo estado es 'approved' o 'rejected' (y difiere del anterior),
   * recalcula las estadisticas de rating del target via ReviewAggregationService.
   *
   * @param string $entityTypeId
   *   Tipo de entidad de resena (e.g., 'comercio_review').
   * @param int $entityId
   *   ID de la entidad.
   * @param string $newStatus
   *   Nuevo estado: pending, approved, rejected, flagged.
   *
   * @return bool
   *   TRUE si la operacion fue exitosa.
   */
  public function moderate(string $entityTypeId, int $entityId, string $newStatus): bool {
    if (!in_array($newStatus, self::VALID_STATUSES, TRUE)) {
      $this->logger->error('Invalid moderation status @status for @type @id.', [
        '@status' => $newStatus,
        '@type' => $entityTypeId,
        '@id' => $entityId,
      ]);
      return FALSE;
    }

    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $entity = $storage->load($entityId);
    if (!$entity) {
      $this->logger->error('Entity @type @id not found for moderation.', [
        '@type' => $entityTypeId,
        '@id' => $entityId,
      ]);
      return FALSE;
    }

    $statusField = $this->getStatusFieldName($entityTypeId);
    $oldStatus = $entity->get($statusField)->value;

    if ($oldStatus === $newStatus) {
      return TRUE;
    }

    $entity->set($statusField, $newStatus);
    $entity->save();

    // Recalcular agregacion si cambia a/desde approved.
    if (in_array($newStatus, ['approved', 'rejected'], TRUE) || $oldStatus === 'approved') {
      try {
        $this->aggregationService->recalculateForReviewEntity($entity);
      }
      catch (\Exception $e) {
        $this->logger->error('Aggregation recalculation failed after moderation: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $this->logger->info('Review @type @id moderated: @old -> @new by uid @uid.', [
      '@type' => $entityTypeId,
      '@id' => $entityId,
      '@old' => $oldStatus ?? 'null',
      '@new' => $newStatus,
      '@uid' => $this->currentUser->id(),
    ]);

    return TRUE;
  }

  /**
   * Obtiene resenas pendientes de moderacion para un tipo de entidad.
   *
   * @param string $entityTypeId
   *   Tipo de entidad de resena.
   * @param int|null $tenantGroupId
   *   Filtrar por tenant (group ID). NULL para todos.
   * @param int $limit
   *   Maximo de resultados.
   *
   * @return array
   *   Array de entidades ordenadas FIFO (mas antiguas primero).
   */
  public function getPendingReviews(string $entityTypeId, ?int $tenantGroupId = NULL, int $limit = 50): array {
    $statusField = $this->getStatusFieldName($entityTypeId);
    $storage = $this->entityTypeManager->getStorage($entityTypeId);

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition($statusField, self::STATUS_PENDING)
      ->sort('created', 'ASC')
      ->range(0, $limit);

    if ($tenantGroupId !== NULL) {
      $definition = $this->entityTypeManager->getDefinition($entityTypeId);
      $baseFields = $definition->get('entity_keys');
      // Solo filtrar si la entidad tiene campo tenant_id.
      try {
        $query->condition('tenant_id', $tenantGroupId);
      }
      catch (\Exception) {
        // La entidad no tiene tenant_id — devolver sin filtro de tenant.
      }
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Devuelve conteos de resenas pendientes por tipo de entidad.
   *
   * @param int|null $tenantGroupId
   *   Filtrar por tenant. NULL para todos.
   *
   * @return array
   *   Associative array ['entity_type_id' => count].
   */
  public function getPendingCounts(?int $tenantGroupId = NULL): array {
    $counts = [];

    foreach (self::STATUS_FIELD_MAP as $entityTypeId => $statusField) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityTypeId);
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition($statusField, self::STATUS_PENDING)
          ->count();

        if ($tenantGroupId !== NULL) {
          try {
            $query->condition('tenant_id', $tenantGroupId);
          }
          catch (\Exception) {
            // Sin campo tenant_id.
          }
        }

        $count = (int) $query->execute();
        if ($count > 0) {
          $counts[$entityTypeId] = $count;
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Could not count pending @type: @msg', [
          '@type' => $entityTypeId,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    return $counts;
  }

  /**
   * Marca una resena como flagged con un motivo.
   *
   * @param string $entityTypeId
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   * @param string $reason
   *   Motivo del flag.
   *
   * @return bool
   *   TRUE si fue exitoso.
   */
  public function flagReview(string $entityTypeId, int $entityId, string $reason): bool {
    $result = $this->moderate($entityTypeId, $entityId, self::STATUS_FLAGGED);

    if ($result) {
      $this->logger->info('Review @type @id flagged. Reason: @reason', [
        '@type' => $entityTypeId,
        '@id' => $entityId,
        '@reason' => $reason,
      ]);
    }

    return $result;
  }

  /**
   * Devuelve el nombre del campo de estado para un tipo de entidad.
   *
   * @param string $entityTypeId
   *   Tipo de entidad de resena.
   *
   * @return string
   *   Nombre del campo (status, state, review_status).
   */
  public function getStatusFieldName(string $entityTypeId): string {
    return self::STATUS_FIELD_MAP[$entityTypeId] ?? 'review_status';
  }

  /**
   * Devuelve los tipos de entidad de resena soportados.
   *
   * @return string[]
   *   Array de entity type IDs.
   */
  public function getSupportedEntityTypes(): array {
    return array_keys(self::STATUS_FIELD_MAP);
  }

}
