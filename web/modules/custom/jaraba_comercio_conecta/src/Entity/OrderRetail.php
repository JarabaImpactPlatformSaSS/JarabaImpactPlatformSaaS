<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * @ContentEntityType(
 *   id = "order_retail",
 *   label = @Translation("Pedido Retail"),
 *   label_collection = @Translation("Pedidos Retail"),
 *   label_singular = @Translation("pedido retail"),
 *   label_plural = @Translation("pedidos retail"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\OrderRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\OrderRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\OrderRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\OrderRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\OrderRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "order_retail",
 *   admin_permission = "manage comercio orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "order_number",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-order/{order_retail}",
 *     "add-form" = "/admin/content/comercio-order/add",
 *     "edit-form" = "/admin/content/comercio-order/{order_retail}/edit",
 *     "delete-form" = "/admin/content/comercio-order/{order_retail}/delete",
 *     "collection" = "/admin/content/comercio-orders",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.order_retail.settings",
 * )
 */
class OrderRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if ($this->isNew() && empty($this->get('order_number')->value)) {
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('tenant_id', $this->get('tenant_id')->target_id)
        ->condition('order_number', "ORD-{$year}-", 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->count();
      $count = (int) $query->execute();
      $this->set('order_number', sprintf('ORD-%s-%04d', $year, $count + 1));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Numero de pedido'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['customer_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cliente'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'pending' => t('Pendiente'),
        'confirmed' => t('Confirmado'),
        'processing' => t('En preparacion'),
        'shipped' => t('Enviado'),
        'delivered' => t('Entregado'),
        'cancelled' => t('Cancelado'),
        'refunded' => t('Reembolsado'),
      ])
      ->setDefaultValue('draft')
      ->setDisplayOptions('form', ['weight' => 4])
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

    $fields['tax_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Impuestos'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_cost'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste de envio'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Descuento'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Metodo de pago'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del pago'))
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'paid' => t('Pagado'),
        'refunded' => t('Reembolsado'),
        'failed' => t('Fallido'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_intent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Payment Intent'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Direccion de envio'))
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Direccion de facturacion'))
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shipping_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Metodo de envio'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tracking_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Numero de seguimiento'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 33])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
