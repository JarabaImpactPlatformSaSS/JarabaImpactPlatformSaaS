<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para ofertas de empleo (job_posting).
 */
class JobPostingGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'empleabilidad';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('job_posting');
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
      $location = $entity->hasField('location') && !$entity->get('location')->isEmpty()
        ? (string) $entity->get('location')->value
        : '';

      $results[] = [
        'title' => $entity->label() ?? 'Oferta de empleo',
        'summary' => mb_substr(strip_tags((string) ($entity->get('description')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Oferta de empleo',
        'metadata' => [
          'ubicacion' => $location,
          'tipo' => (string) ($entity->get('job_type')->value ?? ''),
        ],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 50;
  }

}
