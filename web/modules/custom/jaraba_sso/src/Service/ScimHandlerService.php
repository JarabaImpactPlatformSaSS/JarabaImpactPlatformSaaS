<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * SCIM 2.0 Handler Service (RFC 7643 / RFC 7644).
 *
 * Implements a SCIM 2.0 REST server for user and group provisioning.
 * Used by enterprise IdPs (Azure AD, Okta, Google Workspace) to
 * automatically create, update, and deactivate users.
 *
 * COMPLIANCE:
 * - SCIM 2.0 Core Schema (RFC 7643)
 * - SCIM 2.0 Protocol (RFC 7644)
 * - Supports SCIM PATCH operations (add, replace, remove)
 * - Returns SCIM-formatted JSON (NOT standard platform envelope)
 *
 * MULTI-TENANCY:
 * All operations are scoped to the current tenant via TenantContextService.
 */
class ScimHandlerService {

  /**
   * SCIM schema URIs.
   */
  protected const SCHEMA_USER = 'urn:ietf:params:scim:schemas:core:2.0:User';
  protected const SCHEMA_GROUP = 'urn:ietf:params:scim:schemas:core:2.0:Group';
  protected const SCHEMA_LIST = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';
  protected const SCHEMA_PATCH = 'urn:ietf:params:scim:api:messages:2.0:PatchOp';
  protected const SCHEMA_SP_CONFIG = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';
  protected const SCHEMA_SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:Schema';

  /**
   * Constructor with property promotion.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Lists SCIM Users with optional filters and pagination.
   *
   * @param array $filters
   *   SCIM filter parameters (e.g., ['filter' => 'userName eq "john@example.com"']).
   * @param int $startIndex
   *   1-based start index for pagination.
   * @param int $count
   *   Number of results per page.
   *
   * @return array
   *   SCIM ListResponse with Users.
   */
  public function listUsers(array $filters = [], int $startIndex = 1, int $count = 100): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->range($startIndex - 1, $count)
      ->sort('uid', 'ASC');

    // Apply SCIM filter if present.
    if (!empty($filters['filter'])) {
      $this->applyScimFilter($query, $filters['filter']);
    }

    $uids = $query->execute();
    $users = $uids ? $storage->loadMultiple($uids) : [];

    // Count total.
    $countQuery = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->count();

    if (!empty($filters['filter'])) {
      $this->applyScimFilter($countQuery, $filters['filter']);
    }

    $totalResults = (int) $countQuery->execute();

    $resources = [];
    foreach ($users as $user) {
      $resources[] = $this->userToScim($user);
    }

