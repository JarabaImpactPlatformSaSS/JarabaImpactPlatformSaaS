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

}
