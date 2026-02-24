<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the SLA Agreement entity.
 *
 * STRUCTURE:
 * Core entity representing an SLA contract between the platform and a tenant.
 * Each agreement defines uptime targets, credit policies, and custom terms.
 *
 * LOGIC:
 * Agreements are scoped per-tenant with a tier (standard/premium/critical).
 * The credit_policy field stores a JSON array of threshold/credit_pct pairs
 * used by SlaCreditEngineService to calculate automatic credits.
 *
 * RELATIONS:
 * - SlaAgreement -> Group (tenant_id): owning tenant.
 * - SlaAgreement <- SlaMeasurement (agreement_id): monthly measurements.
 *
 * @ContentEntityType(
 *   id = "sla_agreement",
 *   label = @Translation("SLA Agreement"),
 *   label_collection = @Translation("SLA Agreements"),
 *   label_singular = @Translation("SLA agreement"),
 *   label_plural = @Translation("SLA agreements"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_sla\ListBuilder\SlaAgreementListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_sla\Access\SlaAgreementAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sla_agreement",
 *   admin_permission = "administer sla",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "sla_tier",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sla-agreements",
 *     "canonical" = "/admin/content/sla-agreements/{sla_agreement}",
 *     "delete-form" = "/admin/content/sla-agreements/{sla_agreement}/delete",
 *   },
 *   field_ui_base_route = "entity.sla_agreement.settings",
 * )
 */
class SlaAgreement extends ContentEntityBase implements SlaAgreementInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getSlaTier(): string {
    return (string) ($this->get('sla_tier')->value ?? 'standard');
  }

  /**
   * {@inheritdoc}
   */
  public function getUptimeTarget(): float {
    return (float) ($this->get('uptime_target')->value ?? 99.9);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditPolicy(): array {
    $raw = $this->get('credit_policy')->value ?? '[]';
    return json_decode($raw, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) ($this->get('is_active')->value ?? TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $tid = $this->get('tenant_id')->target_id;
    return $tid !== NULL ? (int) $tid : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOCK 1: MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant that owns this SLA agreement.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 2: SLA DEFINITION
    // =========================================================================

    $fields['sla_tier'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('SLA Tier'))
      ->setDescription(new TranslatableMarkup('The SLA service tier.'))
      ->setRequired(TRUE)
      ->setDefaultValue('standard')
      ->setSetting('allowed_values', [
        'standard' => new TranslatableMarkup('Standard'),
        'premium' => new TranslatableMarkup('Premium'),
        'critical' => new TranslatableMarkup('Critical'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uptime_target'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Uptime Target (%)'))
      ->setDescription(new TranslatableMarkup('Target uptime percentage, e.g. 99.900.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 3)
      ->setDefaultValue('99.900')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['credit_policy'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Credit Policy'))
      ->setDescription(new TranslatableMarkup('JSON array of credit policy thresholds.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['custom_terms'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Custom Terms'))
      ->setDescription(new TranslatableMarkup('JSON object with custom SLA terms.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 3: DATES
    // =========================================================================

    $fields['effective_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Effective Date'))
      ->setDescription(new TranslatableMarkup('When the SLA agreement becomes effective.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expiry_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Expiry Date'))
      ->setDescription(new TranslatableMarkup('When the SLA agreement expires (NULL = indefinite).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 4: STATUS
    // =========================================================================

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Active'))
      ->setDescription(new TranslatableMarkup('Whether this SLA agreement is currently active.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 5: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    return $fields;
  }

}
