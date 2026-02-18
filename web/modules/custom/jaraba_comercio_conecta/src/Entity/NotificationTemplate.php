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
 *   id = "comercio_notification_template",
 *   label = @Translation("Plantilla de Notificacion"),
 *   label_collection = @Translation("Plantillas de Notificacion"),
 *   label_singular = @Translation("plantilla de notificacion"),
 *   label_plural = @Translation("plantillas de notificacion"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\NotificationTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\NotificationTemplateForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\NotificationTemplateForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\NotificationTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\NotificationTemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_notification_template",
 *   admin_permission = "manage comercio notifications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-notification-template/{comercio_notification_template}",
 *     "add-form" = "/admin/content/comercio-notification-template/add",
 *     "edit-form" = "/admin/content/comercio-notification-template/{comercio_notification_template}/edit",
 *     "delete-form" = "/admin/content/comercio-notification-template/{comercio_notification_template}/delete",
 *     "collection" = "/admin/content/comercio-notification-templates",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.notification_template.settings",
 * )
 */
class NotificationTemplate extends ContentEntityBase implements EntityChangedInterface {

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

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre maquina'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['channel'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Canal'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'email' => t('Email'),
        'push' => t('Push'),
        'in_app' => t('In-App'),
        'sms' => t('SMS'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject_template'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plantilla de asunto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body_template'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Plantilla de cuerpo'))
      ->setRequired(TRUE)
      ->setDescription(t('Soporta variables con {{variable}}'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
