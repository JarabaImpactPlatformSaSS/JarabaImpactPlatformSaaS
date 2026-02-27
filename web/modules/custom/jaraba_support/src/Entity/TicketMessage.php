<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Ticket Message entity.
 *
 * Messages in the conversation thread of a ticket, including human
 * and AI-generated messages, internal notes, and system messages.
 *
 * SPEC: 178 — Section 3.2
 *
 * @ContentEntityType(
 *   id = "ticket_message",
 *   label = @Translation("Ticket Message"),
 *   label_collection = @Translation("Ticket Messages"),
 *   label_singular = @Translation("ticket message"),
 *   label_plural = @Translation("ticket messages"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_support\Form\TicketMessageForm",
 *       "add" = "Drupal\jaraba_support\Form\TicketMessageForm",
 *       "edit" = "Drupal\jaraba_support\Form\TicketMessageForm",
 *     },
 *   },
 *   base_table = "ticket_message",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class TicketMessage extends ContentEntityBase implements TicketMessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getTicket(): ?SupportTicketInterface {
    $ticket = $this->get('ticket_id')->entity;
    return $ticket instanceof SupportTicketInterface ? $ticket : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorType(): string {
    return $this->get('author_type')->value ?? 'system';
  }

  /**
   * {@inheritdoc}
   */
  public function isInternalNote(): bool {
    return (bool) $this->get('is_internal_note')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isAiGenerated(): bool {
    return (bool) $this->get('is_ai_generated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Generate plain text for search indexing.
    $body = $this->get('body')->value ?? '';
    if ($body && empty($this->get('body_plain')->value)) {
      $this->set('body_plain', strip_tags($body));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['ticket_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ticket'))
      ->setDescription(t('The parent support ticket.'))
      ->setSetting('target_type', 'support_ticket')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['author_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user who wrote the message. NULL for system/AI.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['author_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Author Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'customer' => t('Customer'),
        'agent' => t('Agent'),
        'ai' => t('AI'),
        'system' => t('System'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message Body'))
      ->setDescription(t('Content of the message (Markdown supported).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body_html'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Body HTML'))
      ->setDescription(t('Server-rendered HTML (sanitized).'));

    $fields['body_plain'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Body Plain'))
      ->setDescription(t('Plain text for search indexing.'));

    $fields['is_internal_note'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Internal Note'))
      ->setDescription(t('If TRUE, not visible to the tenant.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_ai_generated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('AI Generated'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_confidence'] = BaseFieldDefinition::create('float')
      ->setLabel(t('AI Confidence'))
      ->setDescription(t('Confidence score 0.00-1.00.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_sources'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('AI Sources'))
      ->setDescription(t('JSON array of KB sources used for the response.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['edited_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Edited At'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
