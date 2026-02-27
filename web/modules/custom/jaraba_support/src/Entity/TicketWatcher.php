<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Ticket Watcher entity.
 *
 * CC/mention watchers on tickets. Closes GAP-SUP-06.
 *
 * @ContentEntityType(
 *   id = "ticket_watcher",
 *   label = @Translation("Ticket Watcher"),
 *   label_collection = @Translation("Ticket Watchers"),
 *   label_singular = @Translation("ticket watcher"),
 *   label_plural = @Translation("ticket watchers"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *   },
 *   base_table = "ticket_watcher",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class TicketWatcher extends ContentEntityBase {

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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['watcher_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Watcher Type'))
      ->setRequired(TRUE)
      ->setDefaultValue('cc')
      ->setSetting('allowed_values', [
        'cc' => t('CC'),
        'mention' => t('Mention'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
