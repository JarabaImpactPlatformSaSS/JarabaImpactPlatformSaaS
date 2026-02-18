<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\SsoConfigurationInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Just-In-Time (JIT) User Provisioner Service.
 *
 * Handles automatic user creation and update during SSO authentication.
 * When an IdP sends user attributes that don't match an existing Drupal user,
 * this service creates a new account with the appropriate role and tenant membership.
 *
 * FLOW:
 * 1. SSO handler extracts attributes from IdP response.
 * 2. findExistingUser() checks if user already exists by email or external ID.
 * 3. If not found and auto_provision is enabled, provisionUser() creates the account.
 * 4. If found, updateUser() syncs attributes from the IdP.
 *
 * MULTI-TENANCY:
 * New users are automatically associated with the tenant that owns
 * the SSO configuration via group membership.
 */
class JitProvisionerService {

  /**
   * Constructor with property promotion.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly UserAuthInterface $userAuth,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Creates a new Drupal user from IdP attributes.
   *
   * @param array $attributes
   *   User attributes from the IdP:
   *   - email: string (required)
   *   - first_name: string
   *   - last_name: string
   *   - name_id: string (SAML) or sub: string (OIDC)
   *   - groups: string[]
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration (provides default_role, attribute_mapping, tenant_id).
   *
   * @return \Drupal\user\UserInterface
   *   The newly created user.
   *
   * @throws \RuntimeException
   *   If email is missing or user creation fails.
   */
  public function provisionUser(array $attributes, SsoConfigurationInterface $config): UserInterface {
    $email = $attributes['email'] ?? '';
    if (empty($email)) {
      throw new \RuntimeException('Cannot provision user without email address.');
    }

    // Double-check no existing user.
    $existing = $this->findExistingUser($attributes);
    if ($existing) {
      return $this->updateUser($existing, $attributes, $config);
    }

    $storage = $this->entityTypeManager->getStorage('user');

    // Generate username from email or name.
    $username = $this->generateUsername($attributes);

    $user = $storage->create([
      'name' => $username,
      'mail' => $email,
      'status' => 1,
      'pass' => bin2hex(random_bytes(32)),
    ]);

    // Apply attribute mapping.
    $this->applyAttributes($user, $attributes, $config);

    // Assign default role.
    $defaultRole = $config->getDefaultRole();
    if (!empty($defaultRole) && $defaultRole !== 'authenticated') {
      $user->addRole($defaultRole);
    }

    // Assign IdP groups as roles if they exist.
    $groups = $attributes['groups'] ?? [];
    foreach ($groups as $group) {
      $roleId = $this->resolveGroupToRole($group);
      if ($roleId !== NULL) {
        $user->addRole($roleId);
      }
    }

    $user->save();

    // Assign tenant membership if group entity type exists.
    $this->assignTenantMembership($user, $config);

    $this->logger->info('JIT user provisioned: @email (uid: @uid) for tenant @tenant via @provider', [
      '@email' => $email,
      '@uid' => $user->id(),
      '@tenant' => $config->getTenantId(),
      '@provider' => $config->getProviderName(),
    ]);

    return $user;
  }

  /**
   * Updates an existing user with fresh IdP attributes.
   *
   * @param \Drupal\user\UserInterface $user
   *   The existing user to update.
   * @param array $attributes
   *   User attributes from the IdP.
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration.
   *
   * @return \Drupal\user\UserInterface
   *   The updated user.
   */
  public function updateUser(UserInterface $user, array $attributes, SsoConfigurationInterface $config): UserInterface {
    $this->applyAttributes($user, $attributes, $config);

    // Sync groups if present.
    $groups = $attributes['groups'] ?? [];
    if (!empty($groups)) {
      foreach ($groups as $group) {
        $roleId = $this->resolveGroupToRole($group);
        if ($roleId !== NULL && !in_array($roleId, $user->getRoles(TRUE), TRUE)) {
          $user->addRole($roleId);
        }
      }
    }

    $user->save();

    $this->logger->info('JIT user updated: @email (uid: @uid) via @provider', [
      '@email' => $user->getEmail(),
      '@uid' => $user->id(),
      '@provider' => $config->getProviderName(),
    ]);

    return $user;
  }

