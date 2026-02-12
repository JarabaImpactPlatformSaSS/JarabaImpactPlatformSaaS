<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de productos retail en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/comercio-products.
 *
 * Lógica: Muestra columnas clave: título, SKU, precio, stock,
 *   estado y nombre del comerciante propietario.
 */
class ProductRetailListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Producto');
    $header['sku'] = $this->t('SKU');
    $header['price'] = $this->t('Precio');
    $header['stock'] = $this->t('Stock');
    $header['status'] = $this->t('Estado');
    $header['merchant'] = $this->t('Comercio');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activo'),
      'paused' => $this->t('Pausado'),
      'out_of_stock' => $this->t('Sin Stock'),
      'archived' => $this->t('Archivado'),
    ];

    $status = $entity->get('status')->value;
    $merchant_name = '';
    $merchant_ref = $entity->get('merchant_id')->entity;
    if ($merchant_ref) {
      $merchant_name = $merchant_ref->get('business_name')->value;
    }

    $row['title'] = $entity->get('title')->value;
    $row['sku'] = $entity->get('sku')->value;
    $row['price'] = number_format((float) $entity->get('price')->value, 2, ',', '.') . ' €';
    $row['stock'] = $entity->get('stock_quantity')->value;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['merchant'] = $merchant_name;
    return $row + parent::buildRow($entity);
  }

}
