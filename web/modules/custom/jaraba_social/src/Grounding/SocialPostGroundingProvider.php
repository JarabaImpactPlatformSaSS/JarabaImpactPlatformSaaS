<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for social media posts.
 */
class SocialPostGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $keywords, int $limit = 3): array {
    if (!$this->entityTypeManager->hasDefinition('social_post')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
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
        $orGroup->condition('content', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('platform', '%' . $keyword . '%', 'LIKE');
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

      $content = (string) ($entity->get('content')->value ?? '');
      $platform = (string) ($entity->get('platform')->value ?? '');

      $results[] = [
        'title' => mb_substr(strip_tags($content), 0, 60) . '...',
        'summary' => mb_substr(sprintf('[%s] %s', $platform, strip_tags($content)), 0, 200),
        'url' => '/social/calendar',
        'type' => 'Post social — ' . ucfirst($platform),
        'metadata' => [
          'platform' => $platform,
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 35;
  }

}
