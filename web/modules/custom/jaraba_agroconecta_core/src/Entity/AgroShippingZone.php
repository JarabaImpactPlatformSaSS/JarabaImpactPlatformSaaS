<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AgroShippingZone.
 *
 * @ContentEntityType(
 *   id = "agro_shipping_zone",
 *   label = @Translation("Zona de Envío Agro"),
 *   label_collection = @Translation("Zonas de Envío Agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroShippingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_shipping_zone",
 *   admin_permission = "manage agro shipping",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/agro-shipping-zones",
 *     "add-form" = "/admin/structure/agro-shipping-zones/add",
 *     "canonical" = "/admin/structure/agro-shipping-zones/{agro_shipping_zone}",
 *     "edit-form" = "/admin/structure/agro-shipping-zones/{agro_shipping_zone}/edit",
 *     "delete-form" = "/admin/structure/agro-shipping-zones/{agro_shipping_zone}/delete",
 *   },
 * )
 */
class AgroShippingZone extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
;

    $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Productor'))
      ->setSetting('target_type', 'producer_profile')
      ->setDescription(t('Si está vacío, la zona es global para el tenant.'))
;

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Zona'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayOptions('view', ['label' => 'hidden', 'weight' => -10]);

    $fields['zone_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Definición'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'postal_codes' => t('Códigos Postales'),
        'provinces' => t('Provincias'),
        'countries' => t('Países'),
      ])
      ->setDefaultValue('postal_codes')
      ->setDisplayOptions('form', ['weight' => -5]);

    $fields['zone_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos de la Zona'))
      ->setDescription(t('Lista separada por comas de valores según el tipo seleccionado.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 5]);

    $fields['sort_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden de Evaluación'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10]);

    return $fields;
  }

}
