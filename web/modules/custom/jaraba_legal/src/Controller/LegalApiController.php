<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_legal\Service\AupEnforcerService;
use Drupal\jaraba_legal\Service\OffboardingManagerService;
use Drupal\jaraba_legal\Service\SlaCalculatorService;
use Drupal\jaraba_legal\Service\TosManagerService;
use Drupal\jaraba_legal\Service\WhistleblowerChannelService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST CONTROLLER LEGAL — LegalApiController.
 *
 * ESTRUCTURA:
 * Proporciona 12 endpoints REST para el stack legal compliance:
 * - ToS (3): current, accept, check.
 * - SLA (2): report, current.
 * - AUP (2): violations, usage.
 * - Offboarding (3): request, status, cancel.
 * - Whistleblower (2): report (publico), status (publico).
 *
 * LOGICA DE NEGOCIO:
 * - Los endpoints de ToS, SLA, AUP requieren Bearer + access legal api.
 * - Los endpoints de offboarding requieren Bearer + manage offboarding.
 * - Los endpoints de whistleblower son publicos (sin auth).
 * - Todas las respuestas usan envelope estandar via ApiResponseTrait.
 *
 * RELACIONES:
 * - LegalApiController -> TosManagerService (ToS endpoints)
 * - LegalApiController -> SlaCalculatorService (SLA endpoints)
 * - LegalApiController -> AupEnforcerService (AUP endpoints)
 * - LegalApiController -> OffboardingManagerService (offboarding endpoints)
 * - LegalApiController -> WhistleblowerChannelService (whistleblower endpoints)
 * - LegalApiController <- routing (jaraba_legal.routing.yml)
 *
 * Spec: Doc 184 §6-7. Plan: FASE 7, Stack Compliance Legal N1.
 */
