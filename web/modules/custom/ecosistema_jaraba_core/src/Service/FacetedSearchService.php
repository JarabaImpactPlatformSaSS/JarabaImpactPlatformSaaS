<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical faceted search service.
 *
 * GAP-SEARCH-FACET: Orchestrates search across multiple entity types
 * and verticals, providing unified results with facet aggregation.
 */
class FacetedSearchService {

  /**
   * Entity types searchable per vertical.
   */
  protected const SEARCHABLE_TYPES = [
    'empleabilidad' => [
      'candidate_profile' => ['title_field' => 'headline', 'body_field' => 'summary'],
      'job_posting' => ['title_field' => 'title', 'body_field' => 'description'],
    ],
    'emprendimiento' => [
      'business_model_canvas' => ['title_field' => 'title', 'body_field' => NULL],
    ],
    'comercioconecta' => [
      'product_retail' => ['title_field' => 'title', 'body_field' => 'description'],
    ],
    'agroconecta' => [
      'product_agro' => ['title_field' => 'name', 'body_field' => 'description_short'],
    ],
    'formacion' => [
      'lms_course' => ['title_field' => 'title', 'body_field' => 'description'],
    ],
    'content_hub' => [
      'content_article' => ['title_field' => 'title', 'body_field' => 'excerpt'],
    ],
    'serviciosconecta' => [
      'service_offering' => ['title_field' => 'title', 'body_field' => 'description'],
    ],
  ];

  /**
   * Allowed filter fields.
   */
  protected const ALLOWED_FILTERS = [
    'vertical', 'entity_type', 'date_from', 'date_to',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Performs a cross-vertical faceted search.
   *
   * @param string $query
   *   Search query string.
   * @param array $filters
   *   Optional filters: vertical, entity_type, date_from, date_to.
   * @param int $page
   *   Page number (1-indexed).
   * @param int $limit
   *   Items per page (max 50).
   *
   * @return array
   *   Search results with facets.
   */
  public function search(string $query, array $filters = [], int $page = 1, int $limit = 20): array {
    $query = trim($query);
    if (mb_strlen($query) < 2) {
      return $this->emptyResult();
    }

    $limit = min(50, max(1, $limit));
    $verticalFilter = $filters['vertical'] ?? NULL;
    $entityTypeFilter = $filters['entity_type'] ?? NULL;

    $allResults = [];
    $facets = ['verticals' => [], 'types' => []];

    foreach (self::SEARCHABLE_TYPES as $vertical => $entityTypes) {
      // Apply vertical filter.
      if ($verticalFilter && $verticalFilter !== $vertical) {
        continue;
      }

      foreach ($entityTypes as $entityTypeId => $config) {
        // Apply entity type filter.
        if ($entityTypeFilter && $entityTypeFilter !== $entityTypeId) {
          continue;
        }

        // Check if entity type exists.
        if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
          continue;
        }

        try {
          $results = $this->searchEntityType(
            $entityTypeId,
            $config,
            $query,
            $vertical,
            $filters
          );

          // Aggregate facets.
          if (!empty($results)) {
            $facets['verticals'][$vertical] = ($facets['verticals'][$vertical] ?? 0) + count($results);
            $facets['types'][$entityTypeId] = ($facets['types'][$entityTypeId] ?? 0) + count($results);
          }

          $allResults = array_merge($allResults, $results);
        }
        catch (\Exception $e) {
          $this->logger->warning('Search error for @type: @error', [
            '@type' => $entityTypeId,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }

    $total = count($allResults);

    // Paginate.
    $offset = ($page - 1) * $limit;
    $pagedResults = array_slice($allResults, $offset, $limit);

    return [
      'results' => $pagedResults,
      'facets' => $facets,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
      'pages' => (int) ceil($total / $limit),
      'query' => $query,
    ];
  }

  /**
   * Searches a single entity type.
   *
   * @param string $entityTypeId
   *   Entity type machine name.
   * @param array $config
   *   Config with title_field and body_field.
   * @param string $query
   *   Search term.
   * @param string $vertical
   *   Vertical key.
   * @param array $filters
   *   Additional filters.
   *
   * @return array
   *   Array of result items.
   */
  protected function searchEntityType(
    string $entityTypeId,
    array $config,
    string $query,
    string $vertical,
    array $filters,
  ): array {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $titleField = $config['title_field'];
    $bodyField = $config['body_field'];

    $entityQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->range(0, 25);

    // Text search: title OR body contains query.
    if ($titleField && $bodyField) {
      $orGroup = $entityQuery->orConditionGroup()
        ->condition($titleField, '%' . $query . '%', 'LIKE')
        ->condition($bodyField, '%' . $query . '%', 'LIKE');
      $entityQuery->condition($orGroup);
    }
    elseif ($titleField) {
      $entityQuery->condition($titleField, '%' . $query . '%', 'LIKE');
    }

    // Published filter if field exists.
    $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
    $keys = $entityType->getKeys();
    if (isset($keys['published'])) {
      $entityQuery->condition($keys['published'], 1);
    }
    elseif ($entityType->hasKey('status')) {
      $entityQuery->condition('status', 1);
    }

    // Date filter.
    if (!empty($filters['date_from'])) {
      $entityQuery->condition('created', strtotime($filters['date_from']), '>=');
    }
    if (!empty($filters['date_to'])) {
      $entityQuery->condition('created', strtotime($filters['date_to']), '<=');
    }

    // Sort by changed desc.
    $entityQuery->sort('changed', 'DESC');

    $ids = $entityQuery->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      $title = '';
      if ($titleField && $entity->hasField($titleField)) {
        $title = $entity->get($titleField)->value ?? '';
      }
      if (!$title) {
        $title = $entity->label() ?? '';
      }

      $snippet = '';
      if ($bodyField && $entity->hasField($bodyField)) {
        $raw = $entity->get($bodyField)->value ?? '';
        $snippet = mb_substr(strip_tags($raw), 0, 200);
      }

      $results[] = [
        'id' => $entity->id(),
        'entity_type' => $entityTypeId,
        'vertical' => $vertical,
        'title' => $title,
        'snippet' => $snippet,
        'created' => $entity->get('created')->value ?? 0,
        'url' => $entity->toUrl()->toString(),
      ];
    }

    return $results;
  }

  /**
   * Returns available verticals for facet display.
   *
   * @return array
   *   Vertical labels keyed by machine name.
   */
  public function getAvailableVerticals(): array {
    return [
      'empleabilidad' => 'Empleabilidad',
      'emprendimiento' => 'Emprendimiento',
      'comercioconecta' => 'ComercioConecta',
      'agroconecta' => 'AgroConecta',
      'formacion' => 'Formación',
      'content_hub' => 'Blog / Contenido',
      'serviciosconecta' => 'ServiciosConecta',
    ];
  }

  /**
   * Returns an empty result set.
   */
  protected function emptyResult(): array {
    return [
      'results' => [],
      'facets' => ['verticals' => [], 'types' => []],
      'total' => 0,
      'page' => 1,
      'limit' => 20,
      'pages' => 0,
      'query' => '',
    ];
  }

}
