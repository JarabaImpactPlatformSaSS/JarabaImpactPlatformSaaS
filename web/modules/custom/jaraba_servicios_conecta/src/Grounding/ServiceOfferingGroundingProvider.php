<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para servicios profesionales (service_offering).
 */
class ServiceOfferingGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   *
   */
  public function getVerticalKey(): string {
    return 'serviciosconecta';
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
      $storage = $this->entityTypeManager->getStorage('service_offering');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_published', TRUE)
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
      $price = $entity->hasField('price') && !$entity->get('price')->isEmpty()
        ? number_format((float) $entity->get('price')->value, 2) . '€'
        : '';

      $results[] = [
        'title' => $entity->label() ?? 'Servicio profesional',
        'summary' => mb_substr(strip_tags((string) ($entity->get('description')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Servicio — ServiciosConecta',
        'metadata' => [
          'precio' => $price,
        ],
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
