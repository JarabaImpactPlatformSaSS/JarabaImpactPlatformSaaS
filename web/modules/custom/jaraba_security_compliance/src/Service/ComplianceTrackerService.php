<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de compliance multi-framework.
 *
 * Proporciona métodos para consultar el estado de compliance
 * por framework (SOC2, ISO27001, ENS, GDPR), ejecutar evaluaciones
 * y calcular puntuaciones agregadas.
 */
class ComplianceTrackerService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\State\StateInterface $state
   *   El servicio de estado.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene el estado de compliance por framework.
   *
   * @param int|null $tenantId
   *   ID del tenant, o NULL para estado global.
   *
   * @return array
   *   Array keyed by framework con:
   *   - framework (string): Código del framework.
   *   - label (string): Nombre legible del framework.
   *   - total_controls (int): Total de controles evaluados.
   *   - passing (int): Controles que cumplen.
   *   - failing (int): Controles que no cumplen.
   *   - warnings (int): Controles con advertencia.
   *   - not_assessed (int): Controles sin evaluar.
   *   - score (int): Porcentaje de cumplimiento (0-100).
   */
  public function getComplianceStatus(?int $tenantId = NULL): array {
    $frameworks = [
      'soc2' => 'SOC 2 Type II',
      'iso27001' => 'ISO 27001:2022',
      'ens' => 'ENS',
      'gdpr' => 'GDPR / RGPD',
    ];

    $result = [];

    try {
      $storage = $this->entityTypeManager->getStorage('compliance_assessment_v2');

      foreach ($frameworks as $code => $label) {
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('framework', $code);

        if ($tenantId !== NULL) {
          $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        $assessments = !empty($ids) ? $storage->loadMultiple($ids) : [];

        $stats = [
          'framework' => $code,
          'label' => $label,
          'total_controls' => count($assessments),
          'passing' => 0,
          'failing' => 0,
          'warnings' => 0,
          'not_assessed' => 0,
          'score' => 0,
        ];

        foreach ($assessments as $assessment) {
          /** @var \Drupal\jaraba_security_compliance\Entity\ComplianceAssessment $assessment */
          $status = $assessment->getAssessmentStatus();
          match ($status) {
            'pass' => $stats['passing']++,
            'fail' => $stats['failing']++,
            'warning' => $stats['warnings']++,
            default => $stats['not_assessed']++,
          };
        }

        if ($stats['total_controls'] > 0) {
          $stats['score'] = (int) round(
            ($stats['passing'] / $stats['total_controls']) * 100,
          );
        }

        $result[$code] = $stats;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load compliance status: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Ejecuta una evaluación automatizada de un framework.
   *
   * @param string $framework
   *   Código del framework (soc2, iso27001, ens, gdpr).
   * @param int|null $tenantId
   *   ID del tenant, o NULL para evaluación global.
   *
   * @return array
   *   Resultado de la evaluación con:
   *   - framework (string): Código del framework evaluado.
   *   - controls_evaluated (int): Número de controles evaluados.
   *   - score (int): Puntuación resultante (0-100).
   *   - timestamp (int): Momento de la evaluación.
   *   - details (array): Detalle por control.
   */
  public function runAssessment(string $framework, ?int $tenantId = NULL): array {
    $result = [
      'framework' => $framework,
      'controls_evaluated' => 0,
      'score' => 0,
      'timestamp' => time(),
      'details' => [],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('compliance_assessment_v2');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('framework', $framework);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      $assessments = !empty($ids) ? $storage->loadMultiple($ids) : [];

      $passing = 0;
      foreach ($assessments as $assessment) {
        /** @var \Drupal\jaraba_security_compliance\Entity\ComplianceAssessment $assessment */
        $detail = [
          'control_id' => $assessment->getControlId(),
          'control_name' => $assessment->getControlName(),
          'status' => $assessment->getAssessmentStatus(),
        ];
        $result['details'][] = $detail;
        $result['controls_evaluated']++;

        if ($assessment->getAssessmentStatus() === 'pass') {
          $passing++;
        }
      }

      if ($result['controls_evaluated'] > 0) {
        $result['score'] = (int) round(
          ($passing / $result['controls_evaluated']) * 100,
        );
      }

      // Update state with last assessment timestamp.
      $this->state->set('jaraba_security_compliance.last_assessment', time());
      $this->state->set(
        'jaraba_security_compliance.last_assessment_' . $framework,
        $result,
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to run compliance assessment for @framework: @message', [
        '@framework' => $framework,
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Calcula la puntuación de compliance global.
   *
   * @param int|null $tenantId
   *   ID del tenant, o NULL para puntuación global.
   *
   * @return int
   *   Puntuación de compliance de 0 a 100.
   */
  public function getComplianceScore(?int $tenantId = NULL): int {
    try {
      $status = $this->getComplianceStatus($tenantId);
      $totalControls = 0;
      $totalPassing = 0;

      foreach ($status as $framework) {
        $totalControls += $framework['total_controls'];
        $totalPassing += $framework['passing'];
      }

      if ($totalControls === 0) {
        return 0;
      }

      return (int) round(($totalPassing / $totalControls) * 100);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to calculate compliance score: @message', [
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Gets the compliance score for a specific framework.
   *
   * @param string $framework
   *   The framework code (soc2, iso27001, ens, gdpr).
   * @param int|null $tenantId
   *   Optional tenant ID filter.
   *
   * @return float
   *   Compliance score as percentage (0.0-100.0).
   */
  public function getFrameworkScore(string $framework, ?int $tenantId = NULL): float {
    try {
      $status = $this->getComplianceStatus($tenantId);

      if (!isset($status[$framework])) {
        return 0.0;
      }

      return (float) $status[$framework]['score'];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get framework score for @framework: @message', [
        '@framework' => $framework,
        '@message' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Maps a single control to multiple compliance frameworks.
   *
   * Cross-references a control across SOC 2, ISO 27001, and ENS
   * to show how a single implementation satisfies multiple requirements.
   *
   * @param string $controlId
   *   The control identifier (can be from any framework).
   *
   * @return array
   *   Array with:
   *   - control_id (string): The input control ID.
   *   - mappings (array): Framework mappings, each with:
   *     - framework (string): Framework code.
   *     - control (string): Framework-specific control ID.
   *     - name (string): Control name.
   *     - description (string): Brief description.
   */
  public function getMultiFrameworkMapping(string $controlId): array {
    $crossMap = $this->getCrossFrameworkMappings();

    $result = [
      'control_id' => $controlId,
      'mappings' => [],
    ];

    // Search for the control in any framework column.
    foreach ($crossMap as $entry) {
      $found = FALSE;
      foreach (['soc2', 'iso27001', 'ens'] as $fw) {
        if (isset($entry[$fw]) && $entry[$fw] === $controlId) {
          $found = TRUE;
          break;
        }
      }

      if ($found) {
        if (!empty($entry['soc2'])) {
          $result['mappings'][] = [
            'framework' => 'soc2',
            'control' => $entry['soc2'],
            'name' => $entry['name_soc2'] ?? $entry['name'],
            'description' => $entry['description'] ?? '',
          ];
        }
        if (!empty($entry['iso27001'])) {
          $result['mappings'][] = [
            'framework' => 'iso27001',
            'control' => $entry['iso27001'],
            'name' => $entry['name_iso'] ?? $entry['name'],
            'description' => $entry['description'] ?? '',
          ];
        }
        if (!empty($entry['ens'])) {
          $result['mappings'][] = [
            'framework' => 'ens',
            'control' => $entry['ens'],
            'name' => $entry['name_ens'] ?? $entry['name'],
            'description' => $entry['description'] ?? '',
          ];
        }
        break;
      }
    }

    return $result;
  }

  /**
   * Generates an audit-ready compliance report for a framework.
   *
   * @param string $framework
   *   The framework code (soc2, iso27001, ens, gdpr).
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Audit report with:
   *   - framework (string): Framework code.
   *   - framework_label (string): Human-readable framework name.
   *   - tenant_id (int): Tenant ID.
   *   - generated_at (string): ISO 8601 timestamp.
   *   - summary (array): Score, total controls, passing, failing.
   *   - controls (array): Per-control details.
   *   - recommendations (array): Improvement recommendations.
   */
  public function generateAuditReport(string $framework, int $tenantId): array {
    $frameworkLabels = [
      'soc2' => 'SOC 2 Type II',
      'iso27001' => 'ISO 27001:2022',
      'ens' => 'ENS (RD 311/2022)',
      'gdpr' => 'GDPR / RGPD',
    ];

    $report = [
      'framework' => $framework,
      'framework_label' => $frameworkLabels[$framework] ?? $framework,
      'tenant_id' => $tenantId,
      'generated_at' => date('c'),
      'summary' => [
        'score' => 0,
        'total_controls' => 0,
        'passing' => 0,
        'failing' => 0,
        'warnings' => 0,
        'not_assessed' => 0,
      ],
      'controls' => [],
      'recommendations' => [],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('compliance_assessment_v2');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('framework', $framework)
        ->condition('tenant_id', $tenantId)
        ->sort('control_id');
      $ids = $query->execute();

      if (!empty($ids)) {
        $assessments = $storage->loadMultiple($ids);

        foreach ($assessments as $assessment) {
          /** @var \Drupal\jaraba_security_compliance\Entity\ComplianceAssessment $assessment */
          $status = $assessment->getAssessmentStatus();
          $controlDetail = [
            'control_id' => $assessment->getControlId(),
            'control_name' => $assessment->getControlName(),
            'status' => $status,
            'evidence_notes' => $assessment->getEvidenceNotes(),
            'assessed_at' => $assessment->get('assessed_at')->value ?? NULL,
          ];
          $report['controls'][] = $controlDetail;
          $report['summary']['total_controls']++;

          match ($status) {
            'pass' => $report['summary']['passing']++,
            'fail' => $report['summary']['failing']++,
            'warning' => $report['summary']['warnings']++,
            default => $report['summary']['not_assessed']++,
          };

          // Add recommendation for non-passing controls.
          if ($status === 'fail') {
            $report['recommendations'][] = [
              'control_id' => $assessment->getControlId(),
              'control_name' => $assessment->getControlName(),
              'priority' => 'high',
              'recommendation' => sprintf(
                'Control %s (%s) is non-compliant. Review and implement corrective actions.',
                $assessment->getControlId(),
                $assessment->getControlName(),
              ),
            ];
          }
          elseif ($status === 'warning') {
            $report['recommendations'][] = [
              'control_id' => $assessment->getControlId(),
              'control_name' => $assessment->getControlName(),
              'priority' => 'medium',
              'recommendation' => sprintf(
                'Control %s (%s) has warnings. Review evidence and address deficiencies.',
                $assessment->getControlId(),
                $assessment->getControlName(),
              ),
            ];
          }
        }

        if ($report['summary']['total_controls'] > 0) {
          $report['summary']['score'] = (int) round(
            ($report['summary']['passing'] / $report['summary']['total_controls']) * 100,
          );
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to generate audit report for @framework tenant @tenant: @message', [
        '@framework' => $framework,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $report;
  }

  /**
   * Returns the cross-framework control mapping table.
   *
   * Maps equivalent controls across SOC 2, ISO 27001, and ENS.
   *
   * @return array
   *   Array of mapping entries.
   */
  protected function getCrossFrameworkMappings(): array {
    return [
      [
        'name' => 'Multi-Factor Authentication',
        'soc2' => 'CC6.1',
        'iso27001' => 'A.9.4.2',
        'ens' => 'op.acc.5',
        'name_soc2' => 'Logical and Physical Access Controls',
        'name_iso' => 'Secure Log-on Procedures',
        'name_ens' => 'Mecanismo de Autenticacion (MFA)',
        'description' => 'Multi-factor authentication for system access.',
      ],
      [
        'name' => 'Access Control',
        'soc2' => 'CC6.3',
        'iso27001' => 'A.9.2.1',
        'ens' => 'op.acc.2',
        'name_soc2' => 'Role-Based Authorization',
        'name_iso' => 'User Registration and De-registration',
        'name_ens' => 'Requisitos de Acceso',
        'description' => 'Role-based access control and authorization.',
      ],
      [
        'name' => 'Audit Logging',
        'soc2' => 'CC7.2',
        'iso27001' => 'A.12.4.1',
        'ens' => 'op.exp.1',
        'name_soc2' => 'Security Event Monitoring',
        'name_iso' => 'Event Logging',
        'name_ens' => 'Registro de Actividad',
        'description' => 'Comprehensive security event logging and monitoring.',
      ],
      [
        'name' => 'Change Management',
        'soc2' => 'CC8.1',
        'iso27001' => 'A.12.1.2',
        'ens' => 'op.pl.2',
        'name_soc2' => 'Change Management',
        'name_iso' => 'Change Management',
        'name_ens' => 'Arquitectura de Seguridad',
        'description' => 'Controlled change management for infrastructure and software.',
      ],
      [
        'name' => 'Risk Assessment',
        'soc2' => 'CC3.1',
        'iso27001' => 'A.8.2.1',
        'ens' => 'op.pl.1',
        'name_soc2' => 'Risk Assessment',
        'name_iso' => 'Classification of Information',
        'name_ens' => 'Analisis de Riesgos',
        'description' => 'Systematic risk assessment and classification.',
      ],
      [
        'name' => 'Security Policy',
        'soc2' => 'CC1.1',
        'iso27001' => 'A.5.1.1',
        'ens' => 'org.1',
        'name_soc2' => 'Control Environment',
        'name_iso' => 'Policies for Information Security',
        'name_ens' => 'Politica de Seguridad',
        'description' => 'Documented and approved security policies.',
      ],
      [
        'name' => 'Incident Response',
        'soc2' => 'CC7.3',
        'iso27001' => 'A.16.1.1',
        'ens' => 'op.exp.2',
        'name_soc2' => 'Security Incident Response',
        'name_iso' => 'Responsibilities and Procedures',
        'name_ens' => 'Gestion de Incidentes',
        'description' => 'Incident response procedures and escalation.',
      ],
      [
        'name' => 'Data Protection',
        'soc2' => 'P6.1',
        'iso27001' => 'A.18.1.4',
        'ens' => 'mp.si.1',
        'name_soc2' => 'Data Retention and Disposal',
        'name_iso' => 'Privacy and Protection of PII',
        'name_ens' => 'Clasificacion de la Informacion',
        'description' => 'Data retention, disposal, and privacy protection.',
      ],
    ];
  }

}
