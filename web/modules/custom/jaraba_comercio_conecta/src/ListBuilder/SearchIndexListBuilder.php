<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class SearchIndexListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['entity_type_ref'] = $this->t('Tipo de Entidad');
    $header['weight'] = $this->t('Peso');
    $header['is_active'] = $this->t('Activo');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->get('title')->value;
    $row['entity_type_ref'] = $entity->get('entity_type_ref')->value;
    $row['weight'] = $entity->get('weight')->value;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
