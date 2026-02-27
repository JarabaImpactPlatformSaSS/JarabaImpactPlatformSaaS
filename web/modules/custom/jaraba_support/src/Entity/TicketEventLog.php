<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Ticket Event Log entity (append-only).
 *
 * Immutable audit trail of all ticket lifecycle events.
 * No edit/delete forms — AccessHandler denies update/delete.
 *
 * SPEC: 178 — Section 3.5
 *
 * @ContentEntityType(
 *   id = "ticket_event_log",
 *   label = @Translation("Ticket Event Log"),
 *   label_collection = @Translation("Ticket Event Logs"),
 *   label_singular = @Translation("ticket event log"),
 *   label_plural = @Translation("ticket event logs"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\TicketEventLogAccessControlHandler",
 *   },
 *   base_table = "ticket_event_log",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class TicketEventLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['ticket_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ticket'))
      ->setSetting('target_type', 'support_ticket')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'created' => t('Created'),
        'assigned' => t('Assigned'),
        'status_changed' => t('Status Changed'),
        'priority_changed' => t('Priority Changed'),
        'escalated' => t('Escalated'),
        'sla_warning' => t('SLA Warning'),
        'sla_breached' => t('SLA Breached'),
        'ai_classified' => t('AI Classified'),
        'ai_responded' => t('AI Responded'),
        'resolved' => t('Resolved'),
        'closed' => t('Closed'),
        'reopened' => t('Reopened'),
        'merged' => t('Merged'),
        'tagged' => t('Tagged'),
        'watcher_added' => t('Watcher Added'),
        'parent_linked' => t('Parent Linked'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setDescription(t('User who caused the event. NULL = system.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Actor Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'customer' => t('Customer'),
        'agent' => t('Agent'),
        'ai' => t('AI'),
        'system' => t('System'),
        'eca' => t('ECA'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['old_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Old Value'))
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    $fields['new_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('New Value'))
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata'))
      ->setDescription(t('JSON with additional event data.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setSettings(['max_length' => 45])
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Immutable timestamp.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
