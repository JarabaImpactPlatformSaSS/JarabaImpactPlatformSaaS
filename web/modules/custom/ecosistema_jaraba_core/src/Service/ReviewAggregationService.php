<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio transversal de agregacion de ratings para todas las resenas.
 *
 * Calcula estadisticas (promedio, conteo, distribucion) y opcionalmente
 * desnormaliza en la entidad target (e.g., provider_profile.average_rating).
 * Usa SQL directo para rendimiento y cache con tags para invalidacion.
 *
 * REV-PHASE3: Servicio 2 de 5 transversales.
 */
class ReviewAggregationService {

  /**
   * Mapeo de cada entidad de resena a su target.
   *
   * - type_field: campo que almacena el tipo de entidad target (polimorfismo).
   * - id_field: campo que almacena el ID del target.
   * - fixed_type: si type_field es NULL, el tipo de target es fijo.
   */
  private const TARGET_FIELD_MAP = [
    'comercio_review' => [
      'type_field' => 'entity_type_ref',
      'id_field' => 'entity_id_ref',
    ],
    'review_agro' => [
      'type_field' => 'target_entity_type',
      'id_field' => 'target_entity_id',
    ],
    'review_servicios' => [
      'type_field' => NULL,
      'id_field' => 'provider_id',
      'fixed_type' => 'provider_profile',
    ],
    'session_review' => [
      'type_field' => NULL,
      'id_field' => 'session_id',
      'fixed_type' => 'mentoring_session',
    ],
    'course_review' => [
      'type_field' => NULL,
      'id_field' => 'course_id',
      'fixed_type' => 'lms_course',
    ],
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
   * Campo de estado de moderacion por tipo de entidad.
   */
  private const STATUS_FIELD_MAP = [
    'comercio_review' => 'status',
    'review_agro' => 'state',
    'review_servicios' => 'status',
    'session_review' => 'review_status',
    'course_review' => 'review_status',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly CacheBackendInterface $cache,
  ) {}

  /**
   * Calcula estadisticas de rating para un target especifico.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena (e.g., 'comercio_review').
   * @param string $targetEntityType
   *   Tipo de entidad target (e.g., 'merchant_profile').
   * @param int $targetEntityId
   *   ID del target.
   *
   * @return array
   *   ['average' => float, 'count' => int, 'distribution' => [1 => N, ..., 5 => N]]
   */
  public function getRatingStats(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId): array {
    $cacheKey = "review_stats:{$reviewEntityTypeId}:{$targetEntityType}:{$targetEntityId}";
    $cached = $this->cache->get($cacheKey);
    if ($cached) {
      return $cached->data;
    }

    $stats = $this->calculateStats($reviewEntityTypeId, $targetEntityType, $targetEntityId);

    $this->cache->set($cacheKey, $stats, CacheBackendInterface::CACHE_PERMANENT, [
      "review_stats:{$targetEntityType}:{$targetEntityId}",
    ]);

    return $stats;
  }

  /**
   * Recalcula estadisticas a partir de una entidad de resena.
   *
   * Resuelve el target de la resena, recalcula las estadisticas
   * y actualiza campos desnormalizados si existen en el target.
   *
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   La entidad de resena.
   */
  public function recalculateForReviewEntity(EntityInterface $reviewEntity): void {
    $entityTypeId = $reviewEntity->getEntityTypeId();
    $targetInfo = $this->resolveTarget($reviewEntity);
    if ($targetInfo === NULL) {
      return;
    }

    [$targetEntityType, $targetEntityId] = $targetInfo;

    // Invalidar cache.
    $this->invalidateStatsCache($targetEntityType, $targetEntityId);

    // Recalcular.
    $stats = $this->calculateStats($entityTypeId, $targetEntityType, $targetEntityId);

    // Actualizar cache.
    $cacheKey = "review_stats:{$entityTypeId}:{$targetEntityType}:{$targetEntityId}";
    $this->cache->set($cacheKey, $stats, CacheBackendInterface::CACHE_PERMANENT, [
      "review_stats:{$targetEntityType}:{$targetEntityId}",
    ]);

    // Desnormalizar en target si tiene los campos.
    $this->denormalizeToTarget($targetEntityType, $targetEntityId, $stats);
  }

  /**
   * Invalida la cache de estadisticas para un target.
   *
   * @param string $targetEntityType
   *   Tipo de entidad target.
   * @param int $targetEntityId
   *   ID del target.
   */
  public function invalidateStatsCache(string $targetEntityType, int $targetEntityId): void {
    $this->cache->invalidateTags([
      "review_stats:{$targetEntityType}:{$targetEntityId}",
    ]);
  }

  /**
   * Calcula estadisticas directamente via SQL.
   */
  protected function calculateStats(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId): array {
    $default = [
      'average' => 0.0,
      'count' => 0,
      'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    ];

    $ratingField = self::RATING_FIELD_MAP[$reviewEntityTypeId] ?? NULL;
    $statusField = self::STATUS_FIELD_MAP[$reviewEntityTypeId] ?? 'review_status';
    $targetMapping = self::TARGET_FIELD_MAP[$reviewEntityTypeId] ?? NULL;

    if ($ratingField === NULL || $targetMapping === NULL) {
      return $default;
    }

    $table = $reviewEntityTypeId;
    $idField = $targetMapping['id_field'];

    try {
      $query = $this->database->select($table, 'r')
        ->condition('r.' . $statusField, 'approved')
        ->condition('r.' . $idField, $targetEntityId);

      // Si es polimorfismo, filtrar por type_field tambien.
      if ($targetMapping['type_field'] !== NULL) {
        $query->condition('r.' . $targetMapping['type_field'], $targetEntityType);
      }

      $query->addExpression("r.{$ratingField}", 'rating_value');
      $results = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculating stats for @type targeting @target @id: @msg', [
        '@type' => $reviewEntityTypeId,
        '@target' => $targetEntityType,
        '@id' => $targetEntityId,
        '@msg' => $e->getMessage(),
      ]);
      return $default;
    }

    if (empty($results)) {
      return $default;
    }

    $total = 0;
    $count = 0;
    $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    foreach ($results as $row) {
      $rating = (int) $row->rating_value;
      if ($rating >= 1 && $rating <= 5) {
        $total += $rating;
        $count++;
        $distribution[$rating]++;
      }
    }

    return [
      'average' => $count > 0 ? round($total / $count, 2) : 0.0,
      'count' => $count,
      'distribution' => $distribution,
    ];
  }

