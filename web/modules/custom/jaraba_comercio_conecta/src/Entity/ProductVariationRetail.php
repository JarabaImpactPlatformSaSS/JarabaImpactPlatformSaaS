<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Variación de Producto Retail.
 *
 * Estructura: Variación de un producto padre (talla, color, etc.).
 *   Cada variación tiene su propio SKU, precio, stock e imagen.
 *   La relación con el producto padre es many-to-one via product_id.
 *
 * Lógica: Las variaciones permiten gestionar combinaciones de atributos
 *   (ej: "Camiseta Azul - Talla M") con precios y stock independientes.
 *   El stock total del producto padre es la suma de todas sus variaciones.
 *   Los atributos se almacenan como JSON en el campo 'attributes' para
 *   máxima flexibilidad (no se necesita Field UI para atributos).
 *
 * @ContentEntityType(
 *   id = "product_variation_retail",
 *   label = @Translation("Variación de Producto"),
 *   label_collection = @Translation("Variaciones de Producto"),
 *   label_singular = @Translation("variación de producto"),
 *   label_plural = @Translation("variaciones de producto"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ProductVariationRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ProductVariationRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ProductVariationRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ProductVariationRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ProductVariationRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "product_variation_retail",
 *   admin_permission = "manage comercio products",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-variation/{product_variation_retail}",
 *     "add-form" = "/admin/content/comercio-variation/add",
 *     "edit-form" = "/admin/content/comercio-variation/{product_variation_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-variation/{product_variation_retail}/delete",
 *     "collection" = "/admin/content/comercio-variations",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.product_variation_retail.settings",
 * )
 */
class ProductVariationRetail extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * Estructura: Define los 16 campos base de la variación de producto.
   * Lógica: product_id es la FK al producto padre con cascade delete implícito.
   *   attributes almacena pares clave-valor como JSON para máxima flexibilidad.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Referencia al producto padre (relación many-to-one)
    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setDescription(t('Producto padre al que pertenece esta variación.'))
      ->setSetting('target_type', 'product_retail')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Aislamiento multi-tenant obligatorio
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta variación para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // SKU único de la variación
    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDescription(t('Código SKU único de la variación dentro del tenant.'))
      ->setSettings(['max_length' => 64])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nombre descriptivo de la variación
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la variación (ej: "Camiseta Azul - Talla M").'))
      ->setSettings(['max_length' => 255])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio de la variación (puede diferir del producto padre)
    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio'))
      ->setDescription(t('Precio de venta de esta variación en EUR. Puede diferir del precio del producto padre.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio anterior (para mostrar descuento)
    $fields['compare_at_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio anterior'))
      ->setDescription(t('Precio anterior de la variación para mostrar el descuento tachado.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Imagen específica de la variación
    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen'))
      ->setDescription(t('Imagen específica de esta variación (ej: foto del color concreto).'))
      ->setSetting('file_directory', 'comercio/variations')
      ->setSetting('alt_field', TRUE)
      ->setSetting('title_field', FALSE)
      ->setSetting('max_filesize', '5 MB')
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Atributos de la variación como JSON flexible
    $fields['attributes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Atributos'))
      ->setDescription(t('Pares clave-valor de atributos en formato JSON (ej: {"color": "Azul", "talla": "M"}).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 7,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Código de barras específico de la variación
    $fields['barcode_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de barras'))
      ->setDescription(t('Código de barras específico de esta variación (EAN, UPC, etc.).'))
      ->setSettings(['max_length' => 32])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Stock de la variación
    $fields['stock_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Stock'))
      ->setDescription(t('Cantidad en stock de esta variación (suma de todas las ubicaciones).'))
      ->setDefaultValue(0)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Peso override si diferente al producto padre
    $fields['weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso (kg)'))
      ->setDescription(t('Peso en kilogramos si difiere del producto padre.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 3)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de la variación
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la variación en el catálogo.'))
      ->setSetting('allowed_values', [
        'active' => t('Activa'),
        'inactive' => t('Inactiva'),
        'out_of_stock' => t('Agotada'),
      ])
      ->setDefaultValue('active')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Orden de presentación
    $fields['sort_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Orden de presentación entre las variaciones del mismo producto.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDescription(t('Fecha en que se creó la variación.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'))
      ->setDescription(t('Fecha de la última modificación de la variación.'));

    return $fields;
  }

}
