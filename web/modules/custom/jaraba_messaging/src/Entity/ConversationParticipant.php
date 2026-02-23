<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Participante de conversación segura (tabla de unión).
 *
 * PROPÓSITO:
 * Vincula usuarios con conversaciones. Controla permisos granulares
 * (can_send, can_attach, can_invite), estado de lectura, y preferencias
 * de notificación por participante.
 *
 * LÓGICA:
 * - Constraint UNIQUE: (conversation_id, user_id).
 * - Status sigue máquina de estados: active -> left/removed/blocked.
 * - unread_count se actualiza atómicamente desde MessageService.
 * - Sin UI propia — gestionada vía ConversationService.
 *
 * @ContentEntityType(
 *   id = "conversation_participant",
 *   label = @Translation("Conversation Participant"),
 *   label_collection = @Translation("Participants"),
 *   label_singular = @Translation("participant"),
 *   label_plural = @Translation("participants"),
 *   label_count = @PluralTranslation(
 *     singular = "@count participant",
 *     plural = "@count participants",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_messaging\Access\SecureConversationAccessControlHandler",
 *   },
 *   base_table = "conversation_participant",
 *   admin_permission = "administer jaraba messaging",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class ConversationParticipant extends ContentEntityBase implements ConversationParticipantInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conversation'))
      ->setDescription(t('The conversation this participant belongs to.'))
      ->setSetting('target_type', 'secure_conversation')
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user participating in the conversation.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['role'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Role'))
      ->setDescription(t('Role of the participant in the conversation.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        ConversationParticipantInterface::ROLE_OWNER => t('Owner'),
        ConversationParticipantInterface::ROLE_PARTICIPANT => t('Participant'),
        ConversationParticipantInterface::ROLE_OBSERVER => t('Observer'),
      ])
      ->setDefaultValue(ConversationParticipantInterface::ROLE_PARTICIPANT);

    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display Name'))
      ->setDescription(t('Display name override for this conversation.'))
      ->setSetting('max_length', 128);

    $fields['can_send'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can Send'))
      ->setDescription(t('Whether the participant can send messages.'))
      ->setDefaultValue(TRUE);

    $fields['can_attach'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can Attach'))
      ->setDescription(t('Whether the participant can attach files.'))
      ->setDefaultValue(TRUE);

    $fields['can_invite'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can Invite'))
      ->setDescription(t('Whether the participant can invite others.'))
      ->setDefaultValue(FALSE);

    $fields['is_muted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Muted'))
      ->setDescription(t('Whether notifications are muted for this participant.'))
      ->setDefaultValue(FALSE);

    $fields['is_pinned'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Pinned'))
      ->setDescription(t('Whether the conversation is pinned for this participant.'))
      ->setDefaultValue(FALSE);

    $fields['last_read_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Read At'))
      ->setDescription(t('Timestamp of last read action.'));

    $fields['last_read_message_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last Read Message ID'))
      ->setDescription(t('ID of the last message read by this participant.'))
      ->setSetting('size', 'big')
      ->setDefaultValue(0);

    $fields['unread_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Unread Count'))
      ->setDescription(t('Number of unread messages for this participant.'))
      ->setDefaultValue(0);

    $fields['notification_pref'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Notification Preference'))
      ->setDescription(t('Notification preference for this conversation.'))
      ->setSetting('allowed_values', [
        ConversationParticipantInterface::NOTIFICATION_ALL => t('All messages'),
        ConversationParticipantInterface::NOTIFICATION_MENTIONS => t('Mentions only'),
        ConversationParticipantInterface::NOTIFICATION_NONE => t('None'),
      ])
      ->setDefaultValue(ConversationParticipantInterface::NOTIFICATION_ALL);

    $fields['joined_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Joined At'))
      ->setDescription(t('Timestamp when the user joined the conversation.'));

    $fields['left_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Left At'))
      ->setDescription(t('Timestamp when the user left the conversation.'));

    $fields['removed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Removed By'))
      ->setDescription(t('User who removed this participant.'))
      ->setSetting('target_type', 'user');

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Participant status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        ConversationParticipantInterface::STATUS_ACTIVE => t('Active'),
        ConversationParticipantInterface::STATUS_LEFT => t('Left'),
        ConversationParticipantInterface::STATUS_REMOVED => t('Removed'),
        ConversationParticipantInterface::STATUS_BLOCKED => t('Blocked'),
      ])
      ->setDefaultValue(ConversationParticipantInterface::STATUS_ACTIVE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last change.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversationId(): int {
    return (int) $this->get('conversation_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId(): int {
    return (int) $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRole(): string {
    return $this->get('role')->value ?? self::ROLE_PARTICIPANT;
  }

  /**
   * {@inheritdoc}
   */
  public function canSend(): bool {
    return (bool) $this->get('can_send')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function canAttach(): bool {
    return (bool) $this->get('can_attach')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnreadCount(): int {
    return (int) $this->get('unread_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->getStatus() === self::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? self::STATUS_ACTIVE;
  }

}