  /**
   * Finds an existing Drupal user by email or external ID.
   *
   * @param array $attributes
   *   User attributes from the IdP containing email, name_id, or sub.
   *
   * @return \Drupal\user\UserInterface|null
   *   The existing user, or NULL if not found.
   */
  public function findExistingUser(array $attributes): ?UserInterface {
    $storage = $this->entityTypeManager->getStorage('user');

    // Try by email first (most reliable).
    $email = $attributes['email'] ?? '';
    if (!empty($email)) {
      $users = $storage->loadByProperties(['mail' => $email]);
      if (!empty($users)) {
        return reset($users);
      }
    }

    // Try by username (SAML NameID or OIDC sub).
    $nameId = $attributes['name_id'] ?? ($attributes['sub'] ?? '');
    if (!empty($nameId)) {
      $users = $storage->loadByProperties(['name' => $nameId]);
      if (!empty($users)) {
        return reset($users);
      }
    }

    return NULL;
  }

  /**
   * Applies IdP attributes to a user entity using the attribute mapping.
   */
  protected function applyAttributes(UserInterface $user, array $attributes, SsoConfigurationInterface $config): void {
    $mapping = $config->getAttributeMapping();

    // Apply first name.
    $firstName = $attributes['first_name'] ?? '';
    if (!empty($firstName) && $user->hasField('field_first_name')) {
      $user->set('field_first_name', $firstName);
    }

    // Apply last name.
    $lastName = $attributes['last_name'] ?? '';
    if (!empty($lastName) && $user->hasField('field_last_name')) {
      $user->set('field_last_name', $lastName);
    }

    // Apply custom mapping fields.
    $rawAttributes = $attributes['raw_attributes'] ?? [];
    foreach ($mapping as $drupalField => $idpAttribute) {
      if (in_array($drupalField, ['email', 'first_name', 'last_name', 'groups'], TRUE)) {
        continue;
      }

      if (isset($rawAttributes[$idpAttribute]) && $user->hasField($drupalField)) {
        $user->set($drupalField, $rawAttributes[$idpAttribute]);
      }
    }
  }

  /**
   * Generates a unique username from attributes.
   */
  protected function generateUsername(array $attributes): string {
    $email = $attributes['email'] ?? '';
    $firstName = $attributes['first_name'] ?? '';
    $lastName = $attributes['last_name'] ?? '';

    // Try "first.last" format.
    if (!empty($firstName) && !empty($lastName)) {
      $base = strtolower($firstName . '.' . $lastName);
    }
    else {
      // Use email local part.
      $base = strstr($email, '@', TRUE) ?: ('sso_user_' . bin2hex(random_bytes(4)));
    }

    // Sanitize.
    $base = preg_replace('/[^a-z0-9._-]/', '', $base);

    // Ensure uniqueness.
    $username = $base;
    $suffix = 0;
    $storage = $this->entityTypeManager->getStorage('user');
    while (!empty($storage->loadByProperties(['name' => $username]))) {
      $suffix++;
      $username = $base . '.' . $suffix;
    }

    return $username;
  }

  /**
   * Resolves an IdP group name to a Drupal role ID.
   */
  protected function resolveGroupToRole(string $group): ?string {
    // Try exact match by role ID.
    $role = $this->entityTypeManager->getStorage('user_role')->load($group);
    if ($role) {
      return $group;
    }

    // Try by label (case-insensitive match).
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $roleEntity) {
      if (strtolower($roleEntity->label()) === strtolower($group)) {
        return $roleEntity->id();
      }
    }

    return NULL;
  }

  /**
   * Assigns tenant group membership to a user.
   */
  protected function assignTenantMembership(UserInterface $user, SsoConfigurationInterface $config): void {
    $tenantId = $config->getTenantId();
    if (empty($tenantId)) {
      return;
    }

    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $group = $groupStorage->load($tenantId);
      if ($group && method_exists($group, 'addMember')) {
        $group->addMember($user);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not assign tenant membership for uid @uid to tenant @tenant: @error', [
        '@uid' => $user->id(),
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
