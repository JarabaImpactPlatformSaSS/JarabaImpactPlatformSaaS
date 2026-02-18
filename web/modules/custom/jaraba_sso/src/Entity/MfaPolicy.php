<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * MFA POLICY ENTITY — MfaPolicy
 *
 * PURPOSE:
 * Stores per-tenant Multi-Factor Authentication policies.
 * Controls enforcement level, allowed methods, grace periods,
 * and session constraints.
 *
 * MULTI-TENANCY:
 * Field tenant_id is mandatory. Each tenant has at most one active policy.
 *
 * ENFORCEMENT LEVELS:
 * - disabled: MFA is not required.
 * - admins_only: MFA required for admin roles only.
 * - required: MFA required for all tenant users.
 *
 * @ContentEntityType(
 *   id = "mfa_policy",
 *   label = @Translation("MFA Policy"),
 *   label_collection = @Translation("MFA Policies"),
 *   label_singular = @Translation("MFA policy"),
 *   label_plural = @Translation("MFA policies"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "mfa_policy",
 *   admin_permission = "manage mfa policies",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "enforcement",
 *   },
 * )
 */
class MfaPolicy extends ContentEntityBase implements MfaPolicyInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant reference (REQUIRED).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this MFA policy applies to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setCardinality(1);

    // Enforcement level.
    $fields['enforcement'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Enforcement'))
      ->setDescription(t('MFA enforcement level.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'disabled' => 'Disabled',
          'admins_only' => 'Admins Only',
          'required' => 'Required for All',
        ],
      ])
      ->setDefaultValue('disabled');

    // Allowed MFA methods (JSON array).
    $fields['allowed_methods'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Allowed Methods'))
      ->setDescription(t('JSON array of allowed MFA methods: totp, webauthn, sms.'));

    // Grace period in days.
    $fields['grace_period_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Grace Period (days)'))
      ->setDescription(t('Number of days before MFA enforcement kicks in.'))
      ->setDefaultValue(7);

    // Session duration in hours.
    $fields['session_duration_hours'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Session Duration (hours)'))
      ->setDescription(t('How long an MFA-authenticated session remains valid.'))
      ->setDefaultValue(8);

    // Max concurrent sessions.
    $fields['max_concurrent_sessions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Concurrent Sessions'))
      ->setDescription(t('Maximum number of concurrent authenticated sessions.'))
      ->setDefaultValue(3);

    // Active flag.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether this MFA policy is active.'))
      ->setDefaultValue(TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnforcement(): string {
    return $this->get('enforcement')->value ?? 'disabled';
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedMethods(): array {
    $json = $this->get('allowed_methods')->value ?? '[]';
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getGracePeriodDays(): int {
    return (int) ($this->get('grace_period_days')->value ?? 7);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionDurationHours(): int {
    return (int) ($this->get('session_duration_hours')->value ?? 8);
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxConcurrentSessions(): int {
    return (int) ($this->get('max_concurrent_sessions')->value ?? 3);
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

}
