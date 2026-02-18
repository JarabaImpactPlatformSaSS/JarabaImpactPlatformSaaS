<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de variaciones de producto en /admin/content/comercio-variations.
 *
 * Estructura: Extiende EntityListBuilder para mostrar las variaciones
 *   en la tabla de administración con columnas clave.
 *
 * Lógica: Muestra título, SKU, precio, stock, estado y producto padre
 *   para que el administrador pueda gestionar rápidamente el inventario
 *   de variaciones sin entrar al detalle de cada una.
 */
class ProductVariationRetailListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Sintaxis: Devuelve array de cabeceras para la tabla de listado.
   *   Cada clave es el identificador de la columna.
   *
   * @return array
   *   Array asociativo de cabeceras de columna.
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Nombre');
    $header['sku'] = $this->t('SKU');
    $header['price'] = $this->t('Precio');
    $header['stock_quantity'] = $this->t('Stock');
    $header['status'] = $this->t('Estado');
    $header['product'] = $this->t('Producto');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * Sintaxis: Devuelve array de celdas para una fila de la tabla.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   La entidad ProductVariationRetail de la fila actual.
   *
   * @return array
   *   Array de celdas renderizadas.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->get('title')->value;
    $row['sku'] = $entity->get('sku')->value;

    // Precio formateado con símbolo de euro
    $price = $entity->get('price')->value;
    $row['price'] = $price ? number_format((float) $price, 2, ',', '.') . ' €' : '-';

    $row['stock_quantity'] = $entity->get('stock_quantity')->value ?? '0';
    $row['status'] = $entity->get('status')->value ?? '-';

    // Nombre del producto padre si existe la referencia
    $product = $entity->get('product_id')->entity;
    $row['product'] = $product ? $product->get('title')->value : '-';

    return $row + parent::buildRow($entity);
  }

}
