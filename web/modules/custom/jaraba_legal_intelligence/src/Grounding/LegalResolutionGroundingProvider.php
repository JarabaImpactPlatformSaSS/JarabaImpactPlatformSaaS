<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para resoluciones legales (legal_resolution).
 */
class LegalResolutionGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'jarabalex';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_legal', 'vigente')
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('title', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('abstract_ai', '%' . $keyword . '%', 'LIKE');
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
        'title' => $entity->label() ?? 'Resolución legal',
        'summary' => mb_substr(strip_tags((string) ($entity->get('abstract_ai')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Resolución legal — JarabaLex',
        'metadata' => [],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 50;
  }

}
