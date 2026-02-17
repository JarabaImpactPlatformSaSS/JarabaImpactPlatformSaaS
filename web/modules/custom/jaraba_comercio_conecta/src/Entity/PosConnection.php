<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * @ContentEntityType(
 *   id = "comercio_pos_connection",
 *   label = @Translation("Conexion POS"),
 *   label_collection = @Translation("Conexiones POS"),
 *   label_singular = @Translation("conexion POS"),
 *   label_plural = @Translation("conexiones POS"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\PosConnectionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\PosConnectionForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\PosConnectionForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\PosConnectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\PosConnectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_pos_connection",
 *   admin_permission = "manage comercio pos connections",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-pos-connection/{comercio_pos_connection}",
 *     "add-form" = "/admin/content/comercio-pos-connection/add",
 *     "edit-form" = "/admin/content/comercio-pos-connection/{comercio_pos_connection}/edit",
 *     "delete-form" = "/admin/content/comercio-pos-connection/{comercio_pos_connection}/delete",
 *     "collection" = "/admin/content/comercio-pos-connections",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_pos_connection.settings",
 * )
 */
class PosConnection extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

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

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Proveedor'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'sumup' => t('SumUp'),
        'zettle' => t('Zettle'),
        'square' => t('Square'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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

    $fields['webhook_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del webhook'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de ubicacion'))
      ->setDescription(t('ID de ubicacion del proveedor'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'active' => t('Activo'),
        'disconnected' => t('Desconectado'),
        'error' => t('Error'),
      ])
      ->setDefaultValue('active')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_sync_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima sincronizacion'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sync_frequency'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Frecuencia de sincronizacion'))
      ->setDescription(t('En segundos'))
      ->setDefaultValue(300)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
