<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Service\DataClassifierService;
use Drupal\jaraba_governance\Service\DataLineageService;
use Drupal\jaraba_governance\Service\DataMaskingService;
use Drupal\jaraba_governance\Service\ErasureService;
use Drupal\jaraba_governance\Service\RetentionPolicyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for Data Governance endpoints.
 *
 * All responses use the standardized JSON envelope:
 * {success: bool, data: mixed, error: string|null}
 */
class GovernanceApiController extends ControllerBase {

  /**
   * Data classification service.
   */
  protected DataClassifierService $classifier;

  /**
   * Retention policy service.
   */
  protected RetentionPolicyService $retention;

  /**
   * Data lineage service.
   */
  protected DataLineageService $lineage;

  /**
   * Erasure service.
   */
  protected ErasureService $erasure;

  /**
   * Data masking service.
   */
  protected DataMaskingService $masking;

  /**
   * Tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->classifier = $container->get('jaraba_governance.classifier');
    $instance->retention = $container->get('jaraba_governance.retention');
    $instance->lineage = $container->get('jaraba_governance.lineage');
    $instance->erasure = $container->get('jaraba_governance.erasure');
    $instance->masking = $container->get('jaraba_governance.masking');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  // =========================================================================
  // DATA CLASSIFICATION ENDPOINTS
  // =========================================================================

  /**
   * Lists all data classifications.
   *
   * GET /api/v1/governance/classifications
   */
  public function listClassifications(): JsonResponse {
    try {
      $summary = $this->classifier->getClassificationSummary();
      return new JsonResponse([
        'success' => TRUE,
        'data' => $summary,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Gets classification for a specific entity type.
   *
   * GET /api/v1/governance/classifications/{entity_type}
   */
  public function getClassificationForEntityType(string $entity_type): JsonResponse {
    try {
      $classifications = $this->classifier->getClassificationsForEntity($entity_type);
      $data = [];
      foreach ($classifications as $key => $classification) {
        $data[$key] = [
          'id' => (int) $classification->id(),
          'entity_type_id' => $classification->getEntityTypeClassified(),
          'field_name' => $classification->getFieldName(),
          'classification_level' => $classification->getClassificationLevel(),
          'is_pii' => $classification->isPii(),
          'is_sensitive' => $classification->isSensitive(),
          'retention_days' => $classification->getRetentionDays(),
          'encryption_required' => $classification->isEncryptionRequired(),
          'masking_required' => $classification->isMaskingRequired(),
          'cross_border_allowed' => $classification->isCrossBorderAllowed(),
          'legal_basis' => $classification->getLegalBasis(),
        ];
      }
      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Creates or updates a data classification.
   *
   * POST /api/v1/governance/classifications
   */
  public function saveClassification(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      if (empty($content['entity_type']) || empty($content['level'])) {
        return new JsonResponse([
          'success' => FALSE,
          'data' => NULL,
          'error' => 'Missing required fields: entity_type, level.',
        ], 400);
      }

      $entityType = $content['entity_type'];
      $fieldName = $content['field_name'] ?? NULL;
      $level = $content['level'];
      $options = array_intersect_key($content, array_flip([
        'is_pii', 'is_sensitive', 'retention_days',
        'encryption_required', 'masking_required',
        'cross_border_allowed', 'legal_basis',
      ]));

      $classification = $this->classifier->setClassification($entityType, $fieldName, $level, $options);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $classification->id(),
          'entity_type_id' => $classification->getEntityTypeClassified(),
          'classification_level' => $classification->getClassificationLevel(),
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // RETENTION POLICY ENDPOINTS
  // =========================================================================

  /**
   * Lists retention policies with preview of affected entities.
   *
   * GET /api/v1/governance/retention-policies
   */
  public function listRetentionPolicies(): JsonResponse {
    try {
      $preview = $this->retention->previewRetention();
      return new JsonResponse([
        'success' => TRUE,
        'data' => $preview,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Executes retention policy cleanup.
   *
   * POST /api/v1/governance/retention/execute
   */
  public function executeRetention(): JsonResponse {
    try {
      $stats = $this->retention->executeRetention();
      return new JsonResponse([
        'success' => TRUE,
        'data' => $stats,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // DATA LINEAGE ENDPOINTS
  // =========================================================================

  /**
   * Gets data lineage for an entity.
   *
   * GET /api/v1/governance/lineage/{entity_type}/{entity_id}
   */
  public function getLineage(string $entity_type, int $entity_id): JsonResponse {
    try {
      $lineage = $this->lineage->getLineage($entity_type, $entity_id);
      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'entity_type' => $entity_type,
          'entity_id' => $entity_id,
          'events' => $lineage,
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // GDPR ERASURE ENDPOINTS
  // =========================================================================

  /**
   * Creates a new GDPR erasure request.
   *
   * POST /api/v1/governance/erasure-request
   */
  public function createErasureRequest(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      if (empty($content['subject_user_id']) || empty($content['request_type'])) {
        return new JsonResponse([
          'success' => FALSE,
          'data' => NULL,
          'error' => 'Missing required fields: subject_user_id, request_type.',
        ], 400);
      }

      $erasureRequest = $this->erasure->createRequest(
        (int) $content['subject_user_id'],
        $content['request_type'],
        $content['reason'] ?? NULL,
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $erasureRequest->id(),
          'status' => $erasureRequest->getStatus(),
          'request_type' => $erasureRequest->getRequestType(),
          'subject_user_id' => $erasureRequest->getSubjectUserId(),
          'created' => $erasureRequest->getCreatedTime(),
        ],
        'error' => NULL,
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Lists pending erasure requests.
   *
   * GET /api/v1/governance/erasure-requests
   */
  public function listErasureRequests(): JsonResponse {
    try {
      $requests = $this->erasure->getPendingRequests();
      return new JsonResponse([
        'success' => TRUE,
        'data' => $requests,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Processes a pending erasure request.
   *
   * POST /api/v1/governance/erasure-requests/{request_id}/process
   */
  public function processErasureRequest(int $request_id): JsonResponse {
    try {
      $result = $this->erasure->processRequest($request_id);
      if (isset($result['error'])) {
        return new JsonResponse([
          'success' => FALSE,
          'data' => NULL,
          'error' => $result['error'],
        ], 400);
      }
      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // GDPR EXPORT ENDPOINT
  // =========================================================================

  /**
   * Exports all data for a user (GDPR portability).
   *
   * GET /api/v1/governance/export/{user_id}
   */
  public function exportUserData(int $user_id): JsonResponse {
    try {
      $export = $this->erasure->exportUserData($user_id);

      // Record lineage event for the export.
      $this->lineage->recordEvent('user', $user_id, 'exported', [
        'export_type' => 'gdpr_portability',
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $export,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // DATA MASKING ENDPOINT
  // =========================================================================

  /**
   * Executes data masking for staging/dev environment.
   *
   * POST /api/v1/governance/masking/execute
   */
  public function executeMasking(): JsonResponse {
    try {
      $stats = $this->masking->maskDatabase();
      return new JsonResponse([
        'success' => TRUE,
        'data' => $stats,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // =========================================================================
  // DASHBOARD ENDPOINT
  // =========================================================================

  /**
   * Returns governance dashboard data (API).
   *
   * GET /api/v1/governance/dashboard
   */
  public function dashboardData(): JsonResponse {
    try {
      $data = [
        'classification_stats' => $this->classifier->getClassificationSummary(),
        'retention_preview' => $this->retention->previewRetention(),
        'pending_erasure_requests' => $this->erasure->getPendingRequests(),
        'recent_lineage' => $this->lineage->getRecentActivity(20),
        'pii_entity_types' => $this->classifier->getPiiEntities(),
      ];

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

}
