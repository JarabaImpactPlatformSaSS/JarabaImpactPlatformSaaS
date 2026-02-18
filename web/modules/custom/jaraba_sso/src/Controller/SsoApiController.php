<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Service\MfaEnforcerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller for SSO Provider CRUD and MFA Policy.
 *
 * ENDPOINTS:
 * - GET    /api/v1/sso/providers         — List SSO providers for tenant
 * - POST   /api/v1/sso/providers         — Create SSO provider config
 * - PATCH  /api/v1/sso/providers/{id}    — Update provider config
 * - DELETE /api/v1/sso/providers/{id}    — Delete provider
 * - GET    /api/v1/sso/mfa/policy        — Get MFA policy for tenant
 * - POST   /api/v1/sso/mfa/policy        — Set MFA policy
 *
 * All endpoints return standard JSON envelope: { success, data/error }.
 * CSRF required on mutations.
 */
class SsoApiController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly TenantContextService $tenantContext,
    protected readonly MfaEnforcerService $mfaEnforcer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('jaraba_sso.mfa_enforcer'),
    );
  }

  /**
   * Lists all SSO providers for the current tenant.
   *
   * GET /api/v1/sso/providers
   */
  public function listProviders(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $storage = $this->entityTypeManager()->getStorage('sso_configuration');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('is_active', 1)
        ->sort('provider_name', 'ASC')
        ->execute();

      $entities = $ids ? $storage->loadMultiple($ids) : [];
      $providers = [];

      foreach ($entities as $entity) {
        $providers[] = [
          'id' => (int) $entity->id(),
          'provider_name' => $entity->getProviderName(),
          'provider_type' => $entity->getProviderType(),
          'entity_id' => $entity->getEntityId(),
          'sso_url' => $entity->getSsoUrl(),
          'auto_provision' => $entity->isAutoProvision(),
          'force_sso' => $entity->isForceSso(),
          'is_active' => $entity->isActive(),
          'default_role' => $entity->getDefaultRole(),
          'created' => $entity->get('created')->value,
          'changed' => $entity->get('changed')->value,
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'providers' => $providers,
          'count' => count($providers),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Creates a new SSO provider configuration.
   *
   * POST /api/v1/sso/providers
   */
  public function createProvider(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $data = json_decode($request->getContent(), TRUE);
      if (!is_array($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid JSON body.',
        ], 400);
      }

      // Validate required fields.
      if (empty($data['provider_name']) || empty($data['provider_type'])) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Fields provider_name and provider_type are required.',
        ], 400);
      }

      if (!in_array($data['provider_type'], ['saml', 'oidc'], TRUE)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'provider_type must be saml or oidc.',
        ], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('sso_configuration');
      $entity = $storage->create([
        'tenant_id' => $tenantId,
        'provider_name' => $data['provider_name'],
        'provider_type' => $data['provider_type'],
        'entity_id' => $data['entity_id'] ?? '',
        'sso_url' => $data['sso_url'] ?? '',
        'slo_url' => $data['slo_url'] ?? '',
        'certificate' => $data['certificate'] ?? '',
        'client_secret' => $data['client_secret'] ?? '',
        'token_url' => $data['token_url'] ?? '',
        'userinfo_url' => $data['userinfo_url'] ?? '',
        'attribute_mapping' => isset($data['attribute_mapping']) ? json_encode($data['attribute_mapping']) : '{}',
        'default_role' => $data['default_role'] ?? 'authenticated',
        'auto_provision' => $data['auto_provision'] ?? TRUE,
        'force_sso' => $data['force_sso'] ?? FALSE,
        'is_active' => $data['is_active'] ?? TRUE,
      ]);

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $entity->id(),
          'provider_name' => $entity->getProviderName(),
          'provider_type' => $entity->getProviderType(),
        ],
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Updates an existing SSO provider configuration.
   *
   * PATCH /api/v1/sso/providers/{provider_id}
   */
  public function updateProvider(Request $request, int $provider_id): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $storage = $this->entityTypeManager()->getStorage('sso_configuration');
      $entity = $storage->load($provider_id);

      if (!$entity || $entity->getTenantId() !== $tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Provider not found.',
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      if (!is_array($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid JSON body.',
        ], 400);
      }

      // Apply only provided fields.
      $allowedFields = [
        'provider_name', 'provider_type', 'entity_id', 'sso_url', 'slo_url',
        'certificate', 'client_secret', 'token_url', 'userinfo_url',
        'default_role', 'auto_provision', 'force_sso', 'is_active',
      ];

      foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
          $entity->set($field, $data[$field]);
        }
      }

      // Handle attribute_mapping separately (needs JSON encoding).
      if (isset($data['attribute_mapping'])) {
        $entity->set('attribute_mapping', json_encode($data['attribute_mapping']));
      }

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $entity->id(),
          'provider_name' => $entity->getProviderName(),
          'changed' => $entity->get('changed')->value,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Deletes an SSO provider configuration.
   *
   * DELETE /api/v1/sso/providers/{provider_id}
   */
  public function deleteProvider(int $provider_id): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $storage = $this->entityTypeManager()->getStorage('sso_configuration');
      $entity = $storage->load($provider_id);

      if (!$entity || $entity->getTenantId() !== $tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Provider not found.',
        ], 404);
      }

      $name = $entity->getProviderName();
      $entity->delete();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'deleted' => TRUE,
          'provider_name' => $name,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Gets the MFA policy for the current tenant.
   *
   * GET /api/v1/sso/mfa/policy
   */
  public function getMfaPolicy(): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $policy = $this->mfaEnforcer->getPolicy($tenantId);
      if (!$policy) {
        return new JsonResponse([
          'success' => TRUE,
          'data' => [
            'policy' => NULL,
            'message' => 'No MFA policy configured for this tenant.',
          ],
        ]);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'policy' => [
            'id' => (int) $policy->id(),
            'enforcement' => $policy->getEnforcement(),
            'allowed_methods' => $policy->getAllowedMethods(),
            'grace_period_days' => $policy->getGracePeriodDays(),
            'session_duration_hours' => $policy->getSessionDurationHours(),
            'max_concurrent_sessions' => $policy->getMaxConcurrentSessions(),
            'is_active' => $policy->isActive(),
          ],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Sets/updates the MFA policy for the current tenant.
   *
   * POST /api/v1/sso/mfa/policy
   */
  public function setMfaPolicy(Request $request): JsonResponse {
    try {
      $tenantId = $this->getCurrentTenantId();
      if (!$tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tenant context resolved.',
        ], 403);
      }

      $data = json_decode($request->getContent(), TRUE);
      if (!is_array($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid JSON body.',
        ], 400);
      }

      // Validate enforcement value.
      if (isset($data['enforcement']) && !in_array($data['enforcement'], ['disabled', 'admins_only', 'required'], TRUE)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'enforcement must be one of: disabled, admins_only, required.',
        ], 400);
      }

      // Validate allowed methods.
      if (isset($data['allowed_methods'])) {
        $validMethods = ['totp', 'webauthn', 'sms'];
        foreach ($data['allowed_methods'] as $method) {
          if (!in_array($method, $validMethods, TRUE)) {
            return new JsonResponse([
              'success' => FALSE,
              'error' => 'Invalid MFA method: ' . $method . '. Allowed: totp, webauthn, sms.',
            ], 400);
          }
        }
      }

      $policy = $this->mfaEnforcer->setPolicy($tenantId, $data);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'policy' => [
            'id' => (int) $policy->id(),
            'enforcement' => $policy->getEnforcement(),
            'allowed_methods' => $policy->getAllowedMethods(),
            'grace_period_days' => $policy->getGracePeriodDays(),
            'session_duration_hours' => $policy->getSessionDurationHours(),
            'max_concurrent_sessions' => $policy->getMaxConcurrentSessions(),
          ],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Resolves the current tenant ID.
   */
  protected function getCurrentTenantId(): ?int {
    $tenant = $this->tenantContext->getCurrentTenant();
    return $tenant ? (int) $tenant->id() : NULL;
  }

}
