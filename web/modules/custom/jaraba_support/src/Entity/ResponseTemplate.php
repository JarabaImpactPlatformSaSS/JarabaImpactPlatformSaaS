<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Response Template entity.
 *
 * Reusable message templates with variable placeholders for agents.
 * Closes GAP-SUP-04.
 *
 * @ContentEntityType(
 *   id = "response_template",
 *   label = @Translation("Response Template"),
 *   label_collection = @Translation("Response Templates"),
 *   label_singular = @Translation("response template"),
 *   label_plural = @Translation("response templates"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_support\Form\ResponseTemplateForm",
 *       "add" = "Drupal\jaraba_support\Form\ResponseTemplateForm",
 *       "edit" = "Drupal\jaraba_support\Form\ResponseTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "response_template",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "author_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/response-templates/{response_template}",
 *     "add-form" = "/admin/content/response-templates/add",
 *     "edit-form" = "/admin/content/response-templates/{response_template}/edit",
 *     "delete-form" = "/admin/content/response-templates/{response_template}/delete",
 *     "collection" = "/admin/content/response-templates",
 *   },
 * )
 */
class ResponseTemplate extends ContentEntityBase implements EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Template Body'))
      ->setDescription(t('Use placeholders: {{customer_name}}, {{ticket_number}}, {{agent_name}}, {{tenant_name}}.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Category'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Specific vertical or empty for global templates.'))
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
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['scope'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Scope'))
      ->setRequired(TRUE)
      ->setDefaultValue('personal')
      ->setSetting('allowed_values', [
        'global' => t('Global'),
        'team' => t('Team'),
        'personal' => t('Personal'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['usage_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage Count'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
