<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "coupon_redemption",
 *   label = @Translation("Canje de Cupon"),
 *   label_collection = @Translation("Canjes de Cupon"),
 *   label_singular = @Translation("canje de cupon"),
 *   label_plural = @Translation("canjes de cupon"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CouponRedemptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "coupon_redemption",
 *   admin_permission = "manage comercio coupons",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-coupon-redemption/{coupon_redemption}",
 *     "collection" = "/admin/content/comercio-coupon-redemptions",
 *   },
 * )
 */
class CouponRedemption extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['coupon_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cupon'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'coupon_retail')
      ->setDisplayConfigurable('form', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pedido'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'order_retail')
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['discount_applied'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Descuento aplicado'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
