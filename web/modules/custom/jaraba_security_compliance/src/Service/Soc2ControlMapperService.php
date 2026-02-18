<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de mapeo de controles SOC 2 Trust Service Criteria.
 *
 * Mapea las funcionalidades existentes de la plataforma Ecosistema Jaraba
 * a los criterios SOC 2 (CC1-CC9, A1, C1, PI1, P1-P8), evalua el estado
 * de cada control y detecta gaps de cumplimiento.
 */
class Soc2ControlMapperService {

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Returns the complete SOC 2 control mapping.
   *
   * Maps platform features to SOC 2 Trust Service Criteria.
   *
   * @return array
   *   Array keyed by control ID with:
   *   - id (string): Control identifier (e.g. CC6.1).
   *   - name (string): Control name.
   *   - category (string): Trust service category.
   *   - description (string): Control description.
   *   - platform_features (array): Platform features that satisfy this control.
   *   - status (string): satisfied, partial, or gap.
   *   - evidence (string): Evidence description.
   */
  public function getControlMapping(): array {
    return [
      'CC1.1' => [
        'id' => 'CC1.1',
        'name' => 'Control Environment - COSO Principle 1',
        'category' => 'security',
        'description' => 'The entity demonstrates a commitment to integrity and ethical values.',
        'platform_features' => [
          'Security policies module',
          'Code of conduct documentation',
          'SecurityPolicy entity with versioning',
        ],
        'status' => 'satisfied',
        'evidence' => 'SecurityPolicy entity with draft/active/archived lifecycle and version tracking.',
      ],
      'CC2.1' => [
        'id' => 'CC2.1',
        'name' => 'Communication and Information',
        'category' => 'security',
        'description' => 'The entity internally communicates information necessary for internal control.',
        'platform_features' => [
          'Notification service',
          'Tenant knowledge base',
          'Security dashboard',
        ],
        'status' => 'satisfied',
        'evidence' => 'NotificationService, TenantKnowledgeManager, and SecurityDashboardController provide information channels.',
      ],
      'CC3.1' => [
        'id' => 'CC3.1',
        'name' => 'Risk Assessment',
        'category' => 'security',
        'description' => 'The entity specifies objectives with sufficient clarity to identify risks.',
        'platform_features' => [
          'RiskAssessment entity (ISO 27001)',
          'ComplianceAssessment tracking',
          'PolicyEnforcer violations detection',
        ],
        'status' => 'satisfied',
        'evidence' => 'RiskAssessment entity with full risk register, ComplianceTrackerService multi-framework assessment.',
      ],
      'CC4.1' => [
        'id' => 'CC4.1',
        'name' => 'Monitoring Activities',
        'category' => 'security',
        'description' => 'The entity selects and develops ongoing monitoring activities.',
        'platform_features' => [
          'SecurityAuditLog immutable events',
          'Compliance dashboard real-time metrics',
          'Self-healing service monitoring',
        ],
        'status' => 'satisfied',
        'evidence' => 'SecurityAuditLog with continuous event capture, ComplianceTrackerService with periodic assessments.',
      ],
      'CC5.1' => [
        'id' => 'CC5.1',
        'name' => 'Control Activities',
        'category' => 'security',
        'description' => 'The entity selects and develops control activities that mitigate risks.',
        'platform_features' => [
          'SecurityHeadersSubscriber',
          'CSRF protection on mutations',
          'Input validation and sanitization',
        ],
        'status' => 'satisfied',
        'evidence' => 'SecurityHeadersSubscriber enforces CSP, HSTS, X-Frame-Options; CSRF tokens on all POST endpoints.',
      ],
      'CC6.1' => [
        'id' => 'CC6.1',
        'name' => 'Logical and Physical Access Controls',
        'category' => 'security',
        'description' => 'Logical access security over protected information assets.',
        'platform_features' => [
          'Multi-factor authentication (MFA)',
          'Role-based access control (RBAC)',
          'Drupal permission system',
          'Entity access control handlers',
        ],
        'status' => 'satisfied',
        'evidence' => 'RBAC via Drupal roles/permissions, per-entity AccessControlHandler classes, MFA integration.',
      ],
      'CC6.2' => [
        'id' => 'CC6.2',
        'name' => 'Prior to Issuing System Credentials',
        'category' => 'security',
        'description' => 'New access is registered, authorized, and provisioned.',
        'platform_features' => [
          'User provisioning workflow',
          'SSO integration',
          'Tenant-based user management',
        ],
        'status' => 'satisfied',
        'evidence' => 'User creation through admin interface with role assignment, SSO via OAuth, tenant membership via Group module.',
      ],
      'CC6.3' => [
        'id' => 'CC6.3',
        'name' => 'Role-Based Authorization',
        'category' => 'security',
        'description' => 'Access is authorized based on roles and responsibilities.',
        'platform_features' => [
          'Role-based permissions per module',
          'Tenant-specific permissions',
          'Feature gate services per vertical',
        ],
        'status' => 'satisfied',
        'evidence' => 'Granular permissions per module (30+ permissions), FeatureGateService per vertical, Group-based tenant isolation.',
      ],
      'CC6.6' => [
        'id' => 'CC6.6',
        'name' => 'System Boundaries',
        'category' => 'security',
        'description' => 'The entity implements controls to prevent unauthorized access.',
        'platform_features' => [
          'SecurityHeadersSubscriber (CSP, HSTS)',
          '.htaccess security rules',
          'Nginx configuration hardening',
        ],
        'status' => 'satisfied',
        'evidence' => 'SecurityHeadersSubscriber with CSP/HSTS/X-Frame-Options, .htaccess with access restrictions, nginx hardening.',
      ],
      'CC7.1' => [
        'id' => 'CC7.1',
        'name' => 'Vulnerability Management',
        'category' => 'security',
        'description' => 'The entity uses detection and monitoring procedures to identify changes.',
        'platform_features' => [
          'OWASP ZAP scanning (CI/CD)',
          'PHPStan static analysis',
          'Security scan workflow',
        ],
        'status' => 'satisfied',
        'evidence' => 'security-scan.yml workflow with OWASP ZAP, phpstan-security.neon rules, CI/CD pipeline checks.',
      ],
      'CC7.2' => [
        'id' => 'CC7.2',
        'name' => 'Security Event Monitoring',
        'category' => 'security',
        'description' => 'The entity monitors system components for anomalies.',
        'platform_features' => [
          'SecurityAuditLog (immutable audit trail)',
          'Heatmap tracking module',
          'Analytics tracker module',
        ],
        'status' => 'satisfied',
        'evidence' => 'SecurityAuditLog with event_type, severity, actor, IP; jaraba_heatmap real-time tracking; jaraba_analytics event capture.',
      ],
      'CC7.3' => [
        'id' => 'CC7.3',
        'name' => 'Security Incident Response',
        'category' => 'security',
        'description' => 'The entity evaluates and responds to security events.',
        'platform_features' => [
          'SecurityPolicy incident_response type',
          'AuditLogService event escalation',
          'Self-healing service',
        ],
        'status' => 'partial',
        'evidence' => 'SecurityPolicy incident_response policies, SelfHealingService auto-recovery. Formal IRP documentation recommended.',
      ],
      'CC8.1' => [
        'id' => 'CC8.1',
        'name' => 'Change Management',
        'category' => 'security',
        'description' => 'Changes to infrastructure and software are controlled.',
        'platform_features' => [
          'Git version control',
          'CI/CD pipelines (GitHub Actions)',
          'Deploy scripts with rollback',
          'Preflight checks',
        ],
        'status' => 'satisfied',
        'evidence' => 'ci.yml, deploy.yml, deploy-staging.yml, deploy-production.yml workflows; golive scripts with preflight checks and rollback.',
      ],
      'CC9.1' => [
        'id' => 'CC9.1',
        'name' => 'Risk Mitigation',
        'category' => 'security',
        'description' => 'The entity identifies and assesses risks from business partners.',
        'platform_features' => [
          'RiskAssessment entity with treatment strategies',
          'Vendor assessment through ComplianceAssessment',
        ],
        'status' => 'satisfied',
        'evidence' => 'RiskAssessment entity with accept/mitigate/transfer/avoid strategies and residual risk tracking.',
      ],
      'A1.1' => [
        'id' => 'A1.1',
        'name' => 'System Availability',
        'category' => 'availability',
        'description' => 'The entity maintains system availability in accordance with its objectives.',
        'platform_features' => [
          'SelfHealingService health checks',
          'Performance monitoring module',
          'AIOps production scripts',
        ],
        'status' => 'satisfied',
        'evidence' => 'SelfHealingService with automated recovery, jaraba_performance monitoring, aiops-production.sh health probes.',
      ],
      'A1.2' => [
        'id' => 'A1.2',
        'name' => 'Recovery Objectives',
        'category' => 'availability',
        'description' => 'The entity authorizes, designs, and tests recovery procedures.',
        'platform_features' => [
          'Backup configuration',
          'Disaster recovery plan',
          'Rollback scripts',
        ],
        'status' => 'partial',
        'evidence' => '03_rollback.sh provides deployment rollback. Database backup and full DR plan documentation recommended.',
      ],
      'C1.1' => [
        'id' => 'C1.1',
        'name' => 'Confidentiality Commitments',
        'category' => 'confidentiality',
        'description' => 'Confidential information is protected as committed.',
        'platform_features' => [
          'SecurityPolicy data_protection type',
          'DataRetentionService anonymization',
          'Encryption policies',
        ],
        'status' => 'satisfied',
        'evidence' => 'DataRetentionService with anonymizeUser(), SecurityPolicy encryption policies, tenant data isolation via Group module.',
      ],
      'PI1.1' => [
        'id' => 'PI1.1',
        'name' => 'Processing Integrity',
        'category' => 'processing_integrity',
        'description' => 'System processing is complete, valid, accurate, timely, and authorized.',
        'platform_features' => [
          'Input validation on all forms',
          'CSRF protection on mutations',
          'Queue workers for async processing',
        ],
        'status' => 'satisfied',
        'evidence' => 'Form validation via Drupal Form API, CSRF on all POST/PATCH/DELETE, QueueWorker for reliable async processing.',
      ],
      'P1.1' => [
        'id' => 'P1.1',
        'name' => 'Privacy Notice',
        'category' => 'privacy',
        'description' => 'Privacy notice regarding collection, use, retention, and disposal.',
        'platform_features' => [
          'Cookie consent integration',
          'Privacy policy page',
          'GDPR compliance tracking',
        ],
        'status' => 'satisfied',
        'evidence' => 'GDPR framework in ComplianceAssessment, cookie consent, privacy policy content management.',
      ],
      'P4.1' => [
        'id' => 'P4.1',
        'name' => 'Access to Personal Information',
        'category' => 'privacy',
        'description' => 'Personal information access, correction, and deletion requests.',
        'platform_features' => [
          'DataRetentionService anonymizeUser()',
          'User data export capability',
          'Candidate CV builder data management',
        ],
        'status' => 'satisfied',
        'evidence' => 'DataRetentionService::anonymizeUser() for right to erasure, CvBuilderService manages personal data with user control.',
      ],
      'P6.1' => [
        'id' => 'P6.1',
        'name' => 'Data Retention and Disposal',
        'category' => 'privacy',
        'description' => 'Personal information is retained and disposed of according to policy.',
        'platform_features' => [
          'DataRetentionService applyRetentionPolicies()',
          'Configurable retention periods',
          'Automated cleanup via cron',
        ],
        'status' => 'satisfied',
        'evidence' => 'DataRetentionService with configurable audit_log_days/assessment_days, automated cron-based cleanup.',
      ],
    ];
  }

