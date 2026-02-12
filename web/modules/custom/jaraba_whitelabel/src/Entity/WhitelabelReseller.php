<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Reseller / partner entity (migrated from ecosistema_jaraba_core).
 *
 * Represents a partner/reseller that manages one or more tenants on the
 * Jaraba SaaS platform. Each reseller has its own commercial configuration
 * (commissions, territory, revenue-share model) and access to a dedicated
 * partner portal.
 *
 * @ContentEntityType(
 *   id = "whitelabel_reseller",
 *   label = @Translation("Reseller"),
 *   label_collection = @Translation("Resellers"),
 *   label_singular = @Translation("reseller"),
 *   label_plural = @Translation("resellers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count reseller",
 *     plural = "@count resellers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_whitelabel\ListBuilder\WhitelabelResellerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_whitelabel\Form\WhitelabelResellerForm",
 *       "add" = "Drupal\jaraba_whitelabel\Form\WhitelabelResellerForm",
 *       "edit" = "Drupal\jaraba_whitelabel\Form\WhitelabelResellerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_whitelabel\Access\WhitelabelResellerAccessControlHandler",
 *   },
 *   base_table = "whitelabel_reseller",
 *   admin_permission = "administer whitelabel",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/resellers",
 *     "add-form" = "/admin/content/resellers/add",
 *     "canonical" = "/admin/content/resellers/{whitelabel_reseller}",
 *     "edit-form" = "/admin/content/resellers/{whitelabel_reseller}/edit",
 *     "delete-form" = "/admin/content/resellers/{whitelabel_reseller}/delete",
 *   },
 * )
 */
class WhitelabelReseller extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Reseller status values.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_SUSPENDED = 'suspended';
  public const STATUS_PENDING = 'pending';

  /**
   * Revenue share model values.
   */
  public const REVENUE_PERCENTAGE = 'percentage';
  public const REVENUE_FLAT_FEE = 'flat_fee';
  public const REVENUE_TIERED = 'tiered';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Identifying name of the reseller or partner.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
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

    $fields['company_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Company Name'))
      ->setDescription(t('Legal or trading name of the reseller company.'))
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

    $fields['contact_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact Email'))
      ->setDescription(t('Primary email used to identify the user in the partner portal.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['commission_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Commission Rate (%)'))
      ->setDescription(t('Percentage commission on managed tenant revenue (e.g. 15.00 = 15%).'))
      ->setSettings([
        'precision' => 5,
        'scale' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['territory'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Territory'))
      ->setDescription(t('JSON with territory configuration (regions, sectors, exclusivity).'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reseller_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Current reseller status on the platform.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_ACTIVE => 'Active',
          self::STATUS_SUSPENDED => 'Suspended',
          self::STATUS_PENDING => 'Pending',
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

    $fields['managed_tenant_ids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Managed Tenants'))
      ->setDescription(t('Tenants (groups) managed by this reseller.'))
      ->setSetting('target_type', 'group')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revenue_share_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Revenue Share Model'))
      ->setDescription(t('Model used to calculate reseller commissions.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::REVENUE_PERCENTAGE)
      ->setSettings([
        'allowed_values' => [
          self::REVENUE_PERCENTAGE => 'Percentage',
          self::REVENUE_FLAT_FEE => 'Flat fee',
          self::REVENUE_TIERED => 'Tiered',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contract_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Contract Start'))
      ->setDescription(t('Start date of the reseller contract.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contract_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Contract End'))
      ->setDescription(t('End date of the reseller contract.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -1,
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

  /**
   * Returns decoded territory configuration.
   *
   * @return array
   *   Decoded territory array, empty if none or invalid.
   */
  public function getDecodedTerritory(): array {
    $raw = $this->get('territory')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