  /**
   * Resuelve la entidad target de una resena.
   *
   * @return array|null
   *   [targetEntityType, targetEntityId] o NULL si no se puede resolver.
   */
  protected function resolveTarget(EntityInterface $reviewEntity): ?array {
    $entityTypeId = $reviewEntity->getEntityTypeId();
    $mapping = self::TARGET_FIELD_MAP[$entityTypeId] ?? NULL;
    if ($mapping === NULL) {
      return NULL;
    }

    $idField = $mapping['id_field'];
    if (!$reviewEntity->hasField($idField) || $reviewEntity->get($idField)->isEmpty()) {
      return NULL;
    }

    $targetEntityId = (int) $reviewEntity->get($idField)->target_id;
    if ($targetEntityId === 0) {
      // Intentar con ->value para campos no entity_reference.
      $targetEntityId = (int) $reviewEntity->get($idField)->value;
    }
    if ($targetEntityId === 0) {
      return NULL;
    }

    if ($mapping['type_field'] !== NULL) {
      $targetEntityType = $reviewEntity->get($mapping['type_field'])->value ?? NULL;
      if ($targetEntityType === NULL) {
        return NULL;
      }
    }
    else {
      $targetEntityType = $mapping['fixed_type'];
    }

    return [$targetEntityType, $targetEntityId];
  }

  /**
   * Desnormaliza estadisticas en el target si tiene los campos adecuados.
   */
  protected function denormalizeToTarget(string $targetEntityType, int $targetEntityId, array $stats): void {
    try {
      $storage = $this->entityTypeManager->getStorage($targetEntityType);
      $target = $storage->load($targetEntityId);
      if ($target === NULL) {
        return;
      }

      $updated = FALSE;
      if ($target->hasField('average_rating')) {
        $target->set('average_rating', $stats['average']);
        $updated = TRUE;
      }
      if ($target->hasField('total_reviews')) {
        $target->set('total_reviews', $stats['count']);
        $updated = TRUE;
      }

      if ($updated) {
        $target->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not denormalize stats to @type @id: @msg', [
        '@type' => $targetEntityType,
        '@id' => $targetEntityId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
