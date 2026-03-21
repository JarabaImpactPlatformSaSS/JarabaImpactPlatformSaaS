<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para acciones formativas Andalucia +ei.
 *
 * Prioridad 80: superior a contenido general porque los programas
 * institucionales con incentivos tienen alta relevancia para conversion.
 */
class FormativeActionGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getVerticalKey(): string {
    return 'andalucia_ei';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string> $keywords
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('estado', ['en_ejecucion', 'vobo_aprobado'], 'IN')
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('titulo', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('descripcion', '%' . $keyword . '%', 'LIKE');
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
        'title' => $entity->label() ?? 'Acción formativa',
        'summary' => mb_substr(strip_tags((string) ($entity->get('descripcion')->value ?? '')), 0, 200),
        'url' => $entity->toUrl()->toString(),
        'type' => 'Programa formativo — Andalucía +ei',
        'metadata' => [
          'tipo' => (string) ($entity->get('tipo_formacion')->value ?? ''),
          'horas' => (string) ($entity->get('horas_previstas')->value ?? ''),
        ],
      ];
    }

    return $results;
  }

  public function getPriority(): int {
    return 80;
  }

}
