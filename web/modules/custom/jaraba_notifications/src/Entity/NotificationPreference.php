<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad NotificationPreference.
 *
 * Almacena las preferencias de notificacion por usuario y tipo.
 * Cada registro representa un tipo de notificacion (system, social,
 * workflow, ai, marketing) con toggles para cada canal de entrega
 * (email, push, in_app).
 *
 * Convencion: 1 entidad por usuario × tipo de notificacion (5 por usuario).
 *
 * @ContentEntityType(
 *   id = "notification_preference",
 *   label = @Translation("Notification Preference"),
 *   label_collection = @Translation("Notification Preferences"),
 *   label_singular = @Translation("notification preference"),
 *   label_plural = @Translation("notification preferences"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_notifications\Access\NotificationPreferenceAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "notification_preference",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   admin_permission = "administer notifications",
 * )
 */
class NotificationPreference extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * Tipos de notificacion validos.
   */
  public const VALID_TYPES = [
    'system',
    'social',
    'workflow',
    'ai',
    'marketing',
  ];

  /**
   * Canales de entrega validos.
   */
  public const VALID_CHANNELS = [
    'email',
    'push',
    'in_app',
  ];

  /**
   * Valores por defecto de canales por tipo de notificacion.
   */
  public const TYPE_DEFAULTS = [
    'system' => ['email' => TRUE, 'push' => TRUE, 'in_app' => TRUE],
    'social' => ['email' => TRUE, 'push' => FALSE, 'in_app' => TRUE],
    'workflow' => ['email' => TRUE, 'push' => TRUE, 'in_app' => TRUE],
    'ai' => ['email' => FALSE, 'push' => FALSE, 'in_app' => TRUE],
    'marketing' => ['email' => TRUE, 'push' => FALSE, 'in_app' => FALSE],
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE);

    $fields['notification_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Notification Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'system' => 'System',
        'social' => 'Social',
        'workflow' => 'Workflow',
        'ai' => 'AI',
        'marketing' => 'Marketing',
      ]);

    $fields['email_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Email'))
      ->setDefaultValue(TRUE);

    $fields['push_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Push'))
      ->setDefaultValue(FALSE);

    $fields['in_app_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('In-App'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Obtiene el tipo de notificacion.
   */
  public function getNotificationType(): string {
    return $this->get('notification_type')->value ?? '';
  }

  /**
   * Verifica si un canal esta habilitado.
   */
  public function isChannelEnabled(string $channel): bool {
    $field = $channel . '_enabled';
    if (!$this->hasField($field)) {
      return TRUE;
    }
    return (bool) $this->get($field)->value;
  }

  /**
   * Establece el estado de un canal.
   */
  public function setChannelEnabled(string $channel, bool $enabled): static {
    $field = $channel . '_enabled';
    if ($this->hasField($field)) {
      $this->set($field, $enabled);
    }
    return $this;
  }

}
