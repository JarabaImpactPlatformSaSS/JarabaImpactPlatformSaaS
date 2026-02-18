<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class SearchSynonymListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['term'] = $this->t('Termino');
    $header['synonyms'] = $this->t('Sinonimos');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $synonyms = $entity->get('synonyms')->value;
    $truncated = mb_strlen($synonyms) > 80 ? mb_substr($synonyms, 0, 80) . '...' : $synonyms;

    $row['term'] = $entity->get('term')->value;
    $row['synonyms'] = $truncated;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
