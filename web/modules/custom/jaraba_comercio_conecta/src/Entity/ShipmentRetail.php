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
 *   id = "comercio_shipment",
 *   label = @Translation("Envio Retail"),
 *   label_collection = @Translation("Envios Retail"),
 *   label_singular = @Translation("envio retail"),
 *   label_plural = @Translation("envios retail"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ShipmentRetailListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ShipmentRetailForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ShipmentRetailForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ShipmentRetailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ShipmentRetailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_shipment",
 *   admin_permission = "manage comercio shipments",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "tracking_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-shipment/{comercio_shipment}",
 *     "add-form" = "/admin/content/comercio-shipment/add",
 *     "edit-form" = "/admin/content/comercio-shipment/{comercio_shipment}/edit",
 *     "delete-form" = "/admin/content/comercio-shipment/{comercio_shipment}/delete",
 *     "collection" = "/admin/content/comercio-shipments",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_shipment.settings",
 * )
 */
class ShipmentRetail extends ContentEntityBase implements EntityChangedInterface {

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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suborder_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sub-pedido'))
      ->setDescription(t('ID del sub-pedido asociado.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['carrier_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Transportista'))
      ->setSetting('target_type', 'comercio_carrier_config')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_method_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Metodo de envio'))
      ->setSetting('target_type', 'comercio_shipping_method')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tracking_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Numero de seguimiento'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tracking_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de seguimiento'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'picked_up' => t('Recogido'),
        'in_transit' => t('En transito'),
        'out_for_delivery' => t('En reparto'),
        'delivered' => t('Entregado'),
        'returned' => t('Devuelto'),
        'failed' => t('Fallido'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight_kg'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso (kg)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 3)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dimensions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Dimensiones'))
      ->setDescription(t('JSON: length, width, height en cm'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_cost'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste de envio'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estimated_delivery'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Entrega estimada'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['actual_delivery'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Entrega real'))
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
