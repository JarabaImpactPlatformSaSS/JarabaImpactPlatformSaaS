<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AgroShippingRate.
 *
 * @ContentEntityType(
 *   id = "agro_shipping_rate",
 *   label = @Translation("Tarifa de Envío Agro"),
 *   label_collection = @Translation("Tarifas de Envío Agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroShippingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_shipping_rate",
 *   admin_permission = "manage agro shipping",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "service_code",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/agro-shipping-rates",
 *     "add-form" = "/admin/structure/agro-shipping-rates/add",
 *     "canonical" = "/admin/structure/agro-shipping-rates/{agro_shipping_rate}",
 *     "edit-form" = "/admin/structure/agro-shipping-rates/{agro_shipping_rate}/edit",
 *     "delete-form" = "/admin/structure/agro-shipping-rates/{agro_shipping_rate}/delete",
 *   },
 * )
 */
class AgroShippingRate extends ContentEntityBase {

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

    $fields['zone_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Zona de Envío'))
      ->setSetting('target_type', 'agro_shipping_zone')
      ->setRequired(TRUE)
;

    $fields['carrier_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Transportista'))
      ->setRequired(TRUE)
;

    $fields['service_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Servicio'))
      ->setRequired(TRUE);

    $fields['base_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tarifa Base'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE);

    $fields['per_kg_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste por Kg adicional'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0.00);

    $fields['free_shipping_threshold'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Umbral Envío Gratis'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['is_refrigerated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Tarifa Frío'))
      ->setDefaultValue(FALSE);

    $fields['min_weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso Mínimo'))
      ->setDefaultValue(0.000);

    $fields['max_weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso Máximo'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