class LegalApiController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  public function __construct(
    protected TosManagerService $tosManager,
    protected SlaCalculatorService $slaCalculator,
    protected AupEnforcerService $aupEnforcer,
    protected OffboardingManagerService $offboardingManager,
    protected WhistleblowerChannelService $whistleblowerChannel,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal.tos_manager'),
      $container->get('jaraba_legal.sla_calculator'),
      $container->get('jaraba_legal.aup_enforcer'),
      $container->get('jaraba_legal.offboarding_manager'),
      $container->get('jaraba_legal.whistleblower_channel'),
      $container->get('logger.channel.jaraba_legal'),
    );
  }

  // =========================================================================
  // TOS ENDPOINTS — Bearer + access legal api
  // =========================================================================

  /**
   * GET /api/v1/legal/tos/current — ToS vigente del tenant.
   */
  public function getActiveToS(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $tos = $this->tosManager->getActiveVersion($tenantId);
      if (!$tos) {
        return $this->apiSuccess([
          'has_active_tos' => FALSE,
          'message' => 'No existe ToS activo para este tenant.',
        ]);
      }

      return $this->apiSuccess([
        'has_active_tos' => TRUE,
        'id' => (int) $tos->id(),
        'version' => $tos->get('version')->value,
        'title' => $tos->get('title')->value,
        'content_hash' => $tos->get('content_hash')->value,
        'published_at' => $tos->get('published_at')->value ? (int) $tos->get('published_at')->value : NULL,
        'effective_date' => $tos->get('effective_date')->value ? (int) $tos->get('effective_date')->value : NULL,
        'accepted_count' => (int) $tos->get('accepted_count')->value,
        'requires_acceptance' => (bool) $tos->get('requires_acceptance')->value,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getActiveToS error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/legal/tos/accept — Aceptar ToS vigente.
   */
  public function acceptToS(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $userId = (int) $this->currentUser()->id();
      $ipAddress = $request->getClientIp() ?? '0.0.0.0';

      $acceptance = $this->tosManager->acceptToS($tenantId, $userId, $ipAddress);

      return $this->apiSuccess([
        'accepted' => TRUE,
        'tos_id' => $acceptance['tos_id'],
        'tos_version' => $acceptance['tos_version'],
        'accepted_at' => $acceptance['accepted_at'],
        'content_hash' => $acceptance['content_hash'],
      ], [], 201);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'TOS_ACCEPT_ERROR', 400);
    }
    catch (\Exception $e) {
      $this->logger->error('API acceptToS error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/legal/tos/check — Verificar aceptacion de ToS.
   */
  public function checkAcceptance(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $acceptance = $this->tosManager->checkAcceptance($tenantId);

      return $this->apiSuccess($acceptance);
    }
    catch (\Exception $e) {
      $this->logger->error('API checkAcceptance error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // SLA ENDPOINTS — Bearer + access legal api
  // =========================================================================

  /**
   * GET /api/v1/legal/sla/{tenant_id}/report — Informe SLA del tenant.
   */
  public function getSlaReport(Request $request, int $tenant_id): JsonResponse {
    try {
      // Determinar periodo: parametros o mes actual.
      $periodStart = $request->query->get('period_start');
      $periodEnd = $request->query->get('period_end');

      if ($periodStart && $periodEnd) {
        $periodStart = (int) $periodStart;
        $periodEnd = (int) $periodEnd;
      }
      else {
        // Mes actual por defecto.
        $periodStart = (int) strtotime(date('Y-m-01 00:00:00'));
        $periodEnd = (int) strtotime(date('Y-m-t 23:59:59'));
      }

      $report = $this->slaCalculator->generateSlaReport($tenant_id, $periodStart, $periodEnd);

      return $this->apiSuccess($report);
    }
    catch (\Exception $e) {
      $this->logger->error('API getSlaReport error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/legal/sla/{tenant_id}/current — Estado SLA actual del tenant.
   */
  public function getCurrentSla(Request $request, int $tenant_id): JsonResponse {
    try {
      $record = $this->slaCalculator->getCurrentSlaRecord($tenant_id);

      if (!$record) {
        return $this->apiSuccess([
          'has_sla_record' => FALSE,
          'message' => 'No existen registros SLA para este tenant.',
        ]);
      }

      return $this->apiSuccess([
        'has_sla_record' => TRUE,
        'id' => (int) $record->id(),
        'uptime_percentage' => (float) $record->get('uptime_percentage')->value,
        'target_percentage' => (float) $record->get('target_percentage')->value,
        'downtime_minutes' => (int) $record->get('downtime_minutes')->value,
        'credit_percentage' => (float) $record->get('credit_percentage')->value,
        'credit_applied' => (bool) $record->get('credit_applied')->value,
        'incident_count' => (int) $record->get('incident_count')->value,
        'is_met' => $record->isMet(),
        'period_start' => (int) $record->get('period_start')->value,
        'period_end' => (int) $record->get('period_end')->value,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getCurrentSla error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // AUP ENDPOINTS — Bearer + access legal api
  // =========================================================================

  /**
   * GET /api/v1/legal/aup/violations — Historial de violaciones AUP.
   */
  public function getViolations(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $violations = $this->aupEnforcer->getViolationHistory($tenantId);

      return $this->apiSuccess($violations);
    }
    catch (\Exception $e) {
      $this->logger->error('API getViolations error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/legal/aup/usage — Limites de uso actuales del tenant.
   */
  public function getUsageLimits(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $limits = $this->aupEnforcer->checkUsageLimits($tenantId);

      return $this->apiSuccess($limits);
    }
    catch (\Exception $e) {
      $this->logger->error('API getUsageLimits error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // OFFBOARDING ENDPOINTS — Bearer + manage offboarding
  // =========================================================================

  /**
   * POST /api/v1/legal/offboarding/request — Solicitar offboarding.
   */
  public function requestOffboarding(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant no encontrado.', 'TENANT_NOT_FOUND', 403);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];

      if (empty($body['reason'])) {
        return $this->apiError("El campo 'reason' es obligatorio.", 'VALIDATION_ERROR', 422);
      }

      $validReasons = ['voluntary', 'non_payment', 'aup_violation', 'contract_end', 'other'];
      if (!in_array($body['reason'], $validReasons, TRUE)) {
        return $this->apiError('Motivo de baja no valido.', 'INVALID_REASON', 422);
      }

      $userId = (int) $this->currentUser()->id();

      $offboarding = $this->offboardingManager->initiateOffboarding(
        $tenantId,
        $userId,
        $body['reason'],
        $body['reason_detail'] ?? '',
      );

      return $this->apiSuccess([
        'id' => (int) $offboarding->id(),
        'status' => $offboarding->get('status')->value,
        'grace_period_end' => (int) $offboarding->get('grace_period_end')->value,
        'grace_period_end_human' => date('d/m/Y', (int) $offboarding->get('grace_period_end')->value),
      ], [], 201);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'OFFBOARDING_ERROR', 400);
    }
    catch (\Exception $e) {
      $this->logger->error('API requestOffboarding error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/legal/offboarding/{id}/status — Estado del offboarding.
   */
  public function getOffboardingStatus(int $id): JsonResponse {
    try {
      $status = $this->offboardingManager->getOffboardingStatus($id);

      return $this->apiSuccess($status);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'NOT_FOUND', 404);
    }
    catch (\Exception $e) {
      $this->logger->error('API getOffboardingStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/legal/offboarding/{id}/cancel — Cancelar offboarding.
   */
  public function cancelOffboarding(int $id): JsonResponse {
    try {
      $request = $this->offboardingManager->cancelOffboarding($id);

      return $this->apiSuccess([
        'id' => (int) $request->id(),
        'status' => 'cancelled',
        'message' => 'El proceso de offboarding ha sido cancelado.',
      ]);
    }
    catch (\RuntimeException $e) {
      return $this->apiError($e->getMessage(), 'CANCEL_ERROR', 400);
    }
    catch (\Exception $e) {
      $this->logger->error('API cancelOffboarding error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // WHISTLEBLOWER ENDPOINTS — Publico (sin auth)
  // =========================================================================

  /**
   * POST /api/v1/legal/whistleblower/report — Enviar denuncia (publico).
   */
  public function submitReport(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];

      // Validar campos obligatorios.
      if (empty($body['category'])) {
        return $this->apiError("El campo 'category' es obligatorio.", 'VALIDATION_ERROR', 422);
      }
      if (empty($body['description'])) {
        return $this->apiError("El campo 'description' es obligatorio.", 'VALIDATION_ERROR', 422);
      }

      $validCategories = ['fraud', 'corruption', 'harassment', 'safety', 'environment', 'data_protection', 'other'];
      if (!in_array($body['category'], $validCategories, TRUE)) {
        return $this->apiError('Categoria no valida.', 'INVALID_CATEGORY', 422);
      }

      $reportData = [
        'category' => $body['category'],
        'description' => $body['description'],
        'severity' => $body['severity'] ?? 'medium',
        'reporter_contact' => $body['reporter_contact'] ?? NULL,
        'is_anonymous' => $body['is_anonymous'] ?? TRUE,
        'ip_address' => $request->getClientIp() ?? '0.0.0.0',
        'tenant_id' => $body['tenant_id'] ?? NULL,
      ];

      $result = $this->whistleblowerChannel->submitReport($reportData);

      return $this->apiSuccess($result, [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API submitReport error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/legal/whistleblower/{tracking_code}/status — Estado de denuncia (publico).
   */
  public function getReportStatus(string $tracking_code): JsonResponse {
    try {
      $report = $this->whistleblowerChannel->getReportByTrackingCode($tracking_code);

      if (!$report) {
        return $this->apiError(
          'No se encontro un reporte con ese codigo de seguimiento.',
          'NOT_FOUND',
          404
        );
      }

      return $this->apiSuccess($report);
    }
    catch (\Exception $e) {
      $this->logger->error('API getReportStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error interno.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // HELPER METHODS
  // =========================================================================

  /**
   * Resuelve el tenant_id desde la request.
   *
   * Intenta resolver el tenant desde el parametro query 'tenant_id'
   * o desde la membresia de grupo del usuario autenticado.
   */
  protected function resolveTenantId(Request $request): ?int {
    $tenantId = $request->query->get('tenant_id');
    if ($tenantId) {
      return (int) $tenantId;
    }

    // Fallback: resolver desde la membresia de grupo del usuario actual.
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
