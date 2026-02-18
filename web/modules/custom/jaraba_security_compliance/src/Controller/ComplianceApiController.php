<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_security_compliance\Service\ComplianceTrackerService;
use Drupal\jaraba_security_compliance\Service\EnsComplianceService;
use Drupal\jaraba_security_compliance\Service\IsoRiskService;
use Drupal\jaraba_security_compliance\Service\PolicyEnforcerService;
use Drupal\jaraba_security_compliance\Service\Soc2ControlMapperService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller REST API para consultas de compliance y políticas.
 *
 * Endpoints:
 * - GET /api/v1/security/compliance/status
 * - GET /api/v1/security/policies
 */
class ComplianceApiController extends ControllerBase {

  /**
   * Servicio de compliance tracker.
   *
   * @var \Drupal\jaraba_security_compliance\Service\ComplianceTrackerService
   */
  protected ComplianceTrackerService $complianceTracker;

  /**
   * Servicio de policy enforcer.
   *
   * @var \Drupal\jaraba_security_compliance\Service\PolicyEnforcerService
   */
  protected PolicyEnforcerService $policyEnforcer;

  /**
   * The tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * The SOC 2 control mapper service.
   *
   * @var \Drupal\jaraba_security_compliance\Service\Soc2ControlMapperService
   */
  protected Soc2ControlMapperService $soc2Mapper;

  /**
   * The ENS compliance service.
   *
   * @var \Drupal\jaraba_security_compliance\Service\EnsComplianceService
   */
  protected EnsComplianceService $ensCompliance;

  /**
   * The ISO risk service.
   *
   * @var \Drupal\jaraba_security_compliance\Service\IsoRiskService
   */
  protected IsoRiskService $isoRisk;

