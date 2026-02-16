<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_privacy\Service\BreachNotificationService;
use Drupal\jaraba_privacy\Service\CookieConsentManagerService;
use Drupal\jaraba_privacy\Service\DataRightsHandlerService;
use Drupal\jaraba_privacy\Service\DpaManagerService;
use Drupal\jaraba_privacy\Service\PrivacyPolicyGeneratorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST CONTROLLER DE PRIVACIDAD — PrivacyApiController.
 *
 * ESTRUCTURA:
 * Proporciona 10 endpoints REST para el stack de privacidad RGPD:
 * - DPA (5): current, sign, history, pdf, subprocessors.
 * - ARCO-POL (3): request, status, data-export.
 * - Cookies (2): consent POST, consent GET.
 *
 * LÓGICA DE NEGOCIO:
 * - Los endpoints de DPA requieren Bearer + tenant_admin.
 * - Los endpoints de ARCO-POL requieren Bearer + authenticated.
 * - Los endpoints de cookies son públicos (sin auth).
 * - Todas las respuestas usan envelope estándar via ApiResponseTrait.
 * - Rate limiting aplicado en los endpoints públicos de cookies.
 *
 * RELACIONES:
 * - PrivacyApiController → DpaManagerService (DPA endpoints)
 * - PrivacyApiController → DataRightsHandlerService (ARCO-POL)
 * - PrivacyApiController → CookieConsentManagerService (cookies)
 * - PrivacyApiController → PrivacyPolicyGeneratorService (políticas)
 * - PrivacyApiController → BreachNotificationService (brechas)
 * - PrivacyApiController ← routing (jaraba_privacy.routing.yml)
 *
 * Spec: Doc 183 §6-7. Plan: FASE 3, Stack Compliance Legal N1.
 */
