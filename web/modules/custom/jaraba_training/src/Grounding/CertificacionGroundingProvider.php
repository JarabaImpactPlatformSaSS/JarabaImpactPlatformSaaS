<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Grounding;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * GroundingProvider para datos de certificaciones.
 *
 * AI-COVERAGE-001: Permite al copiloto buscar certificaciones, programas
 * y datos de evaluación para responder consultas de usuarios.
 * CASCADE-SEARCH-001: Nivel N2 (keyword match).
 */
class CertificacionGroundingProvider implements GroundingProviderInterface {

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
    $results = [];

    try {
      // Buscar en programas de certificación.
      $storage = $this->entityTypeManager->getStorage('certification_program');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->range(0, $limit);

      // Filtrar por keywords en título.
      if ($keywords !== []) {
        $orGroup = $query->orConditionGroup();
        foreach ($keywords as $kw) {
          $orGroup->condition('title', '%' . $kw . '%', 'LIKE');
        }
        $query->condition($orGroup);
      }

      $ids = $query->execute();
      $programs = $storage->loadMultiple($ids);

      foreach ($programs as $program) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $program */
        $results[] = [
          'title' => $program->label() ?? 'Programa de certificación',
          'summary' => $program->get('description')->value ?? '',
          'url' => '/admin/content/certification-program/' . $program->id(),
          'type' => 'certification_program',
          'metadata' => [
            'entry_fee' => $program->get('entry_fee')->value ?? 0,
            'cid_duration' => $program->get('cid_duration_days')->value ?? 90,
          ],
        ];
      }
    }
    catch (\Throwable) {
      // Graceful degradation.
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
