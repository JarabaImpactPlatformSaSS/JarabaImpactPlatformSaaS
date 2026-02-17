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
 * @ContentEntityType(
 *   id = "comercio_cart",
 *   label = @Translation("Carrito"),
 *   label_collection = @Translation("Carritos"),
 *   label_singular = @Translation("carrito"),
 *   label_plural = @Translation("carritos"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\CartForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\CartForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\CartForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CartAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_cart",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-cart/{comercio_cart}",
 *     "add-form" = "/admin/content/comercio-cart/add",
 *     "edit-form" = "/admin/content/comercio-cart/{comercio_cart}/edit",
 *     "delete-form" = "/admin/content/comercio-cart/{comercio_cart}/delete",
 *     "collection" = "/admin/content/comercio-carts",
 *   },
 * )
 */
class Cart extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session ID'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'active' => t('Activo'),
        'checkout' => t('En checkout'),
        'completed' => t('Completado'),
        'abandoned' => t('Abandonado'),
      ])
      ->setDefaultValue('active')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['coupon_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cupon aplicado'))
      ->setSetting('target_type', 'coupon_retail')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtotal'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Subtotal'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Descuento'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Metodo de envio'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_cost'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste de envio'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    $fields['total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
