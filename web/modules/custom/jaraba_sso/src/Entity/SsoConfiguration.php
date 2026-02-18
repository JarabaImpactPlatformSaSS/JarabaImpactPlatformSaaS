<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * SSO CONFIGURATION ENTITY — SsoConfiguration
 *
 * PURPOSE:
 * Stores SAML 2.0 / OIDC identity provider configurations per tenant.
 * Each tenant can have multiple SSO providers (e.g., Azure AD, Okta, Google Workspace).
 *
 * MULTI-TENANCY:
 * Field tenant_id is mandatory. Full isolation per tenant.
 *
 * SECURITY:
 * Certificate and client_secret fields store sensitive data.
 * Access controlled via 'manage sso providers' permission.
 *
 * @ContentEntityType(
 *   id = "sso_configuration",
 *   label = @Translation("SSO Configuration"),
 *   label_collection = @Translation("SSO Configurations"),
 *   label_singular = @Translation("SSO configuration"),
 *   label_plural = @Translation("SSO configurations"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "sso_configuration",
 *   admin_permission = "administer sso",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "provider_name",
 *   },
 * )
 */
class SsoConfiguration extends ContentEntityBase implements SsoConfigurationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant reference (REQUIRED).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant that owns this SSO configuration.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setCardinality(1);

    // Provider name (e.g., "Azure AD", "Okta", "Google Workspace").
    $fields['provider_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider Name'))
      ->setDescription(t('Human-readable name of the identity provider.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    // Provider type: saml or oidc.
    $fields['provider_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Provider Type'))
      ->setDescription(t('SSO protocol type.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'saml' => 'SAML 2.0',
          'oidc' => 'OpenID Connect',
        ],
      ]);

    // SAML Entity ID / OIDC Client ID.
    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity ID / Client ID'))
      ->setDescription(t('SAML Entity ID or OIDC Client ID.'))
      ->setSetting('max_length', 500);

    // IdP SSO URL.
    $fields['sso_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SSO URL'))
      ->setDescription(t('Identity Provider Single Sign-On URL.'))
      ->setSetting('max_length', 500);

    // IdP SLO URL.
    $fields['slo_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SLO URL'))
      ->setDescription(t('Identity Provider Single Logout URL.'))
      ->setSetting('max_length', 500);

    // X.509 certificate PEM.
    $fields['certificate'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('X.509 Certificate'))
      ->setDescription(t('IdP X.509 certificate in PEM format for SAML signature validation.'));

    // OIDC client secret.
    $fields['client_secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client Secret'))
      ->setDescription(t('OIDC client secret (encrypted at rest).'))
      ->setSetting('max_length', 500);

    // OIDC token URL.
    $fields['token_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token URL'))
      ->setDescription(t('OIDC token endpoint URL.'))
      ->setSetting('max_length', 500);

    // OIDC userinfo URL.
    $fields['userinfo_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Userinfo URL'))
      ->setDescription(t('OIDC userinfo endpoint URL.'))
      ->setSetting('max_length', 500);

    // Attribute mapping (JSON).
    $fields['attribute_mapping'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Attribute Mapping'))
      ->setDescription(t('JSON mapping from IdP attributes to Drupal fields.'));

    // Default role for new users.
    $fields['default_role'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default Role'))
      ->setDescription(t('Drupal role assigned to new JIT-provisioned users.'))
      ->setSetting('max_length', 128)
      ->setDefaultValue('authenticated');

    // JIT auto-provisioning.
    $fields['auto_provision'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Auto Provision'))
      ->setDescription(t('Enable Just-In-Time user provisioning.'))
      ->setDefaultValue(TRUE);

    // Force SSO for all tenant users.
    $fields['force_sso'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Force SSO'))
      ->setDescription(t('Force SSO authentication for all tenant users.'))
      ->setDefaultValue(FALSE);

    // Active flag.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether this SSO configuration is active.'))
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
  public function getProviderName(): string {
    return $this->get('provider_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderType(): string {
    return $this->get('provider_type')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId(): string {
    return $this->get('entity_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSsoUrl(): string {
    return $this->get('sso_url')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSloUrl(): string {
    return $this->get('slo_url')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificate(): string {
    return $this->get('certificate')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret(): string {
    return $this->get('client_secret')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenUrl(): string {
    return $this->get('token_url')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUserinfoUrl(): string {
    return $this->get('userinfo_url')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeMapping(): array {
    $json = $this->get('attribute_mapping')->value ?? '{}';
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRole(): string {
    return $this->get('default_role')->value ?? 'authenticated';
  }

  /**
   * {@inheritdoc}
   */
  public function isAutoProvision(): bool {
    return (bool) $this->get('auto_provision')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isForceSso(): bool {
    return (bool) $this->get('force_sso')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

}
