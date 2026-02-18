<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad AgroShipment.
 *
 * ESTRUCTURA:
 * Representa un envío físico asociado a un sub-pedido. Permite gestionar
 * múltiples bultos o envíos parciales por productor.
 *
 * LÓGICA DE NEGOCIO:
 * - Número auto-generado SHP-YYYY-NNNNN.
 * - Estados normalizados (pending, label_created, in_transit, etc.).
 * - Soporte para cadena de frío (is_refrigerated).
 *
 * @ContentEntityType(
 *   id = "agro_shipment",
 *   label = @Translation("Envío AgroConecta"),
 *   label_collection = @Translation("Envíos AgroConecta"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\AgroShipmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AgroShipmentForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AgroShipmentForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AgroShipmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroShipmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_shipment",
 *   admin_permission = "manage agro shipments",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *     "label" = "shipment_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-shipments/{agro_shipment}",
 *     "add-form" = "/admin/content/agro-shipments/add",
 *     "edit-form" = "/admin/content/agro-shipments/{agro_shipment}/edit",
 *     "delete-form" = "/admin/content/agro-shipments/{agro_shipment}/delete",
 *     "collection" = "/admin/content/agro-shipments",
 *   },
 *   field_ui_base_route = "entity.agro_shipment.collection",
 * )
 */
class AgroShipment extends ContentEntityBase implements AgroShipmentInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getShipmentNumber(): string {
    return $this->get('shipment_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarrierId(): string {
    return $this->get('carrier_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackingNumber(): ?string {
    return $this->get('tracking_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingCost(): float {
    return (float) $this->get('shipping_cost')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isRefrigerated(): bool {
    return (bool) $this->get('is_refrigerated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave($storage) {
    parent::preSave($storage);

    if ($this->isNew() && empty($this->get('shipment_number')->value)) {
      $this->set('shipment_number', $this->generateShipmentNumber());
    }
  }

  /**
   * Genera el número de envío SHP-YYYY-NNNNN.
   *
   * @see ENTITY-AUTONUMBER-001
   */
  protected function generateShipmentNumber(): string {
    $year = date('Y');
    $storage = \Drupal::entityTypeManager()->getStorage('agro_shipment');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('shipment_number', "SHP-{$year}-", 'STARTS_WITH')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    $next = 1;
    if (!empty($ids)) {
      $latest = $storage->load(reset($ids));
      $latest_number = $latest->getShipmentNumber();
      if (preg_match('/-(\d+)$/', $latest_number, $matches)) {
        $next = (int) $matches[1] + 1;
      }
    }

    return "SHP-{$year}-" . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Multi-tenancy obligatorio.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
      ->setRequired(TRUE);

    $fields['sub_order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sub-pedido'))
      ->setDescription(t('ID del sub-pedido asociado.'))
      ->setRequired(FALSE);

    $fields['shipment_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de Envío'))
      ->setReadOnly(TRUE)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -10]);

    $fields['carrier_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Transportista'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -5])
      ->setDisplayOptions('form', ['weight' => -5]);

    $fields['service_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de Servicio'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -4])
      ->setDisplayOptions('form', ['weight' => -4]);

    $fields['tracking_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de Seguimiento'))
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -3])
      ->setDisplayOptions('form', ['weight' => -3]);

    $fields['tracking_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL de Seguimiento'))
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -2]);

    $fields['state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'label_created' => t('Etiqueta Generada'),
        'picked_up' => t('Recogido'),
        'in_transit' => t('En Tránsito'),
        'out_for_delivery' => t('En Reparto'),
        'delivery_attempt' => t('Intento Fallido'),
        'delivered' => t('Entregado'),
        'returned' => t('Devuelto'),
        'exception' => t('Incidencia'),
        'cancelled' => t('Cancelado'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -1])
      ->setDisplayOptions('form', ['weight' => -1]);

    $fields['label_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL Etiqueta PDF'));

    $fields['label_generated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Etiqueta'));

    $fields['weight_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 3)
      ->setRequired(TRUE);

    $fields['weight_unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unidad de Peso'))
      ->setDefaultValue('kg');

    $fields['is_refrigerated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Requiere Frío'))
      ->setDefaultValue(FALSE);

    $fields['shipping_cost'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste de Envío'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0.00);

    $fields['pickup_scheduled_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Recogida Programada'));

    $fields['delivered_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Entrega Real'));

    $fields['delivery_signature'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recibido por'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
