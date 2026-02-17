<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "suborder_retail",
 *   label = @Translation("Sub-pedido Retail"),
 *   label_collection = @Translation("Sub-pedidos Retail"),
 *   label_singular = @Translation("sub-pedido retail"),
 *   label_plural = @Translation("sub-pedidos retail"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\SuborderRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\SuborderRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\SuborderRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\SuborderRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "suborder_retail",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-suborder/{suborder_retail}",
 *     "add-form" = "/admin/content/comercio-suborder/add",
 *     "edit-form" = "/admin/content/comercio-suborder/{suborder_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-suborder/{suborder_retail}/delete",
 *     "collection" = "/admin/content/comercio-suborders",
 *   },
 * )
 */
class SuborderRetail extends ContentEntityBase implements EntityChangedInterface {

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
      ->setLabel(t('Pedido principal'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'order_retail')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'confirmed' => t('Confirmado'),
        'processing' => t('En preparacion'),
        'shipped' => t('Enviado'),
        'delivered' => t('Entregado'),
        'cancelled' => t('Cancelado'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subtotal'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Subtotal'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['commission_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tasa de comision'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['commission_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe de comision'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['merchant_payout'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Pago al comerciante'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    $fields['payout_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del pago'))
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'processing' => t('Procesando'),
        'paid' => t('Pagado'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE);

    $fields['stripe_transfer_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Transfer ID'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
