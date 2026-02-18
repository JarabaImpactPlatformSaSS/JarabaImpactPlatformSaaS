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
 *   id = "comercio_carrier_config",
 *   label = @Translation("Configuracion de Transportista"),
 *   label_collection = @Translation("Configuraciones de Transportista"),
 *   label_singular = @Translation("configuracion de transportista"),
 *   label_plural = @Translation("configuraciones de transportista"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\CarrierConfigListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\CarrierConfigForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\CarrierConfigForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\CarrierConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\CarrierConfigAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_carrier_config",
 *   admin_permission = "manage comercio carrier configs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "carrier_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-carrier-config/{comercio_carrier_config}",
 *     "add-form" = "/admin/content/comercio-carrier-config/add",
 *     "edit-form" = "/admin/content/comercio-carrier-config/{comercio_carrier_config}/edit",
 *     "delete-form" = "/admin/content/comercio-carrier-config/{comercio_carrier_config}/delete",
 *     "collection" = "/admin/content/comercio-carrier-configs",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_carrier_config.settings",
 * )
 */
class CarrierConfig extends ContentEntityBase implements EntityChangedInterface {

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

    $fields['carrier_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del transportista'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['carrier_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo del transportista'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['api_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de la API'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['api_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Key'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['api_secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Secret'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['tracking_url_pattern'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Patron URL de seguimiento'))
      ->setDescription(t('Ej: https://www.mrw.es/seguimiento/{tracking}'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['config_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion adicional'))
      ->setDescription(t('JSON: configuracion especifica del transportista'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
