<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\MfaPolicy;
use Drupal\jaraba_sso\Entity\MfaPolicyInterface;
use Drupal\user\UserInterface;

/**
 * MFA Enforcer Service.
 *
 * Manages per-tenant MFA policies and determines whether MFA is required
 * for a given user based on their tenant, role, and the active policy.
 *
 * ENFORCEMENT LEVELS:
 * - disabled: No MFA required for any user.
 * - admins_only: MFA required for users with 'administrator' or 'tenant_admin' roles.
 * - required: MFA required for all users in the tenant.
 *
 * GRACE PERIOD:
 * When MFA enforcement is first enabled, users have a configurable grace period
 * (default 7 days) before they are locked out without MFA setup.
 */
class MfaEnforcerService {

  /**
   * Admin roles that require MFA under 'admins_only' enforcement.
   */
  protected const ADMIN_ROLES = [
    'administrator',
    'tenant_admin',
    'platform_admin',
  ];

  /**
   * Constructor with property promotion.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Gets the active MFA policy for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_sso\Entity\MfaPolicyInterface|null
   *   The active MFA policy, or NULL if none exists.
   */
  public function getPolicy(int $tenantId): ?MfaPolicyInterface {
    $storage = $this->entityTypeManager->getStorage('mfa_policy');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', 1)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $policy = $storage->load(reset($ids));
    return $policy instanceof MfaPolicyInterface ? $policy : NULL;
  }

  /**
   * Creates or updates the MFA policy for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param array $policyData
   *   Policy data:
   *   - enforcement: string (disabled, admins_only, required)
   *   - allowed_methods: string[] (totp, webauthn, sms)
   *   - grace_period_days: int
   *   - session_duration_hours: int
   *   - max_concurrent_sessions: int
   *
   * @return \Drupal\jaraba_sso\Entity\MfaPolicyInterface
   *   The created or updated MFA policy.
   */
  public function setPolicy(int $tenantId, array $policyData): MfaPolicyInterface {
    $storage = $this->entityTypeManager->getStorage('mfa_policy');

    // Look for existing policy.
    $existing = $this->getPolicy($tenantId);

    if ($existing) {
      // Update existing policy.
      if (isset($policyData['enforcement'])) {
        $existing->set('enforcement', $policyData['enforcement']);
      }
      if (isset($policyData['allowed_methods'])) {
        $existing->set('allowed_methods', json_encode($policyData['allowed_methods']));
      }
      if (isset($policyData['grace_period_days'])) {
        $existing->set('grace_period_days', $policyData['grace_period_days']);
      }
      if (isset($policyData['session_duration_hours'])) {
        $existing->set('session_duration_hours', $policyData['session_duration_hours']);
      }
      if (isset($policyData['max_concurrent_sessions'])) {
        $existing->set('max_concurrent_sessions', $policyData['max_concurrent_sessions']);
      }

      $existing->save();
      return $existing;
    }

    // Create new policy.
    $policy = $storage->create([
      'tenant_id' => $tenantId,
      'enforcement' => $policyData['enforcement'] ?? 'disabled',
      'allowed_methods' => json_encode($policyData['allowed_methods'] ?? ['totp']),
      'grace_period_days' => $policyData['grace_period_days'] ?? 7,
      'session_duration_hours' => $policyData['session_duration_hours'] ?? 8,
      'max_concurrent_sessions' => $policyData['max_concurrent_sessions'] ?? 3,
      'is_active' => TRUE,
    ]);

    $policy->save();
    return $policy;
  }

  /**
   * Checks whether MFA is required for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE if MFA is required for this user, FALSE otherwise.
   */
  public function isRequired(UserInterface $user): bool {
    // Resolve tenant for this user.
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return FALSE;
    }

    $policy = $this->getPolicy((int) $tenant->id());
    if (!$policy || !$policy->isActive()) {
      return FALSE;
    }

    $enforcement = $policy->getEnforcement();

    return match ($enforcement) {
      'disabled' => FALSE,
      'admins_only' => $this->userHasAdminRole($user),
      'required' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Gets the allowed MFA methods for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Array of allowed method identifiers (e.g., ['totp', 'webauthn']).
   */
  public function getAllowedMethods(int $tenantId): array {
    $policy = $this->getPolicy($tenantId);
    if (!$policy) {
      return ['totp'];
    }

    $methods = $policy->getAllowedMethods();
    return !empty($methods) ? $methods : ['totp'];
  }

  /**
   * Checks whether the user has an admin-level role.
   */
  protected function userHasAdminRole(UserInterface $user): bool {
    $userRoles = $user->getRoles(TRUE);
    return !empty(array_intersect($userRoles, self::ADMIN_ROLES));
  }

}
