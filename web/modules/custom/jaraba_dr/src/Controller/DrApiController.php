<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_dr\Service\BackupVerifierService;
use Drupal\jaraba_dr\Service\DrTestRunnerService;
use Drupal\jaraba_dr\Service\FailoverOrchestratorService;
use Drupal\jaraba_dr\Service\IncidentCommunicatorService;
use Drupal\jaraba_dr\Service\StatusPageManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST CONTROLLER DE DISASTER RECOVERY -- DrApiController.
 *
 * ESTRUCTURA:
 * Proporciona 8 endpoints REST para el stack de Disaster Recovery:
 * - Status (1): estado general publico.
 * - Services (1): estado de los servicios monitorizados.
 * - Incidents (2): listado e incidente individual con comunicaciones.
 * - Backups (1): verificacion de integridad.
 * - Tests (2): historial y ejecucion.
 * - Metrics (1): metricas agregadas de DR (RTO/RPO, uptime, backups).
 *
 * LOGICA:
 * - El endpoint /status es publico (sin autenticacion).
 * - El resto de endpoints requiere el permiso 'access dr api'.
 * - Todas las respuestas usan envelope estandar via ApiResponseTrait.
 * - Los endpoints POST validan el body JSON antes de procesar.
 *
 * RELACIONES:
 * - DrApiController -> BackupVerifierService (verificacion de backups)
 * - DrApiController -> FailoverOrchestratorService (estado failover)
 * - DrApiController -> StatusPageManagerService (status page)
 * - DrApiController -> IncidentCommunicatorService (comunicacion)
 * - DrApiController -> DrTestRunnerService (tests DR)
 * - DrApiController <- jaraba_dr.routing.yml (8 rutas API)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 11, Stack Compliance Legal N1.
 */
