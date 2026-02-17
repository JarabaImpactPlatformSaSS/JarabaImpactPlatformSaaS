<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class CarrierConfigListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['carrier_name'] = $this->t('Transportista');
    $header['carrier_code'] = $this->t('Codigo');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['carrier_name'] = $entity->get('carrier_name')->value;
    $row['carrier_code'] = $entity->get('carrier_code')->value;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
