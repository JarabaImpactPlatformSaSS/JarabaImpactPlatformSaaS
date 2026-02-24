<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the PushNotification entity.
 *
 * APPEND-ONLY: This entity records all push notifications sent.
 * Update and delete operations are blocked to preserve the audit trail.
 *
 * @ContentEntityType(
 *   id = "push_notification",
 *   label = @Translation("Push Notification"),
 *   label_collection = @Translation("Push Notifications"),
 *   label_singular = @Translation("push notification"),
 *   label_plural = @Translation("push notifications"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mobile\PushNotificationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mobile\Entity\PushNotificationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "push_notification",
 *   admin_permission = "administer jaraba mobile",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/push-notifications/{push_notification}",
 *     "collection" = "/admin/content/push-notifications",
 *   },
 *   field_ui_base_route = "entity.push_notification.settings",
 * )
 */
class PushNotification extends ContentEntityBase implements PushNotificationInterface {

  /**
   * Delivery status constants.
   */
  const STATUS_QUEUED = 'queued';
  const STATUS_SENT = 'sent';
  const STATUS_DELIVERED = 'delivered';
  const STATUS_OPENED = 'opened';
  const STATUS_FAILED = 'failed';

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return (string) $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): string {
    return (string) $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannel(): string {
    return (string) $this->get('channel')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId(): int {
    return (int) $this->get('recipient_id')->target_id;
  }

  /**
   * Gets a human-readable status label.
   *
   * @return string
   *   Translated status label.
   */
  public function getStatusLabel(): string {
    $labels = [
      self::STATUS_QUEUED => t('Queued'),
      self::STATUS_SENT => t('Sent'),
      self::STATUS_DELIVERED => t('Delivered'),
      self::STATUS_OPENED => t('Opened'),
      self::STATUS_FAILED => t('Failed'),
    ];
    return (string) ($labels[$this->getStatus()] ?? $this->getStatus());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this notification belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient'))
      ->setDescription(t('The user who receives this notification.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The notification title.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Body'))
      ->setDescription(t('The notification body text.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('JSON payload for the notification.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['channel'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Channel'))
      ->setDescription(t('The notification channel.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'general' => 'General',
        'jobs' => 'Jobs',
        'orders' => 'Orders',
        'alerts' => 'Alerts',
        'marketing' => 'Marketing',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Priority'))
      ->setDescription(t('The notification priority.'))
      ->setRequired(TRUE)
      ->setDefaultValue('normal')
      ->setSetting('allowed_values', [
        'high' => 'High',
        'normal' => 'Normal',
        'low' => 'Low',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['deep_link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Deep Link'))
      ->setDescription(t('Deep link URL to open when notification is tapped.'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Sent At'))
      ->setDescription(t('When the notification was sent to the push service.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['delivered_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Delivered At'))
      ->setDescription(t('When the notification was delivered to the device.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['opened_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Opened At'))
      ->setDescription(t('When the user opened the notification.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Delivery status of the notification.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_QUEUED)
      ->setSetting('allowed_values', [
        'queued' => 'Queued',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'opened' => 'Opened',
        'failed' => 'Failed',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
