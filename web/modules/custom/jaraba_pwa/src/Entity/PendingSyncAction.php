<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the PendingSyncAction entity.
 *
 * Stores queued actions from the service worker's Background Sync API.
 * When a user performs an action while offline, the action is queued
 * locally and synced to the server once connectivity is restored.
 *
 * @ContentEntityType(
 *   id = "pending_sync_action",
 *   label = @Translation("Pending Sync Action"),
 *   label_collection = @Translation("Pending Sync Actions"),
 *   label_singular = @Translation("pending sync action"),
 *   label_plural = @Translation("pending sync actions"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_pwa\PendingSyncActionListBuilder",
 *     "access" = "Drupal\jaraba_pwa\Access\PendingSyncActionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "pending_sync_action",
 *   admin_permission = "administer pwa",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pending-sync-actions",
 *     "canonical" = "/admin/content/pending-sync-actions/{pending_sync_action}",
 *     "delete-form" = "/admin/content/pending-sync-actions/{pending_sync_action}/delete",
 *   },
 *   field_ui_base_route = "entity.pending_sync_action.settings",
 * )
 */
class PendingSyncAction extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Type of action to perform when syncing.
    $fields['action_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Action Type'))
      ->setDescription(t('The type of action to perform on sync.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'create' => 'Create',
          'update' => 'Update',
          'delete' => 'Delete',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ]);

    // The entity type ID of the target entity.
    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setDescription(t('Machine name of the target entity type.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 128,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ]);

    // The entity ID of the target entity.
    $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Target Entity ID'))
      ->setDescription(t('ID of the target entity for the sync action.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 2,
      ]);

    // JSON payload with the data to sync.
    $fields['payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Payload'))
      ->setDescription(t('JSON payload containing the action data.'))
      ->setDefaultValue('{}')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 3,
      ]);

    // Sync processing status.
    $fields['sync_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sync Status'))
      ->setDescription(t('Current processing status of the sync action.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSettings([
        'allowed_values' => [
          'pending' => 'Pending',
          'syncing' => 'Syncing',
          'synced' => 'Synced',
          'failed' => 'Failed',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 4,
      ]);

    // Number of retry attempts so far.
    $fields['retry_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retry Count'))
      ->setDescription(t('Number of sync attempts performed.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ]);

    // Maximum retries allowed before marking as permanently failed.
    $fields['max_retries'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Retries'))
      ->setDescription(t('Maximum number of sync attempts allowed.'))
      ->setDefaultValue(3)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 6,
      ]);

    // Tenant reference (group entity).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this action belongs to.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 7,
      ]);

    // User who created the sync action.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who queued this sync action.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 8,
      ]);

    // Creation timestamp.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the sync action was queued.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 9,
      ]);

    return $fields;
  }

  /**
   * Gets the action type.
   */
  public function getActionType(): string {
    return $this->get('action_type')->value ?? '';
  }

  /**
   * Gets the target entity type ID.
   */
  public function getTargetEntityType(): string {
    return $this->get('target_entity_type')->value ?? '';
  }

  /**
   * Gets the target entity ID.
   */
  public function getTargetEntityId(): int {
    return (int) ($this->get('target_entity_id')->value ?? 0);
  }

  /**
   * Gets the payload as decoded array.
   */
  public function getPayload(): array {
    $raw = $this->get('payload')->value ?? '{}';
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Gets the sync status.
   */
  public function getSyncStatus(): string {
    return $this->get('sync_status')->value ?? 'pending';
  }

  /**
   * Gets the retry count.
   */
  public function getRetryCount(): int {
    return (int) ($this->get('retry_count')->value ?? 0);
  }

  /**
   * Gets the max retries.
   */
  public function getMaxRetries(): int {
    return (int) ($this->get('max_retries')->value ?? 3);
  }

  /**
   * Checks if the action can be retried.
   */
  public function canRetry(): bool {
    return $this->getRetryCount() < $this->getMaxRetries();
  }

  /**
   * Increments the retry count.
   *
   * @return $this
   */
  public function incrementRetry(): static {
    $this->set('retry_count', $this->getRetryCount() + 1);
    return $this;
  }

  /**
   * Marks the action as synced.
   *
   * @return $this
   */
  public function markSynced(): static {
    $this->set('sync_status', 'synced');
    return $this;
  }

  /**
   * Marks the action as failed.
   *
   * @return $this
   */
  public function markFailed(): static {
    $this->set('sync_status', 'failed');
    return $this;
  }

  /**
   * Marks the action as currently syncing.
   *
   * @return $this
   */
  public function markSyncing(): static {
    $this->set('sync_status', 'syncing');
    return $this;
  }

}
