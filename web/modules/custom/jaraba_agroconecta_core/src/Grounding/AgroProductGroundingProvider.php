<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para productos agro (product_agro).
 */
class AgroProductGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   *
   */
  public function getVerticalKey(): string {
    return 'agroconecta';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   *
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('product_agro');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('name', '%' . $keyword . '%', 'LIKE');
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
        'title' => $entity->label() ?? 'Producto agrícola',
        'summary' => mb_substr(strip_tags((string) ($entity->get('description')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Producto agrícola — AgroConecta',
        'metadata' => [],
      ];
    }

    return $results;
  }

  /**
   *
   */
  public function getPriority(): int {
    return 50;
  }

}
