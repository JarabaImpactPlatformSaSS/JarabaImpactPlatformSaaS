<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de ubicaciones de stock en /admin/content/comercio-stock-locations.
 *
 * Estructura: Extiende EntityListBuilder para la tabla de administración.
 *
 * Lógica: Muestra nombre, tipo, comercio, flags de pickup/ship-from,
 *   prioridad y estado para gestión rápida del inventario multi-ubicación.
 */
class StockLocationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['type'] = $this->t('Tipo');
    $header['merchant'] = $this->t('Comercio');
    $header['is_pickup_point'] = $this->t('Recogida');
    $header['is_ship_from'] = $this->t('Envío');
    $header['priority'] = $this->t('Prioridad');
    $header['is_active'] = $this->t('Activa');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value;
    $row['type'] = $entity->get('type')->value ?? '-';

    // Nombre del comercio propietario
    $merchant = $entity->get('merchant_id')->entity;
    $row['merchant'] = $merchant ? $merchant->get('business_name')->value : '-';

    // Flags como iconos de texto
    $row['is_pickup_point'] = $entity->get('is_pickup_point')->value ? '✓' : '—';
    $row['is_ship_from'] = $entity->get('is_ship_from')->value ? '✓' : '—';
    $row['priority'] = $entity->get('priority')->value ?? '1';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
