<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_security_compliance\Service\AuditLogService;
use Drupal\jaraba_security_compliance\Service\ComplianceTrackerService;
use Drupal\jaraba_security_compliance\Service\PolicyEnforcerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller principal para el Dashboard de Seguridad y Compliance.
 *
 * Proporciona las vistas de:
 * - Dashboard general de seguridad (/seguridad/dashboard)
 * - Panel administrativo (/admin/structure/security-compliance)
 * - Auditoría (/seguridad/auditoria)
 * - SOC2 Readiness (/seguridad/soc2)
 */
class SecurityDashboardController extends ControllerBase {

  /**
   * Servicio de audit log.
   *
   * @var \Drupal\jaraba_security_compliance\Service\AuditLogService
   */
  protected AuditLogService $auditLog;

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
    AuditLogService $auditLog,
    ComplianceTrackerService $complianceTracker,
    PolicyEnforcerService $policyEnforcer,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->auditLog = $auditLog;
    $this->complianceTracker = $complianceTracker;
    $this->policyEnforcer = $policyEnforcer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_security_compliance.audit_log'),
      $container->get('jaraba_security_compliance.compliance_tracker'),
      $container->get('jaraba_security_compliance.policy_enforcer'),
    );
  }

  /**
   * Renders the admin overview page.
   *
   * @return array
   *   A render array for the admin security compliance overview.
   */
  public function adminOverview(): array {
    $complianceScore = $this->complianceTracker->getComplianceScore();
    $frameworks = $this->complianceTracker->getComplianceStatus();
    $violations = $this->policyEnforcer->getViolations();

    return [
      '#theme' => 'jaraba_security_dashboard',
      '#compliance_score' => $complianceScore,
      '#frameworks' => $frameworks,
      '#recent_events' => $this->getRecentAuditEvents(),
      '#active_policies' => $this->getActivePoliciesSummary(),
      '#stats' => [
        'violations' => count($violations),
        'audit_operational' => $this->auditLog->isOperational(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_security_compliance/security-dashboard',
        ],
        'drupalSettings' => [
          'securityDashboard' => [
            'refreshInterval' => 30000,
            'complianceScore' => $complianceScore,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 30,
      ],
    ];
  }

  /**
   * Renders the frontend security dashboard.
   *
   * @return array
   *   A render array for the security dashboard.
   */
  public function dashboard(): array {
    $complianceScore = $this->complianceTracker->getComplianceScore();
    $frameworks = $this->complianceTracker->getComplianceStatus();

    return [
      '#theme' => 'jaraba_security_dashboard',
      '#compliance_score' => $complianceScore,
      '#frameworks' => $frameworks,
      '#recent_events' => $this->getRecentAuditEvents(),
      '#active_policies' => $this->getActivePoliciesSummary(),
      '#stats' => [
        'compliance_score' => $complianceScore,
        'total_frameworks' => count($frameworks),
        'audit_operational' => $this->auditLog->isOperational(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_security_compliance/security-dashboard',
        ],
        'drupalSettings' => [
          'securityDashboard' => [
            'refreshInterval' => 30000,
            'complianceScore' => $complianceScore,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 30,
      ],
    ];
  }

  /**
   * Renders the audit trail page.
   *
   * @return array
   *   A render array for the audit trail.
   */
  public function auditTrail(): array {
    $events = $this->getRecentAuditEvents(50);

    return [
      '#theme' => 'jaraba_audit_trail',
      '#events' => $events,
      '#filters' => [
        'severities' => ['info', 'notice', 'warning', 'critical'],
        'date_range' => [
          'start' => date('Y-m-d', strtotime('-30 days')),
          'end' => date('Y-m-d'),
        ],
      ],
      '#total_count' => count($events),
      '#attached' => [
        'library' => [
          'jaraba_security_compliance/audit-trail',
        ],
        'drupalSettings' => [
          'auditTrail' => [
            'exportEndpoint' => '/api/v1/security/audit/export',
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 15,
      ],
    ];
  }

  /**
   * Renders the SOC2 readiness page.
   *
   * @return array
   *   A render array for SOC2 readiness.
   */
  public function soc2Readiness(): array {
    $assessment = $this->complianceTracker->runAssessment('soc2');
    $complianceStatus = $this->complianceTracker->getComplianceStatus();
    $soc2Status = $complianceStatus['soc2'] ?? [];

    $trustCriteria = $this->getSoc2TrustCriteria();

    return [
      '#theme' => 'jaraba_soc2_readiness',
      '#trust_criteria' => $trustCriteria,
      '#controls' => $assessment['details'] ?? [],
      '#evidence' => [],
      '#readiness_score' => $assessment['score'] ?? 0,
      '#attached' => [
        'library' => [
          'jaraba_security_compliance/soc2-readiness',
        ],
        'drupalSettings' => [
          'soc2Readiness' => [
            'score' => $assessment['score'] ?? 0,
            'controlsEvaluated' => $assessment['controls_evaluated'] ?? 0,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Retrieves recent audit events.
   *
   * @param int $limit
   *   Maximum number of events to return.
   *
   * @return array
   *   Array of recent audit event data.
   */
  protected function getRecentAuditEvents(int $limit = 20): array {
    $events = [];

    try {
      $storage = $this->entityTypeManager->getStorage('security_audit_log');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, $limit);
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          /** @var \Drupal\jaraba_security_compliance\Entity\SecurityAuditLog $entity */
          $actor = $entity->get('actor_id')->entity;
          $created = (int) $entity->get('created')->value;

          $events[] = [
            'id' => $entity->id(),
            'event_type' => $entity->getEventType(),
            'severity' => $entity->getSeverity(),
            'actor' => $actor ? $actor->getDisplayName() : $this->t('System'),
            'ip_address' => $entity->getIpAddress(),
            'target_type' => $entity->get('target_type')->value ?? '',
            'target_id' => $entity->get('target_id')->value ?? '',
            'timestamp' => $created,
            'timestamp_formatted' => $created
              ? \Drupal::service('date.formatter')->format($created, 'short')
              : '',
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger('jaraba_security_compliance')->warning(
        'Failed to load audit log events: @error',
        ['@error' => $e->getMessage()],
      );
    }

    return $events;
  }

  /**
   * Gets a summary of active security policies.
   *
   * @return array
   *   Array of active policy summaries.
   */
  protected function getActivePoliciesSummary(): array {
    $policies = [];

    try {
      $activePolicies = $this->policyEnforcer->getActivePolicies();
      foreach ($activePolicies as $policy) {
        /** @var \Drupal\jaraba_security_compliance\Entity\SecurityPolicy $policy */
        $policies[] = [
          'id' => $policy->id(),
          'name' => $policy->getName(),
          'type' => $policy->getPolicyType(),
          'version' => $policy->getVersion(),
          'status' => $policy->getPolicyStatus(),
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger('jaraba_security_compliance')->warning(
        'Failed to load active policies: @error',
        ['@error' => $e->getMessage()],
      );
    }

    return $policies;
  }

  /**
   * Returns SOC2 Trust Service Criteria definitions.
   *
   * @return array
   *   Array of trust criteria categories.
   */
  protected function getSoc2TrustCriteria(): array {
    return [
      'security' => [
        'label' => $this->t('Security (Common Criteria)'),
        'description' => $this->t('Protection of information and systems from unauthorized access.'),
        'criteria' => ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9'],
      ],
      'availability' => [
        'label' => $this->t('Availability'),
        'description' => $this->t('System accessibility for operation and use.'),
        'criteria' => ['A1'],
      ],
      'processing_integrity' => [
        'label' => $this->t('Processing Integrity'),
        'description' => $this->t('System processing is complete, valid, accurate, and authorized.'),
        'criteria' => ['PI1'],
      ],
      'confidentiality' => [
        'label' => $this->t('Confidentiality'),
        'description' => $this->t('Information designated as confidential is protected.'),
        'criteria' => ['C1'],
      ],
      'privacy' => [
        'label' => $this->t('Privacy'),
        'description' => $this->t('Personal information is collected, used, retained, and disclosed in conformity.'),
        'criteria' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8'],
      ],
    ];
  }

}
