<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Grounding;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Grounding\GroundingProviderInterface;

/**
 * Grounding provider para perfiles de candidatos (vertical Empleabilidad).
 *
 * Permite al copilot buscar perfiles de candidatos por nombre, titular
 * profesional, resumen y localización para enriquecer respuestas sobre
 * empleabilidad y búsqueda de talento.
 *
 * Prioridad 70: por debajo de programas institucionales (80) pero por
 * encima de contenido editorial general (50).
 */
class CandidateGroundingProvider implements GroundingProviderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'empleabilidad';
  }

  /**
   * {@inheritdoc}
   *
   * Busca perfiles de candidatos por keyword match en nombre, headline,
   * summary y ciudad. Solo devuelve candidatos con disponibilidad activa
   * o pasiva (abiertos a oportunidades).
   *
   * @param array<string> $keywords
   *   Keywords extraidos del mensaje del usuario.
   * @param int $limit
   *   Maximo de resultados.
   *
   * @return array<int, array<string, mixed>>
   *   Resultados con title, summary, url, type, metadata.
   */
  public function search(array $keywords, int $limit = 3): array {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_profile');
    }
    catch (\Throwable) {
      return [];
    }

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('availability_status', ['active', 'passive'], 'IN')
      ->sort('changed', 'DESC')
      ->range(0, $limit);

    if ($keywords !== []) {
      $orGroup = $query->orConditionGroup();
      foreach ($keywords as $keyword) {
        $orGroup->condition('first_name', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('last_name', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('headline', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('summary', '%' . $keyword . '%', 'LIKE');
        $orGroup->condition('city', '%' . $keyword . '%', 'LIKE');
      }
      $query->condition($orGroup);
    }

    $ids = $query->execute();
    if ($ids === [] || !is_array($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      $firstName = (string) ($entity->get('first_name')->value ?? '');
      $lastName = (string) ($entity->get('last_name')->value ?? '');
      $fullName = trim($firstName . ' ' . $lastName);
      $headline = (string) ($entity->get('headline')->value ?? '');
      $summary = (string) ($entity->get('summary')->value ?? '');
      $city = (string) ($entity->get('city')->value ?? '');
      $experienceLevel = (string) ($entity->get('experience_level')->value ?? '');

      $title = $fullName !== '' ? $fullName : ($entity->label() ?? 'Candidato');
      if ($headline !== '') {
        $title .= ' — ' . $headline;
      }

      $snippet = $summary !== ''
        ? mb_substr(strip_tags($summary), 0, 200)
        : ($headline !== '' ? $headline : 'Perfil de candidato');

      try {
        $url = $entity->toUrl()->toString();
      }
      catch (\Throwable) {
        $url = '';
      }

      $results[] = [
        'title' => $title,
        'summary' => $snippet,
        'url' => $url,
        'type' => 'Perfil de candidato — Empleabilidad',
        'metadata' => [
          'ciudad' => $city,
          'nivel_experiencia' => $experienceLevel,
          'disponibilidad' => (string) ($entity->get('availability_status')->value ?? ''),
        ],
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 70;
  }

}