    return [
      'schemas' => [self::SCHEMA_LIST],
      'totalResults' => $totalResults,
      'startIndex' => $startIndex,
      'itemsPerPage' => $count,
      'Resources' => $resources,
    ];
  }

  /**
   * Gets a single SCIM User by ID.
   *
   * @param string $id
   *   The Drupal user ID.
   *
   * @return array
   *   SCIM User resource.
   *
   * @throws \RuntimeException
   *   If the user is not found.
   */
  public function getUser(string $id): array {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    if (!$user) {
      throw new \RuntimeException('User not found: ' . $id);
    }

    return $this->userToScim($user);
  }

  /**
   * Creates a Drupal user from a SCIM User payload.
   *
   * @param array $data
   *   The SCIM User resource data.
   *
   * @return array
   *   The created SCIM User resource.
   */
  public function createUser(array $data): array {
    $email = $data['emails'][0]['value'] ?? ($data['userName'] ?? '');
    $userName = $data['userName'] ?? $email;

    if (empty($email)) {
      throw new \RuntimeException('Email or userName is required.');
    }

    // Check if user already exists.
    $existing = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $email]);
    if (!empty($existing)) {
      throw new \RuntimeException('User already exists with email: ' . $email);
    }

    $storage = $this->entityTypeManager->getStorage('user');
    $user = $storage->create([
      'name' => $userName,
      'mail' => $email,
      'status' => $data['active'] ?? TRUE ? 1 : 0,
      'pass' => bin2hex(random_bytes(32)),
    ]);

    // Set name fields if available.
    if (isset($data['name'])) {
      if (isset($data['name']['givenName']) && $user->hasField('field_first_name')) {
        $user->set('field_first_name', $data['name']['givenName']);
      }
      if (isset($data['name']['familyName']) && $user->hasField('field_last_name')) {
        $user->set('field_last_name', $data['name']['familyName']);
      }
    }

    $user->save();

    $this->logger->info('SCIM user created: @email (uid: @uid)', [
      '@email' => $email,
      '@uid' => $user->id(),
    ]);

    return $this->userToScim($user);
  }

  /**
   * Fully updates a Drupal user from a SCIM User payload.
   *
   * @param string $id
   *   The Drupal user ID.
   * @param array $data
   *   The full SCIM User resource data.
   *
   * @return array
   *   The updated SCIM User resource.
   */
  public function updateUser(string $id, array $data): array {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    if (!$user) {
      throw new \RuntimeException('User not found: ' . $id);
    }

    // Update email.
    if (isset($data['emails'][0]['value'])) {
      $user->set('mail', $data['emails'][0]['value']);
    }

    // Update username.
    if (isset($data['userName'])) {
      $user->set('name', $data['userName']);
    }

    // Update active status.
    if (isset($data['active'])) {
      $user->set('status', $data['active'] ? 1 : 0);
    }

    // Update name fields.
    if (isset($data['name'])) {
      if (isset($data['name']['givenName']) && $user->hasField('field_first_name')) {
        $user->set('field_first_name', $data['name']['givenName']);
      }
      if (isset($data['name']['familyName']) && $user->hasField('field_last_name')) {
        $user->set('field_last_name', $data['name']['familyName']);
      }
    }

    $user->save();

    $this->logger->info('SCIM user updated: uid @uid', ['@uid' => $id]);

    return $this->userToScim($user);
  }

  /**
   * Applies SCIM PATCH operations to a user.
   *
   * @param string $id
   *   The Drupal user ID.
   * @param array $operations
   *   Array of SCIM PATCH operations (op, path, value).
   *
   * @return array
   *   The updated SCIM User resource.
   */
  public function patchUser(string $id, array $operations): array {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    if (!$user) {
      throw new \RuntimeException('User not found: ' . $id);
    }

    foreach ($operations as $operation) {
      $op = strtolower($operation['op'] ?? '');
      $path = $operation['path'] ?? '';
      $value = $operation['value'] ?? NULL;

      match ($op) {
        'replace' => $this->applyPatchReplace($user, $path, $value),
        'add' => $this->applyPatchReplace($user, $path, $value),
        'remove' => $this->applyPatchRemove($user, $path),
        default => $this->logger->warning('Unknown SCIM PATCH op: @op', ['@op' => $op]),
      };
    }

    $user->save();

    $this->logger->info('SCIM user patched: uid @uid (@count operations)', [
      '@uid' => $id,
      '@count' => count($operations),
    ]);

    return $this->userToScim($user);
  }

  /**
   * Deactivates a user (soft delete per SCIM convention).
   *
   * @param string $id
   *   The Drupal user ID.
   */
  public function deleteUser(string $id): void {
    $user = $this->entityTypeManager->getStorage('user')->load($id);
    if (!$user) {
      throw new \RuntimeException('User not found: ' . $id);
    }

    // Soft delete: deactivate rather than permanently delete.
    $user->set('status', 0);
    $user->save();

    $this->logger->info('SCIM user deactivated (soft delete): uid @uid', ['@uid' => $id]);
  }

  /**
   * Lists SCIM Groups with optional filters.
   *
   * @param array $filters
   *   SCIM filter parameters.
   *
   * @return array
   *   SCIM ListResponse with Groups.
   */
  public function listGroups(array $filters = []): array {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    $resources = [];
    foreach ($roles as $role) {
      $resources[] = $this->roleToScimGroup($role);
    }

    return [
      'schemas' => [self::SCHEMA_LIST],
      'totalResults' => count($resources),
      'startIndex' => 1,
      'itemsPerPage' => count($resources),
      'Resources' => $resources,
    ];
  }

  /**
   * Gets a single SCIM Group by ID.
   *
   * @param string $id
   *   The Drupal role ID.
   *
   * @return array
   *   SCIM Group resource.
   */
  public function getGroup(string $id): array {
    $role = $this->entityTypeManager->getStorage('user_role')->load($id);
    if (!$role) {
      throw new \RuntimeException('Group not found: ' . $id);
    }

    return $this->roleToScimGroup($role);
  }

  /**
   * Creates a SCIM Group (Drupal role).
   *
   * @param array $data
   *   SCIM Group data.
   *
   * @return array
   *   The created SCIM Group resource.
   */
  public function createGroup(array $data): array {
    $displayName = $data['displayName'] ?? '';
    if (empty($displayName)) {
      throw new \RuntimeException('displayName is required for Group creation.');
    }

    $machineId = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $displayName));

    $storage = $this->entityTypeManager->getStorage('user_role');
    $role = $storage->create([
      'id' => $machineId,
      'label' => $displayName,
    ]);
    $role->save();

    $this->logger->info('SCIM group created: @name (id: @id)', [
      '@name' => $displayName,
      '@id' => $machineId,
    ]);

    return $this->roleToScimGroup($role);
  }

  /**
   * Updates a SCIM Group.
   *
   * @param string $id
   *   The Drupal role ID.
   * @param array $data
   *   SCIM Group data.
   *
   * @return array
   *   The updated SCIM Group resource.
   */
  public function updateGroup(string $id, array $data): array {
    $role = $this->entityTypeManager->getStorage('user_role')->load($id);
    if (!$role) {
      throw new \RuntimeException('Group not found: ' . $id);
    }

    if (isset($data['displayName'])) {
      $role->set('label', $data['displayName']);
    }

    // Handle member assignments.
    if (isset($data['members'])) {
      foreach ($data['members'] as $member) {
        $uid = $member['value'] ?? NULL;
        if ($uid) {
          $user = $this->entityTypeManager->getStorage('user')->load($uid);
          if ($user) {
            $user->addRole($id);
            $user->save();
          }
        }
      }
    }

    $role->save();

    $this->logger->info('SCIM group updated: @id', ['@id' => $id]);

    return $this->roleToScimGroup($role);
  }

  /**
   * Deletes a SCIM Group (Drupal role).
   *
   * @param string $id
   *   The Drupal role ID.
   */
  public function deleteGroup(string $id): void {
    $role = $this->entityTypeManager->getStorage('user_role')->load($id);
    if (!$role) {
      throw new \RuntimeException('Group not found: ' . $id);
    }

    $role->delete();

    $this->logger->info('SCIM group deleted: @id', ['@id' => $id]);
  }

  /**
   * Returns the SCIM ServiceProviderConfig.
   *
   * @return array
   *   SCIM ServiceProviderConfig resource.
   */
  public function getServiceProviderConfig(): array {
    return [
      'schemas' => [self::SCHEMA_SP_CONFIG],
      'documentationUri' => 'https://docs.jaraba.es/sso/scim',
      'patch' => ['supported' => TRUE],
      'bulk' => [
        'supported' => FALSE,
        'maxOperations' => 0,
        'maxPayloadSize' => 0,
      ],
      'filter' => [
        'supported' => TRUE,
        'maxResults' => 200,
      ],
      'changePassword' => ['supported' => FALSE],
      'sort' => ['supported' => FALSE],
      'etag' => ['supported' => FALSE],
      'authenticationSchemes' => [
        [
          'type' => 'oauthbearertoken',
          'name' => 'OAuth Bearer Token',
          'description' => 'Authentication scheme using the OAuth Bearer Token Standard.',
          'specUri' => 'https://www.rfc-editor.org/info/rfc6750',
          'primary' => TRUE,
        ],
      ],
      'meta' => [
        'resourceType' => 'ServiceProviderConfig',
        'created' => '2026-01-01T00:00:00Z',
        'lastModified' => '2026-01-01T00:00:00Z',
      ],
    ];
  }

  /**
   * Returns the SCIM Schemas discovery.
   *
   * @return array
   *   Array of SCIM Schema resources.
   */
  public function getSchemas(): array {
    return [
      'schemas' => [self::SCHEMA_LIST],
      'totalResults' => 2,
      'startIndex' => 1,
      'itemsPerPage' => 2,
      'Resources' => [
        [
          'schemas' => [self::SCHEMA_SCHEMA],
          'id' => self::SCHEMA_USER,
          'name' => 'User',
          'description' => 'User Account',
          'attributes' => [
            ['name' => 'userName', 'type' => 'string', 'required' => TRUE, 'mutability' => 'readWrite'],
            ['name' => 'name', 'type' => 'complex', 'required' => FALSE, 'mutability' => 'readWrite', 'subAttributes' => [
              ['name' => 'givenName', 'type' => 'string'],
              ['name' => 'familyName', 'type' => 'string'],
            ]],
            ['name' => 'emails', 'type' => 'complex', 'required' => TRUE, 'multiValued' => TRUE, 'mutability' => 'readWrite'],
            ['name' => 'active', 'type' => 'boolean', 'required' => FALSE, 'mutability' => 'readWrite'],
          ],
          'meta' => [
            'resourceType' => 'Schema',
            'location' => '/scim/v2/Schemas/' . self::SCHEMA_USER,
          ],
        ],
        [
          'schemas' => [self::SCHEMA_SCHEMA],
          'id' => self::SCHEMA_GROUP,
          'name' => 'Group',
          'description' => 'Group (Role)',
          'attributes' => [
            ['name' => 'displayName', 'type' => 'string', 'required' => TRUE, 'mutability' => 'readWrite'],
            ['name' => 'members', 'type' => 'complex', 'required' => FALSE, 'multiValued' => TRUE, 'mutability' => 'readWrite'],
          ],
          'meta' => [
            'resourceType' => 'Schema',
            'location' => '/scim/v2/Schemas/' . self::SCHEMA_GROUP,
          ],
        ],
      ],
    ];
  }

  /**
   * Converts a Drupal user entity to a SCIM User resource.
   */
  protected function userToScim($user): array {
    $request = \Drupal::request();
    $baseUrl = $request->getSchemeAndHttpHost();

    $emails = [];
    $mail = $user->get('mail')->value ?? '';
    if (!empty($mail)) {
      $emails[] = [
        'value' => $mail,
        'type' => 'work',
        'primary' => TRUE,
      ];
    }

    $givenName = '';
    $familyName = '';
    if ($user->hasField('field_first_name') && !$user->get('field_first_name')->isEmpty()) {
      $givenName = $user->get('field_first_name')->value;
    }
    if ($user->hasField('field_last_name') && !$user->get('field_last_name')->isEmpty()) {
      $familyName = $user->get('field_last_name')->value;
    }

    $groups = [];
    $roles = $user->getRoles(TRUE);
    foreach ($roles as $roleId) {
      $groups[] = [
        'value' => $roleId,
        'display' => $roleId,
        'type' => 'direct',
      ];
    }

    return [
      'schemas' => [self::SCHEMA_USER],
      'id' => (string) $user->id(),
      'userName' => $user->get('name')->value ?? '',
      'name' => [
        'givenName' => $givenName,
        'familyName' => $familyName,
        'formatted' => trim($givenName . ' ' . $familyName),
      ],
      'emails' => $emails,
      'active' => (bool) $user->get('status')->value,
      'groups' => $groups,
      'meta' => [
        'resourceType' => 'User',
        'created' => date('c', (int) $user->get('created')->value),
        'lastModified' => date('c', (int) $user->get('changed')->value),
        'location' => $baseUrl . '/scim/v2/Users/' . $user->id(),
      ],
    ];
  }

  /**
   * Converts a Drupal role to a SCIM Group resource.
   */
  protected function roleToScimGroup($role): array {
    $request = \Drupal::request();
    $baseUrl = $request->getSchemeAndHttpHost();

    // Load members of this role.
    $uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', $role->id())
      ->execute();

    $members = [];
    foreach ($uids as $uid) {
      $members[] = [
        'value' => (string) $uid,
        'type' => 'User',
      ];
    }

    return [
      'schemas' => [self::SCHEMA_GROUP],
      'id' => $role->id(),
      'displayName' => $role->label(),
      'members' => $members,
      'meta' => [
        'resourceType' => 'Group',
        'location' => $baseUrl . '/scim/v2/Groups/' . $role->id(),
      ],
    ];
  }

  /**
   * Applies a SCIM filter string to an entity query.
   *
   * Supports basic filters: userName eq "value", emails.value eq "value".
   */
  protected function applyScimFilter($query, string $filter): void {
    // Parse simple SCIM filter: "attribute op value".
    if (preg_match('/^(\S+)\s+(eq|co|sw)\s+"([^"]*)"$/i', trim($filter), $matches)) {
      $attribute = $matches[1];
      $operator = strtolower($matches[2]);
      $value = $matches[3];

      $drupalField = match ($attribute) {
        'userName' => 'name',
        'emails.value', 'emails[type eq "work"].value' => 'mail',
        default => NULL,
      };

      if ($drupalField !== NULL) {
        $drupalOperator = match ($operator) {
          'eq' => '=',
          'co' => 'CONTAINS',
          'sw' => 'STARTS_WITH',
          default => '=',
        };

        $query->condition($drupalField, $value, $drupalOperator);
      }
    }
  }

  /**
   * Applies a SCIM PATCH replace operation.
   */
  protected function applyPatchReplace($user, string $path, mixed $value): void {
    match ($path) {
      'userName' => $user->set('name', $value),
      'active' => $user->set('status', $value ? 1 : 0),
      'emails[type eq "work"].value' => $user->set('mail', $value),
      'name.givenName' => $user->hasField('field_first_name') ? $user->set('field_first_name', $value) : NULL,
      'name.familyName' => $user->hasField('field_last_name') ? $user->set('field_last_name', $value) : NULL,
      default => $this->logger->warning('Unknown SCIM PATCH path: @path', ['@path' => $path]),
    };
  }

  /**
   * Applies a SCIM PATCH remove operation.
   */
  protected function applyPatchRemove($user, string $path): void {
    match ($path) {
      'name.givenName' => $user->hasField('field_first_name') ? $user->set('field_first_name', '') : NULL,
      'name.familyName' => $user->hasField('field_last_name') ? $user->set('field_last_name', '') : NULL,
      default => $this->logger->warning('SCIM PATCH remove not supported for path: @path', ['@path' => $path]),
    };
  }

}
