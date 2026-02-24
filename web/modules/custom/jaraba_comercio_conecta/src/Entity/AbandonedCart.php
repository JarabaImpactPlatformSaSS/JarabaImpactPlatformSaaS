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
 *   id = "abandoned_cart",
 *   label = @Translation("Carrito Abandonado"),
 *   label_collection = @Translation("Carritos Abandonados"),
 *   label_singular = @Translation("carrito abandonado"),
 *   label_plural = @Translation("carritos abandonados"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\AbandonedCartListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\AbandonedCartAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "abandoned_cart",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-abandoned-cart/{abandoned_cart}",
 *     "collection" = "/admin/content/comercio-abandoned-carts",
 *   },
 *   field_ui_base_route = "entity.abandoned_cart.settings",
 * )
 */
class AbandonedCart extends ContentEntityBase implements EntityChangedInterface {

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

    $fields['cart_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Carrito'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_cart')
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['recovery_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token de recuperacion'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE);

    $fields['recovery_sent'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Email de recuperacion enviado'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['recovery_sent_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de envio de recuperacion'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['recovered'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recuperado'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['recovered_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de recuperacion'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['cart_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor del carrito'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
