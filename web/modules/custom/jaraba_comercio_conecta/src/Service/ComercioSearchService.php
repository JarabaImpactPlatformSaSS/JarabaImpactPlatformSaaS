<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class ComercioSearchService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Full-text search en el indice comercio_search_index.
   *
   * Filtra por category_ids, entity_type_ref, is_active.
   * Si se proporcionan lat/lng, ordena por distancia.
   *
   * @param string $query
   *   Termino de busqueda.
   * @param array $filters
   *   Filtros opcionales: category_ids, entity_type_ref, is_active.
   * @param float|null $lat
   *   Latitud para busqueda geolocal.
   * @param float|null $lng
   *   Longitud para busqueda geolocal.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de resultados con entity_type_ref, entity_id_ref, title, distance.
   */
  public function search(string $query, array $filters = [], float $lat = NULL, float $lng = NULL, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_index');
      $entity_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('is_active', 1);

      // Text search on title and search_text.
      if (!empty($query)) {
        $group = $entity_query->orConditionGroup()
          ->condition('title', '%' . $query . '%', 'LIKE')
          ->condition('search_text', '%' . $query . '%', 'LIKE')
          ->condition('keywords', '%' . $query . '%', 'LIKE');
        $entity_query->condition($group);

        // Expand synonyms.
        $synonyms = $this->getSynonyms($query);
        if (!empty($synonyms)) {
          foreach ($synonyms as $synonym) {
            $group->condition('search_text', '%' . $synonym . '%', 'LIKE');
          }
        }
      }

      // Apply filters.
      if (!empty($filters['entity_type_ref'])) {
        $entity_query->condition('entity_type_ref', $filters['entity_type_ref']);
      }
      if (isset($filters['is_active'])) {
        $entity_query->condition('is_active', (int) $filters['is_active']);
      }

      $entity_query->sort('weight', 'DESC');
      $entity_query->range(0, $limit);
      $ids = $entity_query->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $result = [
          'entity_type_ref' => $entity->get('entity_type_ref')->value,
          'entity_id_ref' => $entity->get('entity_id_ref')->value,
          'title' => $entity->get('title')->value,
          'distance' => NULL,
        ];

        // Calculate distance if geo coordinates provided.
        if ($lat !== NULL && $lng !== NULL) {
          $entity_lat = (float) $entity->get('location_lat')->value;
          $entity_lng = (float) $entity->get('location_lng')->value;
          if ($entity_lat && $entity_lng) {
            $result['distance'] = $this->calculateDistance($lat, $lng, $entity_lat, $entity_lng);
          }
        }

        $results[] = $result;
      }

      // Sort by distance if geo search.
      if ($lat !== NULL && $lng !== NULL) {
        usort($results, function ($a, $b) {
          if ($a['distance'] === NULL && $b['distance'] === NULL) {
            return 0;
          }
          if ($a['distance'] === NULL) {
            return 1;
          }
          if ($b['distance'] === NULL) {
            return -1;
          }
          return $a['distance'] <=> $b['distance'];
        });
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en busqueda comercio: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Busqueda rapida por prefijo en el campo title.
   *
   * @param string $query
   *   Termino de busqueda.
   * @param int $limit
   *   Numero maximo de sugerencias.
   *
   * @return array
   *   Array de sugerencias con title y entity info.
   */
  public function autocomplete(string $query, int $limit = 10): array {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_index');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('is_active', 1)
        ->condition('title', $query . '%', 'LIKE')
        ->sort('weight', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $suggestions = [];

      foreach ($entities as $entity) {
        $suggestions[] = [
          'title' => $entity->get('title')->value,
          'entity_type_ref' => $entity->get('entity_type_ref')->value,
          'entity_id_ref' => $entity->get('entity_id_ref')->value,
        ];
      }

      return $suggestions;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en autocomplete comercio: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Crea o actualiza una entrada en el indice de busqueda.
   *
   * @param string $entityType
   *   Tipo de entidad (product_retail, merchant_profile).
   * @param int $entityId
   *   ID de la entidad.
   *
   * @return bool
   *   TRUE si se indexo correctamente.
   */
  public function indexEntity(string $entityType, int $entityId): bool {
    try {
      $source_storage = $this->entityTypeManager->getStorage($entityType);
      $source_entity = $source_storage->load($entityId);

      if (!$source_entity) {
        $this->logger->warning('Entidad @type:@id no encontrada para indexar.', [
          '@type' => $entityType,
          '@id' => $entityId,
        ]);
        return FALSE;
      }

      // Build index data based on entity type.
      $index_data = $this->buildIndexData($entityType, $source_entity);
      if (empty($index_data)) {
        return FALSE;
      }

      // Check for existing index entry.
      $index_storage = $this->entityTypeManager->getStorage('comercio_search_index');
      $existing = $index_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_type_ref', $entityType)
        ->condition('entity_id_ref', $entityId)
        ->execute();

      if (!empty($existing)) {
        $index_entity = $index_storage->load(reset($existing));
        foreach ($index_data as $field => $value) {
          $index_entity->set($field, $value);
        }
      }
      else {
        $index_data['entity_type_ref'] = $entityType;
        $index_data['entity_id_ref'] = $entityId;
        $index_entity = $index_storage->create($index_data);
      }

      $index_entity->save();

      $this->logger->info('Entidad @type:@id indexada correctamente.', [
        '@type' => $entityType,
        '@id' => $entityId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error indexando entidad @type:@id: @e', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Elimina una entrada del indice de busqueda.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   *
   * @return bool
   *   TRUE si se elimino correctamente.
   */
  public function removeFromIndex(string $entityType, int $entityId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_index');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_type_ref', $entityType)
        ->condition('entity_id_ref', $entityId)
        ->execute();

      if (empty($ids)) {
        return FALSE;
      }

      $entities = $storage->loadMultiple($ids);
      $storage->delete($entities);

      $this->logger->info('Entidad @type:@id eliminada del indice.', [
        '@type' => $entityType,
        '@id' => $entityId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando del indice @type:@id: @e', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Reindexa todos los productos y perfiles de comercio.
   *
   * @return int
   *   Numero de entidades indexadas.
   */
  public function reindexAll(): int {
    $count = 0;

    // Reindex products.
    try {
      $product_storage = $this->entityTypeManager->getStorage('product_retail');
      $product_ids = $product_storage->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($product_ids as $id) {
        if ($this->indexEntity('product_retail', (int) $id)) {
          $count++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error reindexando productos: @e', ['@e' => $e->getMessage()]);
    }

    // Reindex merchants.
    try {
      $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');
      $merchant_ids = $merchant_storage->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($merchant_ids as $id) {
        if ($this->indexEntity('merchant_profile', (int) $id)) {
          $count++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error reindexando comercios: @e', ['@e' => $e->getMessage()]);
    }

    $this->logger->info('Reindexacion completa: @count entidades indexadas.', ['@count' => $count]);

    return $count;
  }

  /**
   * Registra una busqueda en el log.
   *
   * @param string $query
   *   Termino buscado.
   * @param int $resultsCount
   *   Numero de resultados.
   * @param int|null $uid
   *   ID del usuario.
   * @param string|null $sessionId
   *   ID de sesion.
   * @param float|null $lat
   *   Latitud.
   * @param float|null $lng
   *   Longitud.
   * @param array $filters
   *   Filtros aplicados.
   */
  public function logSearch(string $query, int $resultsCount, ?int $uid = NULL, ?string $sessionId = NULL, ?float $lat = NULL, ?float $lng = NULL, array $filters = []): void {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_log');
      $log_entry = $storage->create([
        'query' => $query,
        'results_count' => $resultsCount,
        'uid' => $uid,
        'session_id' => $sessionId,
        'latitude' => $lat,
        'longitude' => $lng,
        'filters' => !empty($filters) ? json_encode($filters) : NULL,
      ]);
      $log_entry->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando busqueda: @e', ['@e' => $e->getMessage()]);
    }
  }

  /**
   * Obtiene analiticas de busqueda.
   *
   * @param int $days
   *   Numero de dias a analizar.
   *
   * @return array
   *   top_terms, zero_result_terms, volume_trend.
   */
  public function getSearchAnalytics(int $days = 30): array {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_log');
      $since = \Drupal::time()->getRequestTime() - ($days * 86400);

      // Top search terms.
      $query = $this->database->select('comercio_search_log', 'sl')
        ->fields('sl', ['query'])
        ->condition('sl.created', $since, '>=')
        ->groupBy('sl.query')
        ->orderBy('search_count', 'DESC')
        ->range(0, 20);
      $query->addExpression('COUNT(sl.id)', 'search_count');
      $top_terms = $query->execute()->fetchAll();

      // Zero-result searches.
      $zero_query = $this->database->select('comercio_search_log', 'sl')
        ->fields('sl', ['query'])
        ->condition('sl.created', $since, '>=')
        ->condition('sl.results_count', 0)
        ->groupBy('sl.query')
        ->orderBy('zero_count', 'DESC')
        ->range(0, 20);
      $zero_query->addExpression('COUNT(sl.id)', 'zero_count');
      $zero_result_terms = $zero_query->execute()->fetchAll();

      // Volume trend by day.
      $volume_query = $this->database->select('comercio_search_log', 'sl')
        ->condition('sl.created', $since, '>=')
        ->groupBy('search_day');
      $volume_query->addExpression("DATE(FROM_UNIXTIME(sl.created))", 'search_day');
      $volume_query->addExpression('COUNT(sl.id)', 'daily_count');
      $volume_query->orderBy('search_day', 'ASC');
      $volume_trend = $volume_query->execute()->fetchAll();

      return [
        'top_terms' => $top_terms,
        'zero_result_terms' => $zero_result_terms,
        'volume_trend' => $volume_trend,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo analiticas de busqueda: @e', ['@e' => $e->getMessage()]);
      return [
        'top_terms' => [],
        'zero_result_terms' => [],
        'volume_trend' => [],
      ];
    }
  }

  /**
   * Obtiene los sinonimos para un termino.
   *
   * @param string $term
   *   Termino a buscar.
   *
   * @return array
   *   Array de sinonimos.
   */
  public function getSynonyms(string $term): array {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_search_synonym');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('term', $term)
        ->condition('is_active', 1)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entity = $storage->load(reset($ids));
      $synonyms_text = $entity->get('synonyms')->value;

      return array_map('trim', explode(',', $synonyms_text));
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo sinonimos para @term: @e', [
        '@term' => $term,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye datos de indice a partir de una entidad fuente.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param mixed $entity
   *   Entidad fuente.
   *
   * @return array
   *   Datos para el indice.
   */
  protected function buildIndexData(string $entityType, $entity): array {
    $data = [
      'is_active' => 1,
      'weight' => 0,
      'boost_factor' => 1.0,
    ];

    switch ($entityType) {
      case 'product_retail':
        $data['title'] = $entity->get('title')->value ?? '';
        $data['search_text'] = $entity->get('description')->value ?? '';
        $data['keywords'] = '';
        $data['weight'] = 10;
        break;

      case 'merchant_profile':
        $data['title'] = $entity->get('business_name')->value ?? '';
        $data['search_text'] = $entity->get('description')->value ?? '';
        $data['keywords'] = '';
        $data['weight'] = 20;
        if ($entity->hasField('latitude') && $entity->get('latitude')->value) {
          $data['location_lat'] = $entity->get('latitude')->value;
          $data['location_lng'] = $entity->get('longitude')->value;
        }
        break;

      default:
        $this->logger->warning('Tipo de entidad no soportado para indexacion: @type', ['@type' => $entityType]);
        return [];
    }

    return $data;
  }

  /**
   * Calcula la distancia en kilometros entre dos puntos usando Haversine.
   *
   * @param float $lat1
   *   Latitud punto 1.
   * @param float $lng1
   *   Longitud punto 1.
   * @param float $lat2
   *   Latitud punto 2.
   * @param float $lng2
   *   Longitud punto 2.
   *
   * @return float
   *   Distancia en kilometros.
   */
  protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earth_radius = 6371;

    $lat_diff = deg2rad($lat2 - $lat1);
    $lng_diff = deg2rad($lng2 - $lng1);

    $a = sin($lat_diff / 2) * sin($lat_diff / 2)
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
      * sin($lng_diff / 2) * sin($lng_diff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($earth_radius * $c, 2);
  }

}
