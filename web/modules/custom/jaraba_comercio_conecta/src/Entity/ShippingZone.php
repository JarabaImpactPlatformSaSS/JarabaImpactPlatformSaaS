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
 *   id = "comercio_shipping_zone",
 *   label = @Translation("Zona de Envio"),
 *   label_collection = @Translation("Zonas de Envio"),
 *   label_singular = @Translation("zona de envio"),
 *   label_plural = @Translation("zonas de envio"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ShippingZoneForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ShippingZoneForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ShippingZoneForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ShippingZoneAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_shipping_zone",
 *   admin_permission = "manage comercio shipping zones",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-shipping-zone/{comercio_shipping_zone}",
 *     "add-form" = "/admin/content/comercio-shipping-zone/add",
 *     "edit-form" = "/admin/content/comercio-shipping-zone/{comercio_shipping_zone}/edit",
 *     "delete-form" = "/admin/content/comercio-shipping-zone/{comercio_shipping_zone}/delete",
 *     "collection" = "/admin/content/comercio-shipping-zones",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_shipping_zone.settings",
 * )
 */
class ShippingZone extends ContentEntityBase implements EntityChangedInterface {

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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['postal_codes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Codigos postales'))
      ->setDescription(t('JSON: array de patrones de codigo postal'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provinces'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Provincias'))
      ->setDescription(t('JSON: array de nombres de provincia'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['surcharge'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Recargo'))
      ->setDescription(t('Coste adicional para esta zona'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
