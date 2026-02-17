<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_pos_conflict",
 *   label = @Translation("Conflicto POS"),
 *   label_collection = @Translation("Conflictos POS"),
 *   label_singular = @Translation("conflicto POS"),
 *   label_plural = @Translation("conflictos POS"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\PosConflictAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_pos_conflict",
 *   admin_permission = "manage comercio pos conflicts",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-pos-conflict/{comercio_pos_conflict}",
 *     "collection" = "/admin/content/comercio-pos-conflicts",
 *   },
 * )
 */
class PosConflict extends ContentEntityBase {

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

    $fields['sync_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sincronizacion'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_pos_sync')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del campo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['platform_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Valor en plataforma'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pos_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Valor en POS'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Resolucion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'platform_wins' => t('Plataforma prevalece'),
        'pos_wins' => t('POS prevalece'),
        'manual' => t('Manual'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resuelto por'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Resuelto en'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['detected_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Detectado'));

    return $fields;
  }

}
