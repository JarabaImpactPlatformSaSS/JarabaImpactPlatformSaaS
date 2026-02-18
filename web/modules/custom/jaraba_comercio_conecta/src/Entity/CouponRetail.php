<?php

declare(strict_types=1);

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
 *   id = "coupon_retail",
 *   label = @Translation("Cupon de Descuento"),
 *   label_collection = @Translation("Cupones de Descuento"),
 *   label_singular = @Translation("cupon de descuento"),
 *   label_plural = @Translation("cupones de descuento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\CouponRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\CouponRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\CouponRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\CouponRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CouponRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "coupon_retail",
 *   admin_permission = "manage comercio coupons",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "code",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-coupon/{coupon_retail}",
 *     "add-form" = "/admin/content/comercio-coupon/add",
 *     "edit-form" = "/admin/content/comercio-coupon/{coupon_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-coupon/{coupon_retail}/delete",
 *     "collection" = "/admin/content/comercio-coupons",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.coupon_retail.settings",
 * )
 */
class CouponRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo del cupon'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Descripcion'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de descuento'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'percentage' => t('Porcentaje'),
        'fixed_amount' => t('Importe fijo'),
        'free_shipping' => t('Envio gratuito'),
      ])
      ->setDefaultValue('percentage')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor del descuento'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['min_order_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Pedido minimo'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['max_uses'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usos maximos totales'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['max_uses_per_user'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usos maximos por usuario'))
      ->setDefaultValue(1)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['current_uses'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usos actuales'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    $fields['valid_from'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Valido desde'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['valid_until'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Valido hasta'))
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'active' => t('Activo'),
        'inactive' => t('Inactivo'),
        'expired' => t('Expirado'),
      ])
      ->setDefaultValue('active')
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
