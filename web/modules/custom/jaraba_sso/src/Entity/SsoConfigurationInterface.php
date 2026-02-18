<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for SSO Configuration entities.
 *
 * Defines the contract for SAML 2.0 / OIDC provider configuration
 * tied to a specific tenant in the multi-tenant platform.
 */
interface SsoConfigurationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int;

  /**
   * Gets the provider name.
   */
  public function getProviderName(): string;

  /**
   * Gets the provider type (saml or oidc).
   */
  public function getProviderType(): string;

  /**
   * Gets the SAML Entity ID or OIDC Client ID.
   */
  public function getEntityId(): string;

  /**
   * Gets the IdP SSO URL.
   */
  public function getSsoUrl(): string;

  /**
   * Gets the IdP SLO URL.
   */
  public function getSloUrl(): string;

  /**
   * Gets the X.509 certificate PEM.
   */
  public function getCertificate(): string;

  /**
   * Gets the OIDC client secret.
   */
  public function getClientSecret(): string;

  /**
   * Gets the OIDC token URL.
   */
  public function getTokenUrl(): string;

  /**
   * Gets the OIDC userinfo URL.
   */
  public function getUserinfoUrl(): string;

  /**
   * Gets the attribute mapping as an associative array.
   */
  public function getAttributeMapping(): array;

  /**
   * Gets the default role for new users.
   */
  public function getDefaultRole(): string;

  /**
   * Whether JIT provisioning is enabled.
   */
  public function isAutoProvision(): bool;

  /**
   * Whether SSO is forced for all tenant users.
   */
  public function isForceSso(): bool;

  /**
   * Whether the configuration is active.
   */
  public function isActive(): bool;

}
