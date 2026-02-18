<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_cart_item",
 *   label = @Translation("Item de Carrito"),
 *   label_collection = @Translation("Items de Carrito"),
 *   label_singular = @Translation("item de carrito"),
 *   label_plural = @Translation("items de carrito"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CartItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_cart_item",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-cart-item/{comercio_cart_item}",
 *     "collection" = "/admin/content/comercio-cart-items",
 *   },
 * )
 */
class CartItem extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cart_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Carrito'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_cart')
      ->setDisplayConfigurable('form', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'product_retail')
      ->setDisplayConfigurable('form', TRUE);

    $fields['variation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Variacion'))
      ->setSetting('target_type', 'product_variation_retail')
      ->setDisplayConfigurable('form', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad'))
      ->setRequired(TRUE)
      ->setDefaultValue(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio unitario'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
