<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\CommandBar;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides cross-entity search results for the command bar.
 *
 * Searches multiple entity types (articles, pages, users) with
 * relevance-ranked scoring (exact > starts_with > contains).
 *
 * Directivas:
 * - INNERHTML-XSS-001: Output sanitizado con Html::escape()
 * - API-WHITELIST-001: Entity types definidos en SEARCHABLE_ENTITIES
 */
class EntitySearchCommandProvider implements CommandProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity types to search with config.
   *
   * @var array<string, array{field: string, icon: string, category: string}>
   */
  private const SEARCHABLE_ENTITIES = [
    'content_article' => [
      'field' => 'title',
      'icon' => 'article',
      'category' => 'Articles',
      'status_field' => 'status',
      'status_value' => 'published',
    ],
    'page_content' => [
      'field' => 'title',
      'icon' => 'description',
      'category' => 'Pages',
      'status_field' => NULL,
      'status_value' => NULL,
    ],
    'user' => [
      'field' => 'name',
      'icon' => 'person',
      'category' => 'Users',
      'status_field' => 'status',
      'status_value' => '1',
    ],
  ];

  /**
   * Constructs an EntitySearchCommandProvider.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function search(string $query, int $limit = 5): array {
    $allResults = [];
    $perTypeLimit = (int) ceil($limit / count(self::SEARCHABLE_ENTITIES));
    $queryLower = mb_strtolower($query);

    foreach (self::SEARCHABLE_ENTITIES as $entityTypeId => $config) {
      try {
        $typeResults = $this->searchEntityType($entityTypeId, $config, $query, $queryLower, $perTypeLimit);
        $allResults = array_merge($allResults, $typeResults);
      }
      catch (\Exception $e) {
        // Skip failing entity types gracefully.
        continue;
      }
    }

    // Sort by score descending.
    usort($allResults, fn(array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

    return array_slice($allResults, 0, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(AccountInterface $account): bool {
    return $account->hasPermission('access content');
  }

  /**
   * Searches a single entity type.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param array $config
   *   Entity type search configuration.
   * @param string $query
   *   Original search query.
   * @param string $queryLower
   *   Lowercased search query for scoring.
   * @param int $limit
   *   Maximum results per type.
   *
   * @return array
   *   Matching results with relevance scores.
   */
  protected function searchEntityType(string $entityTypeId, array $config, string $query, string $queryLower, int $limit): array {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $field = $config['field'];

    $entityQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition($field, '%' . $query . '%', 'LIKE')
      ->range(0, $limit);

    // Apply status filter if configured.
    if (!empty($config['status_field']) && $config['status_value'] !== NULL) {
      $entityQuery->condition($config['status_field'], $config['status_value']);
    }

    $ids = $entityQuery->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];
    $category = (string) $this->t($config['category']);

    foreach ($entities as $entity) {
      $label = (string) ($entity->label() ?? '');
      $labelLower = mb_strtolower($label);

      // Relevance scoring: exact > starts_with > contains.
      $score = 55;
      if ($labelLower === $queryLower) {
        $score = 95;
      }
      elseif (str_starts_with($labelLower, $queryLower)) {
        $score = 75;
      }

      $url = $this->getEntityUrl($entity, $entityTypeId);

      $results[] = [
        'label' => Html::escape($label),
        'url' => $url,
        'icon' => $config['icon'],
        'category' => $category,
        'score' => $score,
      ];
    }

    return $results;
  }

  /**
   * Gets the frontend URL for an entity.
   *
   * @param object $entity
   *   The loaded entity.
   * @param string $entityTypeId
   *   The entity type ID.
   *
   * @return string
   *   The URL string.
   */
  protected function getEntityUrl(object $entity, string $entityTypeId): string {
    // Special handling for content_article slugs.
    if ($entityTypeId === 'content_article' && method_exists($entity, 'getSlug')) {
      $slug = $entity->getSlug();
      if (!empty($slug)) {
        try {
          return Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->toString();
        }
        catch (\Exception $e) {
          // Fallback below.
        }
      }
    }

    // Try canonical URL.
    try {
      if ($entity->hasLinkTemplate('canonical')) {
        return $entity->toUrl('canonical')->toString();
      }
    }
    catch (\Exception $e) {
      // Fallback below.
    }

    // Hardcoded fallbacks.
    return match ($entityTypeId) {
      'user' => '/user/' . $entity->id(),
      'page_content' => '/page/' . $entity->id(),
      'content_article' => '/content-hub/articles/' . $entity->id(),
      default => '/' . str_replace('_', '-', $entityTypeId) . '/' . $entity->id(),
    };
  }

}