class DrApiController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  /**
   * Construye el controlador de la API DR.
   *
   * @param \Drupal\jaraba_dr\Service\BackupVerifierService $backupVerifier
   *   Servicio de verificacion de backups.
   * @param \Drupal\jaraba_dr\Service\FailoverOrchestratorService $failoverOrchestrator
   *   Servicio de orquestacion de failover.
   * @param \Drupal\jaraba_dr\Service\StatusPageManagerService $statusPageManager
   *   Servicio de gestion de status page.
   * @param \Drupal\jaraba_dr\Service\IncidentCommunicatorService $incidentCommunicator
   *   Servicio de comunicacion de incidentes.
   * @param \Drupal\jaraba_dr\Service\DrTestRunnerService $testRunner
   *   Servicio de ejecucion de tests DR.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected BackupVerifierService $backupVerifier,
    protected FailoverOrchestratorService $failoverOrchestrator,
    protected StatusPageManagerService $statusPageManager,
    protected IncidentCommunicatorService $incidentCommunicator,
    protected DrTestRunnerService $testRunner,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_dr.backup_verifier'),
      $container->get('jaraba_dr.failover_orchestrator'),
      $container->get('jaraba_dr.status_page_manager'),
      $container->get('jaraba_dr.incident_communicator'),
      $container->get('jaraba_dr.test_runner'),
      $container->get('logger.channel.jaraba_dr'),
    );
  }

  // =========================================================================
  // STATUS ENDPOINT -- Publico (sin autenticacion)
  // =========================================================================

  /**
   * GET /api/v1/dr/status -- Estado general publico de la plataforma.
   *
   * Devuelve el estado global, servicios, incidentes activos y uptime.
   * Este endpoint es publico para alimentar la status page.
   */
  public function getStatus(): JsonResponse {
    try {
      $data = $this->statusPageManager->getStatusPageData();
      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      $this->logger->error('API getStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener el estado.', 'STATUS_ERROR', 500);
    }
  }

  // =========================================================================
  // SERVICES ENDPOINT -- Requiere access dr api
  // =========================================================================

  /**
   * GET /api/v1/dr/services -- Estado de los servicios monitorizados.
   */
  public function getServicesStatus(): JsonResponse {
    try {
      $services = $this->statusPageManager->getServicesStatus();
      return $this->apiSuccess($services);
    }
    catch (\Exception $e) {
      $this->logger->error('API getServicesStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener estado de servicios.', 'SERVICES_ERROR', 500);
    }
  }

  // =========================================================================
  // INCIDENTS ENDPOINTS -- Requiere access dr api
  // =========================================================================

  /**
   * GET /api/v1/dr/incidents -- Listado de incidentes activos.
   */
  public function getIncidents(): JsonResponse {
    try {
      $incidents = $this->statusPageManager->getActiveIncidents();
      return $this->apiSuccess($incidents);
    }
    catch (\Exception $e) {
      $this->logger->error('API getIncidents error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener incidentes.', 'INCIDENTS_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/dr/incidents/{id} -- Detalle de un incidente con comunicaciones.
   */
  public function getIncidentDetail(int $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('dr_incident');
      $incident = $storage->load($id);

      if (!$incident) {
        return $this->apiError('Incidente no encontrado.', 'NOT_FOUND', 404);
      }

      $communicationLog = $this->incidentCommunicator->getIncidentCommunicationHistory($id);

      $data = [
        'id' => (int) $incident->id(),
        'title' => $incident->get('title')->value,
        'severity' => $incident->get('severity')->value,
        'status' => $incident->get('status')->value,
        'description' => $incident->get('description')->value,
        'affected_services' => $incident->getAffectedServicesDecoded(),
        'impact' => $incident->get('impact')->value,
        'root_cause' => $incident->get('root_cause')->value,
        'resolution' => $incident->get('resolution')->value,
        'started_at' => (int) $incident->get('started_at')->value,
        'resolved_at' => (int) $incident->get('resolved_at')->value,
        'duration_seconds' => $incident->getDurationSeconds(),
        'postmortem_url' => $incident->get('postmortem_url')->value,
        'communication_log' => $communicationLog,
        'created' => (int) $incident->get('created')->value,
        'changed' => (int) $incident->get('changed')->value,
      ];

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      $this->logger->error('API getIncidentDetail error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener detalle del incidente.', 'INCIDENT_DETAIL_ERROR', 500);
    }
  }

  // =========================================================================
  // BACKUPS ENDPOINT -- Requiere access dr api
  // =========================================================================

  /**
   * POST /api/v1/dr/backups/verify -- Verificar integridad de un backup.
   */
  public function verifyBackup(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];

      // Validar campos requeridos.
      if (empty($body['backup_path'])) {
        return $this->apiError("El campo 'backup_path' es obligatorio.", 'VALIDATION_ERROR', 422);
      }

      $backupType = $body['backup_type'] ?? 'full';
      $backupPath = $body['backup_path'];
      $expectedChecksum = $body['expected_checksum'] ?? NULL;

      $result = $this->backupVerifier->verifyBackup($backupType, $backupPath, $expectedChecksum);

      $statusCode = ($result['status'] === 'verified') ? 200 : 200;
      return $this->apiSuccess($result, [], $statusCode);
    }
    catch (\Exception $e) {
      $this->logger->error('API verifyBackup error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al verificar backup.', 'BACKUP_VERIFY_ERROR', 500);
    }
  }

  // =========================================================================
  // TEST ENDPOINTS -- Requiere access dr api / execute dr tests
  // =========================================================================

  /**
   * GET /api/v1/dr/tests -- Historial de tests DR.
   */
  public function getTestHistory(Request $request): JsonResponse {
    try {
      $limit = (int) ($request->query->get('limit') ?? 50);
      $limit = min(max($limit, 1), 200);

      $tests = $this->testRunner->getTestHistory($limit);
      $stats = $this->testRunner->getTestStats();

      return $this->apiSuccess([
        'tests' => $tests,
        'stats' => $stats,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getTestHistory error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener historial de tests.', 'TEST_HISTORY_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/dr/tests/run -- Ejecutar un test DR.
   */
  public function runTest(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];

      // Validar campos requeridos.
      if (empty($body['test_name'])) {
        return $this->apiError("El campo 'test_name' es obligatorio.", 'VALIDATION_ERROR', 422);
      }
      if (empty($body['test_type'])) {
        return $this->apiError("El campo 'test_type' es obligatorio.", 'VALIDATION_ERROR', 422);
      }

      $validTypes = ['backup_restore', 'failover', 'network', 'database', 'full_dr'];
      if (!in_array($body['test_type'], $validTypes, TRUE)) {
        return $this->apiError(
          "Tipo de test no valido. Tipos permitidos: " . implode(', ', $validTypes),
          'INVALID_TEST_TYPE',
          422
        );
      }

      $result = $this->testRunner->executeTest(
        $body['test_name'],
        $body['test_type'],
        $body['description'] ?? NULL,
      );

      return $this->apiSuccess($result, [], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('API runTest error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al ejecutar test DR.', 'TEST_RUN_ERROR', 500);
    }
  }

  // =========================================================================
  // METRICS ENDPOINT -- Requiere access dr api
  // =========================================================================

  /**
   * GET /api/v1/dr/metrics -- Metricas agregadas de DR.
   *
   * Devuelve metricas de backups, tests, uptime y RTO/RPO.
   */
  public function getMetrics(Request $request): JsonResponse {
    try {
      $days = (int) ($request->query->get('days') ?? 90);
      $days = min(max($days, 1), 365);

      $backupStats = $this->backupVerifier->getVerificationStats();
      $testStats = $this->testRunner->getTestStats();
      $rtoRpo = $this->testRunner->calculateRtoRpo();
      $uptime = $this->statusPageManager->calculateUptime($days);
      $failoverStatus = $this->failoverOrchestrator->getFailoverStatus();

      return $this->apiSuccess([
        'backups' => $backupStats,
        'tests' => $testStats,
        'rto_rpo' => $rtoRpo,
        'uptime' => $uptime,
        'failover' => [
          'status' => $failoverStatus['status'],
          'reason' => $failoverStatus['reason'],
        ],
        'generated_at' => time(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getMetrics error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener metricas DR.', 'METRICS_ERROR', 500);
    }
  }

}