  /**
   * Assesses a specific SOC 2 control.
   *
   * @param string $controlId
   *   The control ID (e.g. 'CC6.1').
   *
   * @return array
   *   Assessment result with:
   *   - control_id (string): The control identifier.
   *   - found (bool): Whether the control exists in the mapping.
   *   - status (string): satisfied, partial, or gap.
   *   - name (string): Control name.
   *   - platform_features (array): Features that satisfy this control.
   *   - evidence (string): Evidence description.
   */
  public function assessControl(string $controlId): array {
    $mapping = $this->getControlMapping();

    if (!isset($mapping[$controlId])) {
      return [
        'control_id' => $controlId,
        'found' => FALSE,
        'status' => 'gap',
        'name' => 'Unknown control',
        'platform_features' => [],
        'evidence' => '',
      ];
    }

    $control = $mapping[$controlId];

    return [
      'control_id' => $controlId,
      'found' => TRUE,
      'status' => $control['status'],
      'name' => $control['name'],
      'platform_features' => $control['platform_features'],
      'evidence' => $control['evidence'],
    ];
  }

  /**
   * Returns controls that are not yet fully satisfied.
   *
   * @return array
   *   Array of controls with status 'partial' or 'gap', each with:
   *   - id (string): Control identifier.
   *   - name (string): Control name.
   *   - status (string): partial or gap.
   *   - description (string): What is missing.
   *   - recommendation (string): Recommended action.
   */
  public function getComplianceGaps(): array {
    $mapping = $this->getControlMapping();
    $gaps = [];

    foreach ($mapping as $controlId => $control) {
      if ($control['status'] !== 'satisfied') {
        $gaps[] = [
          'id' => $controlId,
          'name' => $control['name'],
          'status' => $control['status'],
          'description' => $control['description'],
          'recommendation' => $this->getRecommendation($controlId, $control['status']),
        ];
      }
    }

    return $gaps;
  }

  /**
   * Gets a recommendation for a specific gap.
   *
   * @param string $controlId
   *   The control identifier.
   * @param string $status
   *   The current status (partial or gap).
   *
   * @return string
   *   A recommendation string.
   */
  protected function getRecommendation(string $controlId, string $status): string {
    $recommendations = [
      'CC7.3' => 'Create a formal Incident Response Plan (IRP) document and conduct tabletop exercises.',
      'A1.2' => 'Document full DR/BCP plan with RTO/RPO targets; automate database backups with verified restores.',
    ];

    return $recommendations[$controlId] ?? sprintf(
      'Review control %s and implement missing features to achieve full compliance.',
      $controlId,
    );
  }

}