class PrivacyApiController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  public function __construct(
    protected DpaManagerService $dpaManager,
    protected PrivacyPolicyGeneratorService $policyGenerator,
    protected CookieConsentManagerService $cookieConsentManager,
    protected DataRightsHandlerService $dataRightsHandler,
    protected BreachNotificationService $breachNotification,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_privacy.dpa_manager'),
      $container->get('jaraba_privacy.privacy_policy_generator'),
      $container->get('jaraba_privacy.cookie_consent_manager'),
      $container->get('jaraba_privacy.data_rights_handler'),
      $container->get('jaraba_privacy.breach_notification'),
      $container->get('logger.channel.jaraba_privacy'),
    );
  }

  // =========================================================================
  // DPA ENDPOINTS — Bearer + tenant_admin
  // =========================================================================

  /**
   * GET /api/v1/dpa/current — DPA vigente del tenant.
   */
  public function getDpaCurrent(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $dpa = $this->dpaManager->getCurrentDpa($tenantId);
      if (!$dpa) {
        return $this->apiSuccess([
          'has_dpa' => FALSE,
          'message' => 'No existe DPA activo para este tenant.',
        ]);
      }

      return $this->apiSuccess([
        'has_dpa' => TRUE,
        'id' => (int) $dpa->id(),
        'version' => $dpa->get('version')->value,
        'status' => $dpa->get('status')->value,
        'signed_at' => $dpa->get('signed_at')->value,
        'signer_name' => $dpa->get('signer_name')->value,
        'dpa_hash' => $dpa->get('dpa_hash')->value,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getDpaCurrent error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/dpa/sign — Firmar DPA electrónicamente.
   */
  public function signDpa(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];

      $required = ['signer_name', 'signer_role'];
      foreach ($required as $field) {
        if (empty($body[$field])) {
          return $this->apiError("El campo '$field' es obligatorio.", 'VALIDATION_ERROR', 422);
        }
      }

      $userId = (int) $this->currentUser()->id();
      $ipAddress = $request->getClientIp() ?? '0.0.0.0';

      $dpa = $this->dpaManager->signDpa(
        $tenantId,
        $userId,
        $ipAddress,
        $body['signer_name'],
        $body['signer_role'],
      );

      return $this->apiSuccess([
        'id' => (int) $dpa->id(),
        'version' => $dpa->get('version')->value,
        'status' => 'active',
        'signed_at' => $dpa->get('signed_at')->value,
        'dpa_hash' => $dpa->get('dpa_hash')->value,
      ], [], 201);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'DPA_SIGN_ERROR', 400);
    }
    catch (\Exception $e) {
      $this->logger->error('API signDpa error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/dpa/history — Historial de DPAs del tenant.
   */
  public function getDpaHistory(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $dpas = $this->dpaManager->getDpaHistory($tenantId);
      $items = [];

      foreach ($dpas as $dpa) {
        $items[] = [
          'id' => (int) $dpa->id(),
          'version' => $dpa->get('version')->value,
          'status' => $dpa->get('status')->value,
          'signed_at' => $dpa->get('signed_at')->value,
          'signer_name' => $dpa->get('signer_name')->value,
        ];
      }

      return $this->apiSuccess($items);
    }
    catch (\Exception $e) {
      $this->logger->error('API getDpaHistory error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/dpa/{dpa_id}/pdf — Descargar PDF del DPA.
   */
  public function getDpaPdf(Request $request, int $dpa_id): JsonResponse {
    try {
      $response = $this->dpaManager->exportDpaPdf($dpa_id);
      // El método exportDpaPdf retorna BinaryFileResponse directamente.
      // Pero aquí devolvemos un JsonResponse con la URL del PDF para
      // que el frontend lo descargue vía window.open().
      return $this->apiSuccess([
        'download_url' => '/api/v1/dpa/' . $dpa_id . '/pdf/download',
        'dpa_id' => $dpa_id,
      ]);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'DPA_PDF_ERROR', 404);
    }
    catch (\Exception $e) {
      $this->logger->error('API getDpaPdf error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/dpa/subprocessors — Lista de subprocesadores.
   */
  public function getSubprocessors(): JsonResponse {
    try {
      $subprocessors = $this->dpaManager->getSubprocessorsList();
      return $this->apiSuccess($subprocessors);
    }
    catch (\Exception $e) {
      $this->logger->error('API getSubprocessors error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // ARCO-POL ENDPOINTS — Bearer + authenticated
  // =========================================================================

  /**
   * POST /api/v1/privacy/rights/request — Crear solicitud ARCO-POL.
   */
  public function createRightsRequest(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];

      $required = ['right_type', 'description'];
      foreach ($required as $field) {
        if (empty($body[$field])) {
          return $this->apiError("El campo '$field' es obligatorio.", 'VALIDATION_ERROR', 422);
        }
      }

      $validTypes = ['access', 'rectification', 'erasure', 'restriction', 'portability', 'objection'];
      if (!in_array($body['right_type'], $validTypes, TRUE)) {
        return $this->apiError('Tipo de derecho no válido.', 'INVALID_RIGHT_TYPE', 422);
      }

      $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
      $requesterName = $user ? ($user->getDisplayName() ?? '') : '';
      $requesterEmail = $user ? ($user->getEmail() ?? '') : '';

      $result = $this->dataRightsHandler->createRequest(
        $tenantId,
        $requesterName,
        $requesterEmail,
        $body['right_type'],
        $body['description'],
        $body['verification_method'] ?? 'session',
      );

      return $this->apiSuccess([
        'id' => (int) $result->id(),
        'status' => $result->get('status')->value,
        'right_type' => $result->get('right_type')->value,
        'deadline' => (int) $result->get('deadline')->value,
        'deadline_human' => date('d/m/Y', (int) $result->get('deadline')->value),
      ], [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API createRightsRequest error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/privacy/rights/{request_id}/status — Estado de solicitud.
   */
  public function getRightsRequestStatus(int $request_id): JsonResponse {
    try {
      $status = $this->dataRightsHandler->getRequestStatus($request_id);

      if (isset($status['error'])) {
        return $this->apiError('Solicitud no encontrada.', 'NOT_FOUND', 404);
      }

      return $this->apiSuccess($status);
    }
    catch (\Exception $e) {
      $this->logger->error('API getRightsRequestStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/privacy/data-export — Solicitar exportación de datos.
   */
  public function requestDataExport(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
      $requesterName = $user ? ($user->getDisplayName() ?? '') : '';
      $requesterEmail = $user ? ($user->getEmail() ?? '') : '';

      $body = json_decode($request->getContent(), TRUE) ?? [];
      $format = $body['format'] ?? 'json';
      $description = "Solicitud de exportación de datos personales en formato $format.";

      $result = $this->dataRightsHandler->createRequest(
        $tenantId,
        $requesterName,
        $requesterEmail,
        'portability',
        $description,
        'session',
      );

      return $this->apiSuccess([
        'id' => (int) $result->id(),
        'status' => $result->get('status')->value,
        'right_type' => 'portability',
        'format' => $format,
        'deadline' => (int) $result->get('deadline')->value,
      ], [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API requestDataExport error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // COOKIE CONSENT ENDPOINTS — Público (sin auth)
  // =========================================================================

  /**
   * POST /api/v1/cookies/consent — Registrar consentimiento de cookies.
   */
  public function recordCookieConsent(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];

      $consentData = [
        'analytics' => !empty($body['analytics']),
        'marketing' => !empty($body['marketing']),
        'functional' => !empty($body['functional']),
        'thirdparty' => !empty($body['thirdparty']),
      ];

      $ipAddress = $request->getClientIp() ?? '0.0.0.0';
      $userId = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;
      $sessionId = $body['session_id'] ?? $request->getSession()?->getId();

      $consent = $this->cookieConsentManager->recordConsent(
        $consentData,
        $ipAddress,
        $userId,
        $sessionId,
      );

      return $this->apiSuccess([
        'id' => (int) $consent->id(),
        'consented_at' => (int) $consent->get('consented_at')->value,
        'categories' => $consentData,
      ], [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API recordCookieConsent error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/cookies/consent — Verificar consentimiento actual.
   */
  public function getCookieConsent(Request $request): JsonResponse {
    try {
      $userId = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;
      $sessionId = $request->query->get('session_id') ?? $request->getSession()?->getId();

      $consent = $this->cookieConsentManager->getCurrentConsent($userId, $sessionId);

      if (!$consent) {
        return $this->apiSuccess([
          'has_consent' => FALSE,
          'message' => 'No existe consentimiento registrado.',
        ]);
      }

      return $this->apiSuccess([
        'has_consent' => TRUE,
        'id' => (int) $consent->id(),
        'consented_at' => (int) $consent->get('consented_at')->value,
        'categories' => [
          'analytics' => (bool) $consent->get('consent_analytics')->value,
          'marketing' => (bool) $consent->get('consent_marketing')->value,
          'functional' => (bool) $consent->get('consent_functional')->value,
          'thirdparty' => (bool) $consent->get('consent_thirdparty')->value,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getCookieConsent error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // HELPER METHODS
  // =========================================================================

  /**
   * Resuelve el tenant_id desde la request.
   */
  protected function resolveTenantId(Request $request): ?int {
    $tenantId = $request->query->get('tenant_id');
    if ($tenantId) {
      return (int) $tenantId;
    }

    // Fallback: resolver desde la membresía de grupo del usuario actual.
    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $membershipLoader = \Drupal::service('group.membership_loader');
      $memberships = $membershipLoader->loadByUser($user);
      if (!empty($memberships)) {
        return (int) reset($memberships)->getGroup()->id();
      }
    }

    return NULL;
  }

}
