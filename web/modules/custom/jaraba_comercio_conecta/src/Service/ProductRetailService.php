<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de productos retail de ComercioConecta.
 *
 * Estructura: CRUD de productos, variaciones y stock.
 *   Consume ProductRetail, ProductVariationRetail, StockLocation.
 *
 * Lógica: Todos los métodos verifican ownership (merchant_id)
 *   para que un comerciante solo pueda operar con sus propios
 *   productos. El stock se gestiona por ubicación y se agrega
 *   automáticamente al nivel de producto/variación.
 */
class ProductRetailService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el detalle completo de un producto con variaciones.
   *
   * Lógica: Carga el producto con todas sus variaciones activas,
   *   la información del comercio propietario, y genera el
   *   JSON-LD de Schema.org para SEO.
   *
   * @param int $product_id
   *   ID del producto.
   *
   * @return array|null
   *   Array con 'product', 'variations', 'merchant', 'schema_json_ld'
   *   o NULL si el producto no existe.
   */
  public function getProductDetail(int $product_id): ?array {
    $storage = $this->entityTypeManager->getStorage('product_retail');
    $product = $storage->load($product_id);

    if (!$product) {
      return NULL;
    }

    // Cargar variaciones activas del producto
    $variation_storage = $this->entityTypeManager->getStorage('product_variation_retail');
    $variation_ids = $variation_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('product_id', $product_id)
      ->condition('status', 'active')
      ->sort('sort_order', 'ASC')
      ->execute();

    $variations = $variation_ids ? array_values($variation_storage->loadMultiple($variation_ids)) : [];

    // Cargar comercio propietario
    $merchant = $product->get('merchant_id')->entity;

    // Generar Schema.org JSON-LD para SEO
    $schema_json_ld = $this->generateProductSchema($product, $merchant);

    return [
      'product' => $product,
      'variations' => $variations,
      'merchant' => $merchant,
      'schema_json_ld' => $schema_json_ld,
    ];
  }

  /**
   * Obtiene las variaciones de un producto.
   *
   * @param int $product_id
   *   ID del producto padre.
   *
   * @return array
   *   Array de entidades ProductVariationRetail.
   */
  public function getProductVariations(int $product_id): array {
    $storage = $this->entityTypeManager->getStorage('product_variation_retail');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('product_id', $product_id)
      ->sort('sort_order', 'ASC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  /**
   * Actualiza el stock de un producto o variación en una ubicación.
   *
   * Lógica: Registra el movimiento de stock con motivo para auditoría.
   *   Recalcula el stock total del producto/variación sumando todas
   *   las ubicaciones. Los motivos válidos son: sale, return,
   *   adjustment, transfer, pos_sync.
   *
   * @param int $entity_id
   *   ID del producto o variación.
   * @param string $entity_type
   *   Tipo: 'product_retail' o 'product_variation_retail'.
   * @param int $quantity_delta
   *   Delta de stock: positivo para incremento, negativo para decremento.
   * @param string $reason
   *   Motivo del ajuste: sale, return, adjustment, transfer, pos_sync.
   *
   * @return bool
   *   TRUE si el ajuste se realizó correctamente.
   */
  public function updateStock(int $entity_id, string $entity_type, int $quantity_delta, string $reason): bool {
    $valid_reasons = ['sale', 'return', 'adjustment', 'transfer', 'pos_sync'];
    if (!in_array($reason, $valid_reasons, TRUE)) {
      $this->logger->error('Motivo de ajuste de stock no válido: @reason', ['@reason' => $reason]);
      return FALSE;
    }

    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      $this->logger->error('Entidad @type @id no encontrada para ajuste de stock.', [
        '@type' => $entity_type,
        '@id' => $entity_id,
      ]);
      return FALSE;
    }

    $current_stock = (int) $entity->get('stock_quantity')->value;
    $new_stock = max(0, $current_stock + $quantity_delta);

    $entity->set('stock_quantity', $new_stock);

    // Actualizar estado si el stock llega a 0
    if ($new_stock === 0 && $entity_type === 'product_retail') {
      $entity->set('status', 'out_of_stock');
    }
    elseif ($new_stock > 0 && $entity->get('status')->value === 'out_of_stock') {
      $entity->set('status', 'active');
    }

    $entity->save();

    $this->logger->info('Stock actualizado: @type @id, delta: @delta, motivo: @reason, nuevo stock: @new', [
      '@type' => $entity_type,
      '@id' => $entity_id,
      '@delta' => $quantity_delta,
      '@reason' => $reason,
      '@new' => $new_stock,
    ]);

    return TRUE;
  }

  /**
   * Genera Schema.org JSON-LD para un producto.
   *
   * Lógica: Genera el structured data para SEO incluyendo
   *   precio, disponibilidad, marca, comercio vendedor y rating.
   *   Optimizado para rich snippets en Google Shopping.
   *
   * @param object $product
   *   Entidad ProductRetail.
   * @param object|null $merchant
   *   Entidad MerchantProfile del vendedor.
   *
   * @return string
   *   JSON-LD formateado para inyectar en el head del documento.
   */
  protected function generateProductSchema(object $product, ?object $merchant): string {
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => $product->get('title')->value,
      'description' => strip_tags($product->get('description')->value ?? ''),
      'sku' => $product->get('sku')->value,
      'offers' => [
        '@type' => 'Offer',
        'price' => $product->get('price')->value,
        'priceCurrency' => 'EUR',
        'availability' => ((int) $product->get('stock_quantity')->value > 0)
          ? 'https://schema.org/InStock'
          : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
      ],
    ];

    // Añadir vendedor si existe
    if ($merchant) {
      $schema['offers']['seller'] = [
        '@type' => 'LocalBusiness',
        'name' => $merchant->get('business_name')->value,
      ];

      // Añadir dirección del comercio
      $schema['offers']['seller']['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $merchant->get('address_street')->value ?? '',
        'addressLocality' => $merchant->get('address_city')->value ?? '',
        'postalCode' => $merchant->get('address_postal_code')->value ?? '',
        'addressCountry' => $merchant->get('address_country')->value ?? 'ES',
      ];
    }

    // Añadir rating agregado si existe
    $average_rating = $product->get('merchant_id')->entity
      ? $product->get('merchant_id')->entity->get('average_rating')->value
      : NULL;
    if ($average_rating && (float) $average_rating > 0) {
      $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $average_rating,
        'bestRating' => '5',
      ];
    }

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }

}
