<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Branded email template entity per tenant.
 *
 * Stores customisable email templates (welcome, invoice, password_reset, etc.)
 * that are rendered with tenant branding when sending transactional emails.
 *
 * Named WhitelabelEmailTemplate to avoid generic naming collision with
 * other modules that may define an EmailTemplate entity.
 *
 * @ContentEntityType(
 *   id = "whitelabel_email_template",
 *   label = @Translation("Email Template"),
 *   label_collection = @Translation("Email Templates"),
 *   label_singular = @Translation("email template"),
 *   label_plural = @Translation("email templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count email template",
 *     plural = "@count email templates",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_whitelabel\ListBuilder\WhitelabelEmailTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_whitelabel\Form\WhitelabelEmailTemplateForm",
 *       "add" = "Drupal\jaraba_whitelabel\Form\WhitelabelEmailTemplateForm",
 *       "edit" = "Drupal\jaraba_whitelabel\Form\WhitelabelEmailTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_whitelabel\Access\WhitelabelEmailTemplateAccessControlHandler",
 *   },
 *   base_table = "whitelabel_email_template",
 *   admin_permission = "administer whitelabel",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "template_key",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/email-templates",
 *     "add-form" = "/admin/content/email-templates/add",
 *     "canonical" = "/admin/content/email-templates/{whitelabel_email_template}",
 *     "edit-form" = "/admin/content/email-templates/{whitelabel_email_template}/edit",
 *     "delete-form" = "/admin/content/email-templates/{whitelabel_email_template}/delete",
 *   },
 *   field_ui_base_route = "entity.whitelabel_email_template.settings",
 * )
 */
class WhitelabelEmailTemplate extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Template status values.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['template_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template Key'))
      ->setDescription(t('Machine name of the template (e.g. welcome, invoice, password_reset).'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('Email subject line. Supports token replacement.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('HTML Body'))
      ->setDescription(t('HTML version of the email body.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
        'settings' => ['rows' => 12],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Plain Text Body'))
      ->setDescription(t('Plain text version of the email body.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
        'settings' => ['rows' => 8],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this template belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['template_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether this template is active.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_ACTIVE => 'Active',
          self::STATUS_INACTIVE => 'Inactive',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the entity was last edited.'));

    return $fields;
  }

}
