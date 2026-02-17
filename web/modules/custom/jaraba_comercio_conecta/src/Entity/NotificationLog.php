<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_notification_log",
 *   label = @Translation("Log de Notificacion"),
 *   label_collection = @Translation("Logs de Notificaciones"),
 *   label_singular = @Translation("log de notificacion"),
 *   label_plural = @Translation("logs de notificaciones"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\NotificationLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_notification_log",
 *   admin_permission = "manage comercio notifications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-notification-log/{comercio_notification_log}",
 *     "collection" = "/admin/content/comercio-notification-logs",
 *   },
 * )
 */
class NotificationLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayConfigurable('form', TRUE);

    $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Plantilla'))
      ->setSetting('target_type', 'comercio_notification_template')
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['channel'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Canal'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 16)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Asunto'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Cuerpo'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'sent' => t('Enviado'),
        'delivered' => t('Entregado'),
        'failed' => t('Fallido'),
        'read' => t('Leido'),
      ])
      ->setDefaultValue('sent')
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Enviado'));

    $fields['read_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Leido'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON: datos de contexto adicionales'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
