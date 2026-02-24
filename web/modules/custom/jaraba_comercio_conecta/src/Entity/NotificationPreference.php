<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_notification_pref",
 *   label = @Translation("Preferencia de Notificacion"),
 *   label_collection = @Translation("Preferencias de Notificacion"),
 *   label_singular = @Translation("preferencia de notificacion"),
 *   label_plural = @Translation("preferencias de notificacion"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\NotificationPreferenceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_notification_pref",
 *   admin_permission = "manage comercio notifications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-notification-pref/{comercio_notification_pref}",
 *     "collection" = "/admin/content/comercio-notification-prefs",
 *   },
 *   field_ui_base_route = "entity.comercio_notification_pref.settings",
 * )
 */
class NotificationPreference extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['channel'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Canal'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 16)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Categoria'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Habilitado'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
