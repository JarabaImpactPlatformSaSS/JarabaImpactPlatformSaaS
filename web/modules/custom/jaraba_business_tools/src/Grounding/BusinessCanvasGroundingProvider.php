<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para Business Model Canvas (emprendimiento).
 */
class BusinessCanvasGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'emprendimiento';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('business_model_canvas');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('title', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('description', '%' . $keyword . '%', 'LIKE');
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
      $results[] = [
        'title' => $entity->label() ?? 'Business Model Canvas',
        'summary' => mb_substr(strip_tags((string) ($entity->get('description')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Business Canvas — Emprendimiento',
        'metadata' => [
          'fase' => (string) ($entity->get('business_stage')->value ?? ''),
        ],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 50;
  }

}
