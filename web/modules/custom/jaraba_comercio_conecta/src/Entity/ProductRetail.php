<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Producto Retail.
 *
 * Estructura: Entidad principal del catálogo de ComercioConecta.
 *   Cada producto pertenece a un comerciante (merchant_id) y a un
 *   tenant (tenant_id). Puede tener variaciones (ProductVariationRetail)
 *   y stock multi-ubicación (StockLocation).
 *
 * Lógica: El producto tiene ciclo de vida: draft → active → paused →
 *   out_of_stock → archived. El stock_quantity es un campo desnormalizado
 *   que representa la suma de todas las ubicaciones para queries rápidas.
 *   Las imágenes multi-valor (max 10) forman la galería del producto.
 *   El Schema.org JSON-LD se genera dinámicamente en ProductRetailService.
 *
 * @ContentEntityType(
 *   id = "product_retail",
 *   label = @Translation("Producto Retail"),
 *   label_collection = @Translation("Productos Retail"),
 *   label_singular = @Translation("producto retail"),
 *   label_plural = @Translation("productos retail"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ProductRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ProductRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ProductRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ProductRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ProductRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "product_retail",
 *   admin_permission = "manage comercio products",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-product/{product_retail}",
 *     "add-form" = "/admin/content/comercio-product/add",
 *     "edit-form" = "/admin/content/comercio-product/{product_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-product/{product_retail}/delete",
 *     "collection" = "/admin/content/comercio-products",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.product_retail.settings",
 * )
 */
class ProductRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Datos principales ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Producto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categoría'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['comercio_category' => 'comercio_category']])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['brand_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Marca'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['comercio_brand' => 'comercio_brand']])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['short_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Descripción Corta'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    // --- Precio ---
    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio (EUR)'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['compare_at_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Anterior'))
      ->setDescription(t('Para mostrar descuento tachado.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cost_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio de Coste'))
      ->setDescription(t('No público. Para cálculo de margen.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['tax_rate'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo IVA'))
      ->setRequired(TRUE)
      ->setDefaultValue('general_21')
      ->setSetting('allowed_values', [
        'general_21' => t('IVA General (21%)'),
        'reducido_10' => t('IVA Reducido (10%)'),
        'superreducido_4' => t('IVA Superreducido (4%)'),
        'exento_0' => t('Exento (0%)'),
      ])
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    // --- Inventario ---
    $fields['stock_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Stock Total'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['low_stock_threshold'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Umbral Stock Bajo'))
      ->setDefaultValue(5)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE);

    $fields['has_variations'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Tiene Variaciones'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE);

    // --- Dimensiones ---
    $fields['weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso (kg)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 3)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dimensions_length'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Largo (cm)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dimensions_width'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Ancho (cm)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dimensions_height'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Alto (cm)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 33])
      ->setDisplayConfigurable('form', TRUE);

    // --- Código de barras ---
    $fields['barcode_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo Código'))
      ->setSetting('allowed_values', [
        'ean13' => 'EAN-13',
        'ean8' => 'EAN-8',
        'upc' => 'UPC',
        'isbn' => 'ISBN',
        'internal' => t('Interno'),
      ])
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE);

    $fields['barcode_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de Barras'))
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => 41])
      ->setDisplayConfigurable('form', TRUE);

    // --- SEO ---
    $fields['seo_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título SEO'))
      ->setSetting('max_length', 70)
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE);

    $fields['seo_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Description'))
      ->setSetting('max_length', 160)
      ->setDisplayOptions('form', ['weight' => 51])
      ->setDisplayConfigurable('form', TRUE);

    // --- Estado ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'active' => t('Activo'),
        'paused' => t('Pausado'),
        'out_of_stock' => t('Sin Stock'),
        'archived' => t('Archivado'),
      ])
      ->setDisplayOptions('form', ['weight' => 60])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Media ---
    $fields['images'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imágenes'))
      ->setRequired(TRUE)
      ->setCardinality(10)
      ->setSetting('file_directory', 'comercio/products')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 70])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
