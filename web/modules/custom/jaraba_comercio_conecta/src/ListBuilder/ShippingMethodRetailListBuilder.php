<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ShippingMethodRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['base_price'] = $this->t('Precio Base');
    $header['free_above'] = $this->t('Gratis Desde');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value;
    $row['base_price'] = number_format((float) $entity->get('base_price')->value, 2, ',', '.') . ' EUR';
    $free_above = $entity->get('free_above')->value;
    $row['free_above'] = $free_above ? number_format((float) $free_above, 2, ',', '.') . ' EUR' : '-';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
