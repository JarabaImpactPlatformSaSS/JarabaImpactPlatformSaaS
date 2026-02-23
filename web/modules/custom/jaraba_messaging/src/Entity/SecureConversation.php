<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Conversación segura cifrada con AES-256-GCM.
 *
 * PROPÓSITO:
 * Almacena metadatos de conversación entre participantes.
 * Los mensajes se almacenan en tabla custom (secure_message)
 * por requerimientos de MEDIUMBLOB/VARBINARY.
 *
 * LÓGICA:
 * - tenant_id aisla datos por tenant (multi-tenancy).
 * - status sigue máquina de estados: active -> archived/closed -> deleted.
 * - is_confidential excluye del indexado Qdrant/IA.
 * - last_message_* se actualizan desde MessageService para queries eficientes.
 *
 * @ContentEntityType(
 *   id = "secure_conversation",
 *   label = @Translation("Secure Conversation"),
 *   label_collection = @Translation("Conversations"),
 *   label_singular = @Translation("conversation"),
 *   label_plural = @Translation("conversations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count conversation",
 *     plural = "@count conversations",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_messaging\ListBuilder\SecureConversationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_messaging\Form\SecureConversationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_messaging\Access\SecureConversationAccessControlHandler",
 *   },
 *   base_table = "secure_conversation",
 *   admin_permission = "administer jaraba messaging",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/conversations",
 *     "canonical" = "/admin/content/conversations/{secure_conversation}",
 *     "edit-form" = "/admin/content/conversations/{secure_conversation}/edit",
 *     "delete-form" = "/admin/content/conversations/{secure_conversation}/delete",
 *   },
 * )
 */
class SecureConversation extends ContentEntityBase implements SecureConversationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this conversation belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Conversation title.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Conversation Type'))
      ->setDescription(t('Type of conversation.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        SecureConversationInterface::TYPE_DIRECT => t('Direct'),
        SecureConversationInterface::TYPE_GROUP => t('Group'),
        SecureConversationInterface::TYPE_SUPPORT => t('Support'),
      ])
      ->setDefaultValue(SecureConversationInterface::TYPE_DIRECT)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['context_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Context Type'))
      ->setDescription(t('Business context for this conversation.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        SecureConversationInterface::CONTEXT_GENERAL => t('General'),
        SecureConversationInterface::CONTEXT_CASE => t('Legal Case'),
        SecureConversationInterface::CONTEXT_BOOKING => t('Booking'),
        SecureConversationInterface::CONTEXT_EMPLOYMENT => t('Employment'),
        SecureConversationInterface::CONTEXT_MENTORING => t('Mentoring'),
        SecureConversationInterface::CONTEXT_COMMERCE => t('Commerce'),
      ])
      ->setDefaultValue(SecureConversationInterface::CONTEXT_GENERAL);

    $fields['context_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context ID'))
      ->setDescription(t('ID of the related entity (case ID, booking ID, etc.).'))
      ->setSetting('max_length', 128);

    $fields['initiated_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Initiated By'))
      ->setDescription(t('User who created the conversation.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['encryption_key_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Encryption Key ID'))
      ->setDescription(t('Identifier of the encryption key used.'))
      ->setSetting('max_length', 64)
      ->setRequired(TRUE);

    $fields['is_confidential'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Confidential'))
      ->setDescription(t('Exclude from AI/RAG indexing.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_participants'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Participants'))
      ->setDescription(t('Maximum number of participants allowed.'))
      ->setDefaultValue(50)
      ->setSetting('min', 2)
      ->setSetting('max', 100);

    $fields['is_archived'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Archived'))
      ->setDescription(t('Whether the conversation is archived.'))
      ->setDefaultValue(FALSE);

    $fields['is_muted_by_system'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Muted by System'))
      ->setDescription(t('System-level mute for spam/abuse prevention.'))
      ->setDefaultValue(FALSE);

    $fields['last_message_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Message At'))
      ->setDescription(t('Timestamp of the most recent message.'));

    $fields['last_message_preview'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Message Preview'))
      ->setDescription(t('Preview text of the last message (truncated, decrypted).'))
      ->setSetting('max_length', 255);

    $fields['last_message_sender_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last Message Sender'))
      ->setDescription(t('User who sent the last message.'))
      ->setSetting('target_type', 'user');

    $fields['message_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Message Count'))
      ->setDescription(t('Total number of messages in the conversation.'))
      ->setDefaultValue(0);

    $fields['participant_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Participant Count'))
      ->setDescription(t('Number of active participants.'))
      ->setDefaultValue(0);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata'))
      ->setDescription(t('JSON metadata for extensibility.'));

    $fields['retention_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retention Days'))
      ->setDescription(t('Custom retention period override (0 = use default).'))
      ->setDefaultValue(0);

    $fields['auto_close_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Auto-Close Days'))
      ->setDescription(t('Custom auto-close period override (0 = use default).'))
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Conversation status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        SecureConversationInterface::STATUS_ACTIVE => t('Active'),
        SecureConversationInterface::STATUS_ARCHIVED => t('Archived'),
        SecureConversationInterface::STATUS_CLOSED => t('Closed'),
        SecureConversationInterface::STATUS_DELETED => t('Deleted'),
      ])
      ->setDefaultValue(SecureConversationInterface::STATUS_ACTIVE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp when the conversation was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last change.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $target_id = $this->get('tenant_id')->target_id;
    return $target_id ? (int) $target_id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversationType(): string {
    return $this->get('conversation_type')->value ?? self::TYPE_DIRECT;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? self::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitiatedBy(): ?int {
    $target_id = $this->get('initiated_by')->target_id;
    return $target_id ? (int) $target_id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfidential(): bool {
    return (bool) $this->get('is_confidential')->value;
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
  public function getLastMessageAt(): ?int {
    $value = $this->get('last_message_at')->value;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageCount(): int {
    return (int) $this->get('message_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipantCount(): int {
    return (int) $this->get('participant_count')->value;
  }

}
