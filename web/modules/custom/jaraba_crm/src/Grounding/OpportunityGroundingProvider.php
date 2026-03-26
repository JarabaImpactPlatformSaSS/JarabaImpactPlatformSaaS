<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider for CRM opportunities.
 *
 * Exposes pipeline opportunities to the AI grounding system so the copilot
 * can reference real deal data when answering sales questions.
 */
class OpportunityGroundingProvider implements GroundingProviderInterface {

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
    try {
      $storage = $this->entityTypeManager->getStorage('crm_opportunity');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('title', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('notes', '%' . $keyword . '%', 'LIKE');
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

      $title = (string) ($entity->get('title')->value ?? '');
      $stage = (string) ($entity->get('stage')->value ?? '');
      $value = (float) ($entity->get('value')->value ?? 0);

      $summary = sprintf(
        'Oportunidad %s — Etapa: %s, Valor: %.0f EUR, Probabilidad: %d%%',
        $title,
        $stage,
        $value,
        (int) ($entity->get('probability')->value ?? 50),
      );

      $results[] = [
        'title' => $title,
        'summary' => mb_substr($summary, 0, 200),
        'url' => '/crm/pipeline',
        'type' => 'Oportunidad CRM — Ventas',
        'metadata' => [
          'stage' => $stage,
          'value_eur' => $value,
          'probability' => (int) ($entity->get('probability')->value ?? 50),
          'bant_score' => (int) ($entity->get('bant_score')->value ?? 0),
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 60;
  }

}
