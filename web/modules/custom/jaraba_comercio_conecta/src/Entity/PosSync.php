<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_pos_sync",
 *   label = @Translation("Sincronizacion POS"),
 *   label_collection = @Translation("Sincronizaciones POS"),
 *   label_singular = @Translation("sincronizacion POS"),
 *   label_plural = @Translation("sincronizaciones POS"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\PosSyncAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_pos_sync",
 *   admin_permission = "manage comercio pos syncs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-pos-sync/{comercio_pos_sync}",
 *     "collection" = "/admin/content/comercio-pos-syncs",
 *   },
 * )
 */
class PosSync extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['connection_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conexion POS'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_pos_connection')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sync_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de sincronizacion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'stock' => t('Stock'),
        'price' => t('Precio'),
        'product' => t('Producto'),
        'order' => t('Pedido'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['direction'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Direccion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'inbound' => t('Entrante'),
        'outbound' => t('Saliente'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de entidad referenciada'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id_ref'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID de entidad referenciada'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['old_value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Valor anterior'))
      ->setDescription(t('JSON'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['new_value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Valor nuevo'))
      ->setDescription(t('JSON'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'applied' => t('Aplicado'),
        'failed' => t('Fallido'),
        'skipped' => t('Omitido'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje de error'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['synced_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Sincronizado'));

    return $fields;
  }

}
