<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the MobileDevice entity.
 *
 * Represents a registered mobile device for push notifications and
 * app-level tracking. Each device belongs to a user and tenant.
 *
 * @ContentEntityType(
 *   id = "mobile_device",
 *   label = @Translation("Mobile Device"),
 *   label_collection = @Translation("Mobile Devices"),
 *   label_singular = @Translation("mobile device"),
 *   label_plural = @Translation("mobile devices"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mobile\MobileDeviceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mobile\Entity\MobileDeviceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mobile_device",
 *   admin_permission = "administer jaraba mobile",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "device_model",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/mobile-devices/{mobile_device}",
 *     "edit-form" = "/admin/content/mobile-devices/{mobile_device}/edit",
 *     "delete-form" = "/admin/content/mobile-devices/{mobile_device}/delete",
 *     "collection" = "/admin/content/mobile-devices",
 *   },
 *   field_ui_base_route = "entity.mobile_device.settings",
 * )
 */
class MobileDevice extends ContentEntityBase implements MobileDeviceInterface, EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getDeviceToken(): string {
    return (string) $this->get('device_token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(): string {
    return (string) $this->get('platform')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPushEnabled(): bool {
    return (bool) $this->get('push_enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this device belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who owns this device.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['device_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Device Token'))
      ->setDescription(t('The push notification token for this device.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Platform'))
      ->setDescription(t('The device platform.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'ios' => 'iOS',
        'android' => 'Android',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['os_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('OS Version'))
      ->setDescription(t('The operating system version.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    $fields['app_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('App Version'))
      ->setDescription(t('The mobile app version.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    $fields['device_model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Device Model'))
      ->setDescription(t('The device model name.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['biometric_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Biometric Enabled'))
      ->setDescription(t('Whether biometric authentication is enabled.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['push_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Push Enabled'))
      ->setDescription(t('Whether push notifications are enabled for this device.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_active'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Last Active'))
      ->setDescription(t('The last time this device was active.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether this device registration is active.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
