<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_sso\Service\ScimHandlerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * SCIM 2.0 REST API Controller (RFC 7643 / RFC 7644).
 *
 * Exposes SCIM-compliant endpoints for user and group provisioning.
 * Used by enterprise IdPs (Azure AD, Okta, Google Workspace) for
 * automated user lifecycle management.
 *
 * IMPORTANT: All responses follow SCIM JSON format (NOT the standard
 * platform JSON envelope). SCIM has its own response structure per RFC 7644.
 *
 * ENDPOINTS:
 * - GET/POST          /scim/v2/Users                 — List/Create users
 * - GET/PUT/PATCH/DEL /scim/v2/Users/{id}            — Single user operations
 * - GET/POST          /scim/v2/Groups                — List/Create groups
 * - GET/PUT/PATCH/DEL /scim/v2/Groups/{id}           — Single group operations
 * - GET               /scim/v2/ServiceProviderConfig  — SCIM discovery
 * - GET               /scim/v2/Schemas               — Schema discovery
 *
 * CONTENT-TYPE: application/scim+json
 */
class ScimApiController extends ControllerBase {

  /**
   * SCIM content type header.
   */
  protected const SCIM_CONTENT_TYPE = 'application/scim+json; charset=UTF-8';

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly ScimHandlerService $scimHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_sso.scim_handler'),
    );
  }

  /**
   * Handles GET (list) and POST (create) for /scim/v2/Users.
   */
  public function usersEndpoint(Request $request): JsonResponse {
    try {
      if ($request->isMethod('POST')) {
        return $this->createUser($request);
      }

      // GET: List users with optional filters.
      $filters = [];
      $filter = $request->query->get('filter');
      if ($filter) {
        $filters['filter'] = $filter;
      }

      $startIndex = max(1, (int) $request->query->get('startIndex', 1));
      $count = min(200, max(1, (int) $request->query->get('count', 100)));

      $result = $this->scimHandler->listUsers($filters, $startIndex, $count);

      return $this->scimResponse($result);
    }
    catch (\Exception $e) {
      return $this->scimError($e->getMessage(), 500);
    }
  }

  /**
   * Handles GET, PUT, PATCH, DELETE for /scim/v2/Users/{id}.
   */
  public function userEndpoint(Request $request, string $scim_id): JsonResponse {
    try {
      return match ($request->getMethod()) {
        'GET' => $this->scimResponse($this->scimHandler->getUser($scim_id)),
        'PUT' => $this->updateUser($request, $scim_id),
        'PATCH' => $this->patchUser($request, $scim_id),
        'DELETE' => $this->deleteUser($scim_id),
        default => $this->scimError('Method not allowed.', 405),
      };
    }
    catch (\RuntimeException $e) {
      $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
      return $this->scimError($e->getMessage(), $status);
    }
    catch (\Exception $e) {
      return $this->scimError($e->getMessage(), 500);
    }
  }

  /**
   * Handles GET (list) and POST (create) for /scim/v2/Groups.
   */
  public function groupsEndpoint(Request $request): JsonResponse {
    try {
      if ($request->isMethod('POST')) {
        return $this->createGroup($request);
      }

      $filters = [];
      $filter = $request->query->get('filter');
      if ($filter) {
        $filters['filter'] = $filter;
      }

      $result = $this->scimHandler->listGroups($filters);

      return $this->scimResponse($result);
    }
    catch (\Exception $e) {
      return $this->scimError($e->getMessage(), 500);
    }
  }

  /**
   * Handles GET, PUT, PATCH, DELETE for /scim/v2/Groups/{id}.
   */
  public function groupEndpoint(Request $request, string $scim_id): JsonResponse {
    try {
      return match ($request->getMethod()) {
        'GET' => $this->scimResponse($this->scimHandler->getGroup($scim_id)),
        'PUT' => $this->updateGroupEndpoint($request, $scim_id),
        'PATCH' => $this->patchGroupEndpoint($request, $scim_id),
        'DELETE' => $this->deleteGroup($scim_id),
        default => $this->scimError('Method not allowed.', 405),
      };
    }
    catch (\RuntimeException $e) {
      $status = str_contains($e->getMessage(), 'not found') ? 404 : 400;
      return $this->scimError($e->getMessage(), $status);
    }
    catch (\Exception $e) {
      return $this->scimError($e->getMessage(), 500);
    }
  }

  /**
   * Returns SCIM ServiceProviderConfig.
   *
   * GET /scim/v2/ServiceProviderConfig
   */
  public function serviceProviderConfig(): JsonResponse {
    return $this->scimResponse($this->scimHandler->getServiceProviderConfig());
  }

  /**
   * Returns SCIM Schemas.
   *
   * GET /scim/v2/Schemas
   */
  public function schemas(): JsonResponse {
    return $this->scimResponse($this->scimHandler->getSchemas());
  }

  /**
   * Creates a new user from SCIM payload.
   */
  protected function createUser(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return $this->scimError('Invalid JSON body.', 400);
    }

    try {
      $result = $this->scimHandler->createUser($data);
      return $this->scimResponse($result, 201);
    }
    catch (\RuntimeException $e) {
      $status = str_contains($e->getMessage(), 'already exists') ? 409 : 400;
      return $this->scimError($e->getMessage(), $status);
    }
  }

  /**
   * Full update of a user.
   */
  protected function updateUser(Request $request, string $id): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return $this->scimError('Invalid JSON body.', 400);
    }

    $result = $this->scimHandler->updateUser($id, $data);
    return $this->scimResponse($result);
  }

  /**
   * Partial update (PATCH) of a user.
   */
  protected function patchUser(Request $request, string $id): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data) || empty($data['Operations'])) {
      return $this->scimError('Invalid SCIM PATCH body. Operations array is required.', 400);
    }

    $result = $this->scimHandler->patchUser($id, $data['Operations']);
    return $this->scimResponse($result);
  }

  /**
   * Deletes (deactivates) a user.
   */
  protected function deleteUser(string $id): JsonResponse {
    $this->scimHandler->deleteUser($id);
    return new JsonResponse(NULL, 204);
  }

  /**
   * Creates a new group from SCIM payload.
   */
  protected function createGroup(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return $this->scimError('Invalid JSON body.', 400);
    }

    try {
      $result = $this->scimHandler->createGroup($data);
      return $this->scimResponse($result, 201);
    }
    catch (\RuntimeException $e) {
      return $this->scimError($e->getMessage(), 400);
    }
  }

  /**
   * Full update of a group.
   */
  protected function updateGroupEndpoint(Request $request, string $id): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return $this->scimError('Invalid JSON body.', 400);
    }

    $result = $this->scimHandler->updateGroup($id, $data);
    return $this->scimResponse($result);
  }

  /**
   * Partial update (PATCH) of a group.
   */
  protected function patchGroupEndpoint(Request $request, string $id): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data) || empty($data['Operations'])) {
      return $this->scimError('Invalid SCIM PATCH body. Operations array is required.', 400);
    }

    // Reuse updateGroup for simplicity (apply operations as full update for groups).
    $updateData = [];
    foreach ($data['Operations'] as $op) {
      if (($op['op'] ?? '') === 'replace' || ($op['op'] ?? '') === 'add') {
        $path = $op['path'] ?? '';
        $value = $op['value'] ?? NULL;
        if ($path === 'displayName') {
          $updateData['displayName'] = $value;
        }
        if ($path === 'members') {
          $updateData['members'] = is_array($value) ? $value : [];
        }
      }
    }

    $result = $this->scimHandler->updateGroup($id, $updateData);
    return $this->scimResponse($result);
  }

  /**
   * Deletes a group.
   */
  protected function deleteGroup(string $id): JsonResponse {
    $this->scimHandler->deleteGroup($id);
    return new JsonResponse(NULL, 204);
  }

  /**
   * Creates a SCIM-formatted JSON response.
   */
  protected function scimResponse(array $data, int $status = 200): JsonResponse {
    return new JsonResponse($data, $status, [
      'Content-Type' => self::SCIM_CONTENT_TYPE,
    ]);
  }

  /**
   * Creates a SCIM error response per RFC 7644 Section 3.12.
   */
  protected function scimError(string $detail, int $status): JsonResponse {
    return new JsonResponse([
      'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
      'detail' => $detail,
      'status' => (string) $status,
    ], $status, [
      'Content-Type' => self::SCIM_CONTENT_TYPE,
    ]);
  }

}
