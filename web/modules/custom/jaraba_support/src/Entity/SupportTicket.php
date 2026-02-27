<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Support Ticket entity.
 *
 * Core entity of the jaraba_support module. Represents a support case
 * opened by a tenant user with full lifecycle management, AI classification,
 * SLA tracking, and multi-channel support.
 *
 * SPEC: 178_Platform_Tenant_Support_Tickets_v1_Claude — Section 3.1
 *
 * @ContentEntityType(
 *   id = "support_ticket",
 *   label = @Translation("Support Ticket"),
 *   label_collection = @Translation("Support Tickets"),
 *   label_singular = @Translation("support ticket"),
 *   label_plural = @Translation("support tickets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count support ticket",
 *     plural = "@count support tickets",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_support\SupportTicketListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *       "add" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *       "edit" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "support_ticket",
 *   data_table = "support_ticket_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "langcode" = "langcode",
 *     "owner" = "reporter_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/support-tickets/{support_ticket}",
 *     "add-form" = "/admin/content/support-tickets/add",
 *     "edit-form" = "/admin/content/support-tickets/{support_ticket}/edit",
 *     "delete-form" = "/admin/content/support-tickets/{support_ticket}/delete",
 *     "collection" = "/admin/content/support-tickets",
 *   },
 *   field_ui_base_route = "entity.support_ticket.settings",
 * )
 */
