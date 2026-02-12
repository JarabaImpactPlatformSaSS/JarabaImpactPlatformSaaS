<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use Drupal\jaraba_pwa\Service\PwaManifestService;
use Drupal\jaraba_pwa\Service\PwaSyncManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for PWA endpoints.
 *
 * Exposes REST endpoints for:
 * - POST /api/v1/pwa/push/subscribe: Register a push subscription.
 * - DELETE /api/v1/pwa/push/unsubscribe: Remove a push subscription.
 * - GET /api/v1/pwa/manifest: Dynamic manifest.json.
 * - POST /api/v1/pwa/sync: Process background sync actions.
 *
 * All endpoints except manifest require authentication.
 */
class PwaApiController extends ControllerBase {

  /**
   * Push notification service.
   */
  protected PlatformPushService $pushService;

  /**
   * Manifest generation service.
   */
  protected PwaManifestService $manifestService;

  /**
   * Sync manager service.
   */
  protected PwaSyncManagerService $syncManager;

  /**
   * Logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pushService = $container->get('jaraba_pwa.push');
    $instance->manifestService = $container->get('jaraba_pwa.manifest');
    $instance->syncManager = $container->get('jaraba_pwa.sync_manager');
    $instance->currentUser = $container->get('current_user');
    $instance->logger = $container->get('logger.channel.jaraba_pwa');
    return $instance;
  }

  /**
   * Registers a push subscription for the current user.
   *
   * Expected JSON body:
   * {
   *   "endpoint": "https://fcm.googleapis.com/fcm/send/...",
   *   "keys": {
   *     "auth": "base64url...",
   *     "p256dh": "base64url..."
   *   },
   *   "topics": ["alerts", "updates"]
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request with subscription data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with subscription result.
   */
  public function subscribe(Request $request): JsonResponse {
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => $this->t('Authentication required.')->render(),
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    $data = json_decode($request->getContent(), TRUE);

    if (empty($data)) {
      return new JsonResponse([
        'error' => $this->t('Invalid request body. JSON expected.')->render(),
        'code' => 'INVALID_BODY',
      ], 400);
    }

    if (empty($data['endpoint'])) {
      return new JsonResponse([
        'error' => $this->t('The endpoint field is required.')->render(),
        'code' => 'MISSING_ENDPOINT',
      ], 400);
    }

    if (empty($data['keys']['auth']) || empty($data['keys']['p256dh'])) {
      return new JsonResponse([
        'error' => $this->t('The auth and p256dh keys are required.')->render(),
        'code' => 'MISSING_KEYS',
      ], 400);
    }

    if (!filter_var($data['endpoint'], FILTER_VALIDATE_URL) || !str_starts_with($data['endpoint'], 'https://')) {
      return new JsonResponse([
        'error' => $this->t('The endpoint must be a valid HTTPS URL.')->render(),
        'code' => 'INVALID_ENDPOINT',
      ], 400);
    }

    try {
      $data['user_id'] = (int) $this->currentUser->id();
      $data['user_agent'] = $request->headers->get('User-Agent', '');

      $subscriptionId = $this->pushService->subscribe($data);

      if ($subscriptionId === NULL) {
        return new JsonResponse([
          'error' => $this->t('Failed to create subscription.')->render(),
          'code' => 'CREATION_FAILED',
        ], 500);
      }

      return new JsonResponse([
        'success' => TRUE,
        'message' => $this->t('Push subscription registered successfully.')->render(),
        'subscription_id' => $subscriptionId,
      ], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating push subscription: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => $this->t('Internal error registering subscription.')->render(),
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

  /**
   * Unregisters a push subscription.
   *
   * Expected JSON body:
   * {
   *   "endpoint": "https://fcm.googleapis.com/fcm/send/..."
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request with the endpoint to unsubscribe.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with unsubscribe result.
   */
  public function unsubscribe(Request $request): JsonResponse {
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => $this->t('Authentication required.')->render(),
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['endpoint'])) {
      return new JsonResponse([
        'error' => $this->t('The endpoint field is required.')->render(),
        'code' => 'MISSING_ENDPOINT',
      ], 400);
    }

    try {
      $result = $this->pushService->unsubscribe($data['endpoint']);

      if ($result) {
        return new JsonResponse([
          'success' => TRUE,
          'message' => $this->t('Push subscription removed successfully.')->render(),
        ]);
      }

      return new JsonResponse([
        'error' => $this->t('No subscription found for this endpoint.')->render(),
        'code' => 'NOT_FOUND',
      ], 404);
    }
    catch (\Exception $e) {
      $this->logger->error('Error unsubscribing push endpoint: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => $this->t('Internal error removing subscription.')->render(),
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

  /**
   * Returns the dynamic PWA manifest.
   *
   * Publicly accessible endpoint that returns a W3C Web App Manifest
   * JSON, optionally customized per tenant via query parameter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request. Accepts optional ?tenant_id parameter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The manifest as JSON response with proper Content-Type.
   */
  public function getManifest(Request $request): JsonResponse {
    try {
      $tenantId = $request->query->get('tenant_id');
      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;

      $manifest = $this->manifestService->generateManifest($tenantIdInt);

      $response = new JsonResponse($manifest);
      $response->headers->set('Content-Type', 'application/manifest+json');
      $response->headers->set('Cache-Control', 'public, max-age=3600');
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating PWA manifest: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => $this->t('Failed to generate manifest.')->render(),
        'code' => 'MANIFEST_ERROR',
      ], 500);
    }
  }

  /**
   * Processes background sync actions from the service worker.
   *
   * Expected JSON body:
   * {
   *   "actions": [
   *     {
   *       "action_type": "create",
   *       "entity_type": "node",
   *       "entity_id": 0,
   *       "payload": {...}
   *     }
   *   ]
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request with sync actions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with sync processing results.
   */
  public function syncActions(Request $request): JsonResponse {
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => $this->t('Authentication required.')->render(),
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['actions']) || !is_array($data['actions'])) {
      return new JsonResponse([
        'error' => $this->t('The actions array is required.')->render(),
        'code' => 'MISSING_ACTIONS',
      ], 400);
    }

    try {
      $userId = (int) $this->currentUser->id();
      $queued = 0;

      foreach ($data['actions'] as $action) {
        if (empty($action['action_type']) || empty($action['entity_type'])) {
          continue;
        }

        $payload = $action['payload'] ?? [];
        $payload['user_id'] = $userId;

        $id = $this->syncManager->queueAction(
          $action['action_type'],
          $action['entity_type'],
          (int) ($action['entity_id'] ?? 0),
          $payload
        );

        if ($id !== NULL) {
          $queued++;
        }
      }

      // Process immediately.
      $processed = $this->syncManager->processPendingActions($userId);

      return new JsonResponse([
        'success' => TRUE,
        'message' => $this->t('@queued actions queued, @processed processed.', [
          '@queued' => $queued,
          '@processed' => $processed,
        ])->render(),
        'queued' => $queued,
        'processed' => $processed,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error processing sync actions: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => $this->t('Internal error processing sync actions.')->render(),
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

}
