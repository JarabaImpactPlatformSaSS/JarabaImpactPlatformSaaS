<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for email campaigns.
 */
class EmailCampaignGroundingProvider implements GroundingProviderInterface {

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
    if (!$this->entityTypeManager->hasDefinition('email_campaign')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('email_campaign');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('name', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('subject', '%' . $keyword . '%', 'LIKE');
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

      $name = $entity->label() ?? 'Campana';
      $subject = (string) ($entity->get('subject')->value ?? '');
      $status = (string) ($entity->get('status')->value ?? '');

      $results[] = [
        'title' => (string) $name,
        'summary' => mb_substr(sprintf('Campana: %s — Asunto: %s — Estado: %s', $name, $subject, $status), 0, 200),
        'url' => '/email/campaigns',
        'type' => 'Campana email — Marketing',
        'metadata' => [
          'subject' => $subject,
          'status' => $status,
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 40;
  }

}