class SupportTicket extends ContentEntityBase implements SupportTicketInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getTicketNumber(): string {
    return $this->get('ticket_number')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'new';
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): string {
    return $this->get('priority')->value ?? 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getAiClassification(): array {
    $value = $this->get('ai_classification')->value;
    return $value ? (json_decode($value, TRUE) ?: []) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(): array {
    $value = $this->get('tags')->value;
    return $value ? (json_decode($value, TRUE) ?: []) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function isResolved(): bool {
    return in_array($this->getStatus(), ['resolved', 'closed'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isSlaBreached(): bool {
    return (bool) $this->get('sla_breached')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Generate ticket_number for new tickets.
    if ($this->isNew() && empty($this->get('ticket_number')->value)) {
      $this->set('ticket_number', $this->generateTicketNumber());
    }

    // Update child_count from parent if applicable.
    if (!$this->isNew() && $this->get('parent_ticket_id')->target_id) {
      $parent = $this->get('parent_ticket_id')->entity;
      if ($parent) {
        $count = (int) \Drupal::entityTypeManager()
          ->getStorage('support_ticket')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('parent_ticket_id', $parent->id())
          ->count()
          ->execute();
        $parent->set('child_count', $count);
      }
    }
  }

  /**
   * Generates a unique, human-readable ticket number.
   *
   * Format: JRB-YYYYMM-NNNN (e.g., JRB-202602-0001).
   */
  private function generateTicketNumber(): string {
    $prefix = 'JRB';
    $month = date('Ym');
    $pattern = $prefix . '-' . $month . '-%';

    $count = (int) \Drupal::entityTypeManager()
      ->getStorage('support_ticket')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('ticket_number', $pattern, 'LIKE')
      ->count()
      ->execute();

    return sprintf('%s-%s-%04d', $prefix, $month, $count + 1);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['ticket_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ticket Number'))
      ->setDescription(t('Human-readable ticket number (JRB-YYYYMM-NNNN).'))
      ->setSettings(['max_length' => 20])
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // TENANT-BRIDGE-001: entity_reference to group, never integer.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) that owns this ticket.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reporter_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reporter'))
      ->setDescription(t('The user who opened the ticket.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assignee_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Assignee'))
      ->setDescription(t('The support agent assigned to this ticket.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('The business vertical this ticket belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'empleabilidad' => t('Empleabilidad'),
        'emprendimiento' => t('Emprendimiento'),
        'agro' => t('AgroConecta'),
        'comercio' => t('ComercioConecta'),
        'servicios' => t('ServiciosConecta'),
        'platform' => t('Platform'),
        'billing' => t('Billing'),
        'formacion' => t('Formación'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Category'))
      ->setDescription(t('Primary category of the ticket.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subcategory'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subcategory'))
      ->setDescription(t('Specific subcategory.'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('Brief description of the issue.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Detailed description of the problem.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Current status of the ticket.'))
      ->setRequired(TRUE)
      ->setDefaultValue('new')
      ->setSetting('allowed_values', [
        'new' => t('New'),
        'ai_handling' => t('AI Handling'),
        'open' => t('Open'),
        'pending_customer' => t('Pending Customer'),
        'pending_internal' => t('Pending Internal'),
        'escalated' => t('Escalated'),
        'resolved' => t('Resolved'),
        'closed' => t('Closed'),
        'reopened' => t('Reopened'),
        'merged' => t('Merged'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Priority'))
      ->setDescription(t('Priority level of the ticket.'))
      ->setRequired(TRUE)
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'critical' => t('Critical'),
        'high' => t('High'),
        'medium' => t('Medium'),
        'low' => t('Low'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setDescription(t('Technical severity of the issue.'))
      ->setSetting('allowed_values', [
        'blocker' => t('Blocker'),
        'degraded' => t('Degraded'),
        'minor' => t('Minor'),
        'cosmetic' => t('Cosmetic'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['channel'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Channel'))
      ->setDescription(t('Channel through which the ticket was created.'))
      ->setRequired(TRUE)
      ->setDefaultValue('portal')
      ->setSetting('allowed_values', [
        'portal' => t('Portal'),
        'email' => t('Email'),
        'chat' => t('Chat'),
        'whatsapp' => t('WhatsApp'),
        'phone' => t('Phone'),
        'api' => t('API'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- AI Fields ---

    $fields['ai_classification'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('AI Classification'))
      ->setDescription(t('JSON: {category, confidence, sentiment, urgency, vertical}.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_resolution_attempted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('AI Resolution Attempted'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_resolution_accepted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('AI Resolution Accepted'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_suggested_solution'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('AI Suggested Solution'))
      ->setDescription(t('Solution proposed by the AI engine.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- SLA Fields ---

    $fields['sla_policy_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SLA Policy'))
      ->setDescription(t('ID of the SLA policy applied (ConfigEntity ID).'))
      ->setSettings(['max_length' => 128])
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_first_response_due'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('SLA First Response Due'))
      ->setDescription(t('Deadline for first response.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_resolution_due'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('SLA Resolution Due'))
      ->setDescription(t('Deadline for resolution.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_breached'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('SLA Breached'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_paused_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('SLA Paused At'))
      ->setDescription(t('Timestamp when the SLA was paused (pending_customer).'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_paused_duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('SLA Paused Duration'))
      ->setDescription(t('Accumulated pause time in minutes.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['first_responded_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('First Responded At'))
      ->setDescription(t('Timestamp of first response (human or AI).'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    // --- Satisfaction Fields ---

    $fields['satisfaction_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('CSAT Rating'))
      ->setDescription(t('Customer satisfaction rating (1-5).'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayConfigurable('view', TRUE);

    $fields['satisfaction_comment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('CSAT Comment'))
      ->setDescription(t('Free-text feedback from the customer.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['effort_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Customer Effort Score'))
      ->setDescription(t('CES rating (1-5). GAP-SUP-10.'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayConfigurable('view', TRUE);

    $fields['satisfaction_submitted_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('CSAT Submitted At'))
      ->setDescription(t('Timestamp when the customer submitted their satisfaction response.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['csat_survey_scheduled'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('CSAT Survey Scheduled'))
      ->setDescription(t('Timestamp when the CSAT survey is scheduled to be sent. 0 = not scheduled.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Resolution Fields ---

    $fields['resolution_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resolution Notes'))
      ->setDescription(t('Notes from the agent about how the issue was resolved.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 20,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadata Fields ---

    $fields['tags'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tags'))
      ->setDescription(t('JSON array of tags for organization.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['related_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Related Entity Type'))
      ->setDescription(t('Type of the related entity (order, product, booking, course).'))
      ->setSettings(['max_length' => 32])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['related_entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Related Entity ID'))
      ->setDescription(t('ID of the related entity.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Merge / Parent-Child Fields (GAP-SUP-01, GAP-SUP-05) ---

    $fields['merged_into_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Merged Into'))
      ->setDescription(t('The canonical ticket this one was merged into.'))
      ->setSetting('target_type', 'support_ticket')
      ->setDisplayConfigurable('view', TRUE);

    $fields['merged_from_ids'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Merged From'))
      ->setDescription(t('JSON array of ticket IDs merged into this one.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent_ticket_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent Ticket'))
      ->setDescription(t('Parent ticket for sub-tasks.'))
      ->setSetting('target_type', 'support_ticket')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 32,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['child_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Child Tickets'))
      ->setDescription(t('Number of child tickets.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Watcher Fields (GAP-SUP-06) ---

    $fields['cc_uids'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CC Users'))
      ->setDescription(t('JSON array of user IDs receiving notifications.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamps ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the ticket was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the ticket was last updated.'))
      ->setTranslatable(TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Resolved At'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['closed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Closed At'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
