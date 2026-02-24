<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "order_item_retail",
 *   label = @Translation("Linea de Pedido"),
 *   label_collection = @Translation("Lineas de Pedido"),
 *   label_singular = @Translation("linea de pedido"),
 *   label_plural = @Translation("lineas de pedido"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\OrderItemRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\OrderItemRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\OrderItemRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\OrderItemRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "order_item_retail",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "product_title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-order-item/{order_item_retail}",
 *     "add-form" = "/admin/content/comercio-order-item/add",
 *     "edit-form" = "/admin/content/comercio-order-item/{order_item_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-order-item/{order_item_retail}/delete",
 *     "collection" = "/admin/content/comercio-order-items",
 *   },
 *   field_ui_base_route = "entity.order_item_retail.settings",
 * )
 */
class OrderItemRetail extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pedido'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'order_retail')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'product_retail')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['variation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Variacion'))
      ->setSetting('target_type', 'product_variation_retail')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setRequired(TRUE)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio unitario'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio total'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del producto'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
