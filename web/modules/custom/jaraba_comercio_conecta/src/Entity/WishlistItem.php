<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Item de Lista de Deseos.
 *
 * Estructura: Entidad hija de Wishlist (comercio_wishlist). Cada item
 *   referencia un producto (product_retail) y opcionalmente incluye una
 *   nota del usuario. Se gestiona programaticamente desde el portal.
 *
 * Logica: Los items se crean y eliminan via API desde el portal del
 *   cliente. No requiere formularios ni list builder — la gestion es
 *   integramente programatica. El campo added_at registra el momento
 *   en que el producto fue anadido a la lista.
 *
 * @ContentEntityType(
 *   id = "comercio_wishlist_item",
 *   label = @Translation("Item de Lista de Deseos"),
 *   label_collection = @Translation("Items de Lista de Deseos"),
 *   label_singular = @Translation("item de lista de deseos"),
 *   label_plural = @Translation("items de lista de deseos"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\WishlistItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_wishlist_item",
 *   admin_permission = "manage comercio wishlists",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-wishlist-item/{comercio_wishlist_item}",
 *     "collection" = "/admin/content/comercio-wishlist-items",
 *   },
 *   field_ui_base_route = "entity.comercio_wishlist_item.settings",
 * )
 */
class WishlistItem extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['wishlist_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Lista de deseos'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_wishlist')
      ->setDisplayConfigurable('form', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Producto'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'product_retail')
      ->setDisplayConfigurable('form', TRUE);

    $fields['added_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Anadido'));

    $fields['note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nota'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
