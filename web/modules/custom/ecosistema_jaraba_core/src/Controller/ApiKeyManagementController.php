<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\AdminCenterSettingsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API key self-service management controller.
 *
 * GAP-API-KEYS: Provides endpoints for tenants to manage their
 * own API keys: list, create, revoke, rotate.
 *
 * CONTROLLER-READONLY-001: Do not use readonly for inherited properties.
 * TENANT-001: All keys scoped to the current tenant.
 * SECRET-MGMT-001: Keys shown only once at creation.
 */
class ApiKeyManagementController extends ControllerBase {

  /**
   * Constructor.
   *
   * CONTROLLER-READONLY-001: Do not use readonly for inherited properties.
   */
  public function __construct(
    protected readonly AdminCenterSettingsService $settingsService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.admin_center_settings'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * Renders the API key management page.
   *
   * @return array
   *   Render array for the API key management page.
   */
  public function page(): array {
    $keys = $this->settingsService->listApiKeys();

    // Filter to only show active keys with masked values.
    $displayKeys = [];
    foreach ($keys as $key) {
      if (($key['status'] ?? 'revoked') === 'active') {
        $displayKeys[] = [
          'id' => $key['id'],
          'label' => $key['label'] ?? '',
          'scope' => $key['scope'] ?? 'read',
          'prefix' => $key['key_prefix'] ?? '****...',
          'created' => $key['created'] ?? '',
          'last_used' => $key['last_used'] ?? NULL,
        ];
      }
    }

    return [
      '#theme' => 'api_key_management',
      '#keys' => $displayKeys,
      '#scopes' => [
        'read' => $this->t('Solo lectura'),
        'write' => $this->t('Lectura/Escritura'),
        'admin' => $this->t('Administración completa'),
      ],
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/api-key-management'],
      ],
    ];
  }

  /**
   * API: Create a new API key.
   *
   * POST /api/v1/api-keys with body: { label, scope }.
   */
  public function createKey(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];
    $label = trim($body['label'] ?? '');
    $scope = $body['scope'] ?? 'read';

    if ($label === '') {
      return new JsonResponse(['success' => FALSE, 'error' => 'Label is required'], 400);
    }

    if (mb_strlen($label) > 100) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Label too long (max 100 chars)'], 400);
    }

    try {
      $record = $this->settingsService->createApiKey($label, $scope);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => $record['id'],
          'label' => $record['label'],
          'scope' => $record['scope'],
          'key' => $record['key'],
          'prefix' => $record['key_prefix'],
          'created' => $record['created'],
        ],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('API key creation error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * API: Revoke an API key.
   *
   * POST /api/v1/api-keys/{key_id}/revoke.
   */
  public function revokeKey(string $key_id): JsonResponse {
    $result = $this->settingsService->revokeApiKey($key_id);

    if ($result) {
      return new JsonResponse(['success' => TRUE, 'data' => ['revoked' => TRUE]]);
    }

    return new JsonResponse(['success' => FALSE, 'error' => 'Key not found or already revoked'], 404);
  }

  /**
   * API: Rotate an API key (revoke + create new with same config).
   *
   * POST /api/v1/api-keys/{key_id}/rotate.
   */
  public function rotateKey(string $key_id): JsonResponse {
    try {
      $record = $this->settingsService->rotateApiKey($key_id);

      if ($record === NULL) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Key not found or already revoked'], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => $record['id'],
          'label' => $record['label'],
          'scope' => $record['scope'],
          'key' => $record['key'],
          'prefix' => $record['key_prefix'],
          'created' => $record['created'],
        ],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('API key rotation error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

}
