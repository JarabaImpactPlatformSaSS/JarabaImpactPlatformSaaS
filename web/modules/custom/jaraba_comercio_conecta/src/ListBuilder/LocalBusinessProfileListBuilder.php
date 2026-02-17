<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class LocalBusinessProfileListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['business_name'] = $this->t('Nombre del Negocio');
    $header['address_city'] = $this->t('Ciudad');
    $header['phone'] = $this->t('Telefono');
    $header['nap_consistency_score'] = $this->t('Consistencia NAP');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $score = $entity->get('nap_consistency_score')->value;

    $row['business_name'] = $entity->get('business_name')->value;
    $row['address_city'] = $entity->get('city')->value;
    $row['phone'] = $entity->get('phone')->value;
    $row['nap_consistency_score'] = $score !== NULL ? $score . '%' : $this->t('Sin evaluar');
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
