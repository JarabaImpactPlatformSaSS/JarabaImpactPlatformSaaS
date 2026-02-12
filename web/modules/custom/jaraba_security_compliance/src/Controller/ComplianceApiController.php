<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_security_compliance\Service\ComplianceTrackerService;
use Drupal\jaraba_security_compliance\Service\PolicyEnforcerService;
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
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ComplianceTrackerService $complianceTracker,
    PolicyEnforcerService $policyEnforcer,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->complianceTracker = $complianceTracker;
    $this->policyEnforcer = $policyEnforcer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_security_compliance.compliance_tracker'),
      $container->get('jaraba_security_compliance.policy_enforcer'),
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
      $tenantId = $request->query->get('tenant_id');
      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;

      $status = $this->complianceTracker->getComplianceStatus($tenantIdInt);
      $score = $this->complianceTracker->getComplianceScore($tenantIdInt);
      $violations = $this->policyEnforcer->getViolations($tenantIdInt);

      return new JsonResponse([
        'status' => 'success',
        'data' => [
          'compliance_score' => $score,
          'frameworks' => $status,
          'violations' => $violations,
          'generated_at' => date('c'),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Failed to retrieve compliance status.')->render(),
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
      $tenantId = $request->query->get('tenant_id');
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
        'status' => 'success',
        'data' => [
          'policies' => $policyData,
          'total' => count($policyData),
          'generated_at' => date('c'),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Failed to retrieve security policies.')->render(),
      ], 500);
    }
  }

}
