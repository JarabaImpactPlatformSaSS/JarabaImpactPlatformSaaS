<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Whitelabel configuration entity per tenant.
 *
 * Stores branding settings (logo, colours, custom CSS, footer HTML)
 * that are applied when the tenant is accessed via its custom domain
 * or from the tenant context.
 *
 * @ContentEntityType(
 *   id = "whitelabel_config",
 *   label = @Translation("Whitelabel Config"),
 *   label_collection = @Translation("Whitelabel Configs"),
 *   label_singular = @Translation("whitelabel config"),
 *   label_plural = @Translation("whitelabel configs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count whitelabel config",
 *     plural = "@count whitelabel configs",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_whitelabel\ListBuilder\WhitelabelConfigListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_whitelabel\Form\WhitelabelConfigForm",
 *       "add" = "Drupal\jaraba_whitelabel\Form\WhitelabelConfigForm",
 *       "edit" = "Drupal\jaraba_whitelabel\Form\WhitelabelConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_whitelabel\Access\WhitelabelConfigAccessControlHandler",
 *   },
 *   base_table = "whitelabel_config",
 *   admin_permission = "administer whitelabel",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "config_key",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/whitelabel-configs",
 *     "add-form" = "/admin/content/whitelabel-configs/add",
 *     "canonical" = "/admin/content/whitelabel-configs/{whitelabel_config}",
 *     "edit-form" = "/admin/content/whitelabel-configs/{whitelabel_config}/edit",
 *     "delete-form" = "/admin/content/whitelabel-configs/{whitelabel_config}/delete",
 *   },
 * )
 */
class WhitelabelConfig extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Config status values.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['config_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config Key'))
      ->setDescription(t('Unique key identifying this whitelabel configuration.'))
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

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this configuration belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['logo_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Logo URL'))
      ->setDescription(t('URL of the tenant logo image.'))
      ->setSettings(['max_length' => 2048])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['favicon_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Favicon URL'))
      ->setDescription(t('URL of the tenant favicon.'))
      ->setSettings(['max_length' => 2048])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['company_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Company Name'))
      ->setDescription(t('Name displayed in the whitelabel interface.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['primary_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Primary Colour'))
      ->setDescription(t('Hex colour code for primary brand colour (e.g. #FF8C42).'))
      ->setSettings(['max_length' => 7])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['secondary_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secondary Colour'))
      ->setDescription(t('Hex colour code for secondary brand colour.'))
      ->setSettings(['max_length' => 7])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['custom_css'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Custom CSS'))
      ->setDescription(t('Custom CSS rules applied to the whitelabel interface.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
        'settings' => ['rows' => 8],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['custom_footer_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Custom Footer HTML'))
      ->setDescription(t('Custom HTML injected in the footer area.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hide_powered_by'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Hide Powered By'))
      ->setDescription(t('Whether to hide the "Powered by Jaraba" branding.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['config_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether this configuration is active.'))
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
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
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
