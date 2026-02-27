<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Ticket Attachment entity.
 *
 * Files attached to tickets: documents, screenshots, videos.
 * Includes virus scanning status (GAP-SUP-08) and private storage path.
 *
 * SPEC: 178 — Section 3.3
 *
 * @ContentEntityType(
 *   id = "ticket_attachment",
 *   label = @Translation("Ticket Attachment"),
 *   label_collection = @Translation("Ticket Attachments"),
 *   label_singular = @Translation("ticket attachment"),
 *   label_plural = @Translation("ticket attachments"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *   },
 *   base_table = "ticket_attachment",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "filename",
 *   },
 * )
 */
class TicketAttachment extends ContentEntityBase {

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

    $fields['message_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Message'))
      ->setDescription(t('The message this attachment belongs to (optional).'))
      ->setSetting('target_type', 'ticket_message')
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File'))
      ->setSetting('target_type', 'file')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filename'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename'))
      ->setDescription(t('Original file name.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    $fields['mime_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MIME Type'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('File Size'))
      ->setDescription(t('Size in bytes.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['storage_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Storage Path'))
      ->setDescription(t('Private file path: private://support_attachments/{tenant_id}/{ticket_id}/...'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 500])
      ->setDisplayConfigurable('view', TRUE);

    // GAP-SUP-08: Virus scanning.
    $fields['scan_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Scan Status'))
      ->setDescription(t('Virus scan status.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'clean' => t('Clean'),
        'infected' => t('Infected'),
        'error' => t('Error'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_analysis'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('AI Analysis'))
      ->setDescription(t('JSON result of AI analysis (OCR, classification).'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_screenshot'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Screenshot'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uploaded_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Uploaded By'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
