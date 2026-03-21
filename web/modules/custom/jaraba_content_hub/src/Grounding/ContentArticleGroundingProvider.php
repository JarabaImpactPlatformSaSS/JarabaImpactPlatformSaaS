<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para articulos del Content Hub (content_article).
 */
class ContentArticleGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'jaraba_content_hub';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('content_article');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'published')
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('title', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('answer_capsule', '%' . $keyword . '%', 'LIKE');
      }
      $query->condition($orGroup);
    }

    $ids = $query->execute();
    if ($ids === []) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }
      $capsule = $entity->hasField('answer_capsule') && !$entity->get('answer_capsule')->isEmpty()
        ? (string) $entity->get('answer_capsule')->value
        : '';

      $results[] = [
        'title' => $entity->label() ?? 'Artículo',
        'summary' => $capsule !== '' ? $capsule : mb_substr(strip_tags((string) ($entity->get('seo_description')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Artículo — Content Hub',
        'metadata' => [],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 40;
  }

}