  /**
   * Constructor con inyeccion de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ComplianceTrackerService $complianceTracker,
    PolicyEnforcerService $policyEnforcer,
    TenantContextService $tenantContext,
    Soc2ControlMapperService $soc2Mapper,
    EnsComplianceService $ensCompliance,
    IsoRiskService $isoRisk,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->complianceTracker = $complianceTracker;
    $this->policyEnforcer = $policyEnforcer;
    $this->tenantContext = $tenantContext;
    $this->soc2Mapper = $soc2Mapper;
    $this->ensCompliance = $ensCompliance;
    $this->isoRisk = $isoRisk;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_security_compliance.compliance_tracker'),
      $container->get('jaraba_security_compliance.policy_enforcer'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('jaraba_security_compliance.soc2_control_mapper'),
      $container->get('jaraba_security_compliance.ens_compliance'),
      $container->get('jaraba_security_compliance.iso_risk'),
    );
  }

  /**
   * Returns the compliance status for all frameworks.
   *
   * GET /api/v1/security/compliance/status
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request. Optional query param: tenant_id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with compliance status.
   */
  public function getStatus(Request $request): JsonResponse {
    try {
      $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;

      $status = $this->complianceTracker->getComplianceStatus($tenantIdInt);
      $score = $this->complianceTracker->getComplianceScore($tenantIdInt);
      $violations = $this->policyEnforcer->getViolations($tenantIdInt);

      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'compliance_score' => $score,
          'frameworks' => $status,
          'violations' => $violations,
        ],
        'meta' => ['generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => $this->t('Failed to retrieve compliance status.')->render()],
      ], 500);
    }
  }

  /**
   * Returns active security policies.
   *
   * GET /api/v1/security/policies
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request. Optional query param: tenant_id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with active policies.
   */
  public function getPolicies(Request $request): JsonResponse {
    try {
      $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;

      $policies = $this->policyEnforcer->getActivePolicies($tenantIdInt);
      $policyData = [];

      foreach ($policies as $policy) {
        /** @var \Drupal\jaraba_security_compliance\Entity\SecurityPolicy $policy */
        $policyData[] = [
          'id' => (int) $policy->id(),
          'name' => $policy->getName(),
          'policy_type' => $policy->getPolicyType(),
          'version' => $policy->getVersion(),
          'status' => $policy->getPolicyStatus(),
          'tenant_id' => $policy->getTenantId(),
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $policyData,
        'meta' => ['total' => count($policyData), 'generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => $this->t('Failed to retrieve security policies.')->render()],
      ], 500);
    }
  }

  /**
   * Returns the ISO 27001 risk register.
   *
   * GET /api/v1/compliance/risk-register
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with risk register data.
   */
  public function getRiskRegister(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);

      if ($tenantId === NULL) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant ID is required.'],
        ], 400);
      }

      $risks = $this->isoRisk->getRiskRegister($tenantId);
      $highRisks = $this->isoRisk->getHighRisks($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'risks' => $risks,
          'total' => count($risks),
          'high_risk_count' => count($highRisks),
        ],
        'meta' => ['generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to retrieve risk register.'],
      ], 500);
    }
  }

  /**
   * Creates or updates a risk assessment.
   *
   * POST /api/v1/compliance/risk-assessment
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request with JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the created/updated risk.
   */
  public function createRiskAssessment(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'INVALID_BODY', 'message' => 'Request body must be valid JSON.'],
        ], 400);
      }

      $tenantId = $this->resolveTenantId($request) ?? ($data['tenant_id'] ?? NULL);

      if ($tenantId === NULL) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant ID is required.'],
        ], 400);
      }

      // Validate required fields.
      $required = ['asset', 'threat', 'vulnerability', 'likelihood', 'impact'];
      foreach ($required as $field) {
        if (empty($data[$field])) {
          return new JsonResponse([
            'success' => FALSE,
            'error' => ['code' => 'MISSING_FIELD', 'message' => sprintf('Field "%s" is required.', $field)],
          ], 400);
        }
      }

      $likelihood = max(1, min(5, (int) $data['likelihood']));
      $impact = max(1, min(5, (int) $data['impact']));
      $riskCalc = $this->isoRisk->calculateRiskScore($likelihood, $impact);

      $storage = $this->entityTypeManager->getStorage('risk_assessment');
      $values = [
        'tenant_id' => (int) $tenantId,
        'asset' => (string) $data['asset'],
        'threat' => (string) $data['threat'],
        'vulnerability' => (string) $data['vulnerability'],
        'likelihood' => $likelihood,
        'impact' => $impact,
        'risk_score' => $riskCalc['score'],
        'risk_level' => $riskCalc['level'],
        'treatment' => $data['treatment'] ?? 'mitigate',
        'residual_risk' => (int) ($data['residual_risk'] ?? 0),
        'mitigation_plan' => $data['mitigation_plan'] ?? '',
        'status' => $data['status'] ?? 'open',
      ];

      $entity = $storage->create($values);
      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $entity->id(),
          'risk_score' => $riskCalc['score'],
          'risk_level' => $riskCalc['level'],
        ],
        'meta' => ['created_at' => date('c')],
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to create risk assessment.'],
      ], 500);
    }
  }

  /**
   * Returns ENS measures status.
   *
   * GET /api/v1/compliance/ens/measures
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with ENS measures.
   */
  public function getEnsMeasures(Request $request): JsonResponse {
    try {
      $measures = $this->ensCompliance->getMediaMeasures();
      $summary = $this->ensCompliance->getComplianceSummary();

      // Enrich with assessment status.
      $enriched = [];
      foreach ($measures as $measureId => $measure) {
        $assessment = $this->ensCompliance->assessMeasure($measureId);
        $enriched[] = array_merge($measure, [
          'platform_mapping' => $assessment['platform_mapping'],
          'status' => $assessment['status'],
        ]);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'measures' => $enriched,
          'summary' => $summary,
          'total' => count($enriched),
        ],
        'meta' => ['generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to retrieve ENS measures.'],
      ], 500);
    }
  }

  /**
   * Seeds default ENS measures for a tenant.
   *
   * POST /api/v1/compliance/ens/seed
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with seed results.
   */
  public function seedEnsMeasures(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE) ?? [];
      $tenantId = $this->resolveTenantId($request) ?? ($data['tenant_id'] ?? NULL);

      if ($tenantId === NULL) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant ID is required.'],
        ], 400);
      }

      $created = $this->ensCompliance->seedDefaultMeasures((int) $tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'measures_created' => $created,
          'tenant_id' => (int) $tenantId,
        ],
        'meta' => ['seeded_at' => date('c')],
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to seed ENS measures.'],
      ], 500);
    }
  }

  /**
   * Returns SOC 2 control mapping.
   *
   * GET /api/v1/compliance/soc2/controls
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with SOC 2 control mapping.
   */
  public function getSoc2Controls(Request $request): JsonResponse {
    try {
      $mapping = $this->soc2Mapper->getControlMapping();
      $gaps = $this->soc2Mapper->getComplianceGaps();

      // Calculate summary.
      $totalControls = count($mapping);
      $satisfied = 0;
      $partial = 0;

      foreach ($mapping as $control) {
        if ($control['status'] === 'satisfied') {
          $satisfied++;
        }
        elseif ($control['status'] === 'partial') {
          $partial++;
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'controls' => array_values($mapping),
          'gaps' => $gaps,
          'summary' => [
            'total_controls' => $totalControls,
            'satisfied' => $satisfied,
            'partial' => $partial,
            'gaps' => count($gaps),
            'readiness_score' => $totalControls > 0
              ? (int) round(($satisfied / $totalControls) * 100)
              : 0,
          ],
        ],
        'meta' => ['generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to retrieve SOC 2 controls.'],
      ], 500);
    }
  }

  /**
   * Generates a compliance report for a specific framework.
   *
   * GET /api/v1/compliance/report/{framework}
   *
   * @param string $framework
   *   The framework code (soc2, iso27001, ens, gdpr).
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the compliance report.
   */
  public function getComplianceReport(string $framework, Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);

      if ($tenantId === NULL) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant ID is required.'],
        ], 400);
      }

      $report = $this->complianceTracker->generateAuditReport($framework, $tenantId);

      // Enrich with multi-framework mappings.
      foreach ($report['controls'] as &$control) {
        $crossMapping = $this->complianceTracker->getMultiFrameworkMapping($control['control_id']);
        $control['cross_framework'] = $crossMapping['mappings'];
      }
      unset($control);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $report,
        'meta' => ['generated_at' => date('c'), 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to generate compliance report.'],
      ], 500);
    }
  }

  /**
   * Resolves the tenant ID from context or request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return int|null
   *   The resolved tenant ID, or NULL.
   */
  protected function resolveTenantId(Request $request): ?int {
    $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
    return $tenantId !== NULL ? (int) $tenantId : NULL;
  }

}
