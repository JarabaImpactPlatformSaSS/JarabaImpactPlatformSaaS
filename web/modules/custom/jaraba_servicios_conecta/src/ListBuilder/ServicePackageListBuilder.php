<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de paquetes de servicios en admin.
 */
class ServicePackageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Paquete');
    $header['provider_id'] = $this->t('Profesional');
    $header['total_sessions'] = $this->t('Sesiones');
    $header['price'] = $this->t('Precio');
    $header['validity_days'] = $this->t('Validez');
    $header['is_published'] = $this->t('Publicado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $provider = $entity->get('provider_id')->entity;

    $row['title'] = $entity->get('title')->value;
    $row['provider_id'] = $provider ? $provider->get('display_name')->value : '-';
    $row['total_sessions'] = $entity->get('total_sessions')->value;
    $row['price'] = number_format((float) $entity->get('price')->value, 2, ',', '.') . ' €';
    $row['validity_days'] = $entity->get('validity_days')->value . ' días';
    $row['is_published'] = $entity->get('is_published')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
