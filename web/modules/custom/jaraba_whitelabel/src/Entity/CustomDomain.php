<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Custom domain entity for tenant whitelabel.
 *
 * Tracks custom domains mapped to tenants, including SSL provisioning
 * status and DNS verification state.
 *
 * @ContentEntityType(
 *   id = "custom_domain",
 *   label = @Translation("Custom Domain"),
 *   label_collection = @Translation("Custom Domains"),
 *   label_singular = @Translation("custom domain"),
 *   label_plural = @Translation("custom domains"),
 *   label_count = @PluralTranslation(
 *     singular = "@count custom domain",
 *     plural = "@count custom domains",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_whitelabel\ListBuilder\CustomDomainListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_whitelabel\Form\CustomDomainForm",
 *       "add" = "Drupal\jaraba_whitelabel\Form\CustomDomainForm",
 *       "edit" = "Drupal\jaraba_whitelabel\Form\CustomDomainForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_whitelabel\Access\CustomDomainAccessControlHandler",
 *   },
 *   base_table = "custom_domain",
 *   admin_permission = "administer whitelabel",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "domain",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/custom-domains",
 *     "add-form" = "/admin/content/custom-domains/add",
 *     "canonical" = "/admin/content/custom-domains/{custom_domain}",
 *     "edit-form" = "/admin/content/custom-domains/{custom_domain}/edit",
 *     "delete-form" = "/admin/content/custom-domains/{custom_domain}/delete",
 *   },
 *   field_ui_base_route = "entity.custom_domain.settings",
 * )
 */
class CustomDomain extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * SSL status values.
   */
  public const SSL_PENDING = 'pending';
  public const SSL_ACTIVE = 'active';
  public const SSL_FAILED = 'failed';

  /**
   * Domain status values.
   */
  public const DOMAIN_PENDING = 'pending';
  public const DOMAIN_ACTIVE = 'active';
  public const DOMAIN_SUSPENDED = 'suspended';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['domain'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Domain'))
      ->setDescription(t('Fully qualified domain name (e.g. app.example.com).'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->addConstraint('UniqueField')
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
      ->setDescription(t('The tenant (group) this domain belongs to.'))
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

    $fields['ssl_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('SSL Status'))
      ->setDescription(t('Current state of the SSL certificate.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::SSL_PENDING)
      ->setSettings([
        'allowed_values' => [
          self::SSL_PENDING => 'Pending',
          self::SSL_ACTIVE => 'Active',
          self::SSL_FAILED => 'Failed',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dns_verified'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('DNS Verified'))
      ->setDescription(t('Whether the DNS records have been verified.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dns_verification_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DNS Verification Token'))
      ->setDescription(t('Token that must be set as a TXT record for verification.'))
      ->setSettings(['max_length' => 128])
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

    $fields['provisioned_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Provisioned At'))
      ->setDescription(t('Timestamp when SSL was provisioned.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['domain_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Domain Status'))
      ->setDescription(t('Overall status of the custom domain.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::DOMAIN_PENDING)
      ->setSettings([
        'allowed_values' => [
          self::DOMAIN_PENDING => 'Pending',
          self::DOMAIN_ACTIVE => 'Active',
          self::DOMAIN_SUSPENDED => 'Suspended',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
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
