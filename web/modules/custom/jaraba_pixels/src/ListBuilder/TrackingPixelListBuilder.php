<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de pixels de seguimiento en admin.
 */
class TrackingPixelListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['platform'] = $this->t('Plataforma');
    $header['pixel_id'] = $this->t('Pixel ID');
    $header['tenant_id'] = $this->t('Tenant');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value ?? '-';
    $row['platform'] = $entity->get('platform')->value ?? '-';
    $row['pixel_id'] = $entity->get('pixel_id')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
