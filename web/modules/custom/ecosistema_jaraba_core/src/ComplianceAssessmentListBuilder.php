<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de evaluaciones de compliance con badges de estado y barra de puntuación.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de ComplianceAssessment
 * en /admin/config/compliance-assessments.
 *
 * LOGICA:
 * Muestra: tenant, marco normativo, fecha de evaluación, estado (badge color),
 * puntuación global (indicador color), evaluador, operaciones.
 *
 * Colores de estado:
 * - pending: gris (#6c757d)
 * - in_progress: azul (#0d6efd)
 * - completed: verde (#198754)
 * - remediation: naranja (#fd7e14)
 *
 * Colores de puntuación:
 * - >= 80: verde (#198754)
 * - >= 60: amarillo (#ffc107)
 * - < 60: rojo (#dc3545)
 */
class ComplianceAssessmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_id'] = $this->t('Tenant');
    $header['framework'] = $this->t('Marco Normativo');
    $header['assessment_date'] = $this->t('Fecha de Evaluación');
    $header['status'] = $this->t('Estado');
    $header['overall_score'] = $this->t('Puntuación');
    $header['assessor'] = $this->t('Evaluador');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\ComplianceAssessment $entity */

    // Tenant asociado.
    $tenant = $entity->get('tenant_id')->entity;
    $tenantLabel = $tenant ? $tenant->label() : $this->t('(Sin tenant)');

    // Marco normativo.
    $frameworkLabels = [
      'soc2' => 'SOC 2',
      'iso27001' => 'ISO 27001',
      'ens' => 'ENS',
      'gdpr' => 'GDPR',
    ];
    $framework = $entity->getFramework();
    $frameworkLabel = $frameworkLabels[$framework] ?? $framework;

    // Fecha de evaluación formateada.
    $assessmentDate = $entity->get('assessment_date')->value ?? '';
    if ($assessmentDate) {
      // El campo datetime almacena en formato ISO, mostramos como está.
      $assessmentDate = substr($assessmentDate, 0, 10);
    }

    // Badge de estado con color.
    $statusColors = [
      'pending' => '#6c757d',
      'in_progress' => '#0d6efd',
      'completed' => '#198754',
      'remediation' => '#fd7e14',
    ];
    $statusLabels = [
      'pending' => $this->t('Pendiente'),
      'in_progress' => $this->t('En Progreso'),
      'completed' => $this->t('Completada'),
      'remediation' => $this->t('En Remediación'),
    ];
    $status = $entity->getStatus();
    $statusColor = $statusColors[$status] ?? '#6c757d';
    $statusLabel = $statusLabels[$status] ?? $status;

    // Puntuación global con indicador de color.
    $score = $entity->getOverallScore();
    $scoreDisplay = '';
    if ($score !== NULL) {
      if ($score >= 80) {
        $scoreColor = '#198754';
      }
      elseif ($score >= 60) {
        $scoreColor = '#ffc107';
      }
      else {
        $scoreColor = '#dc3545';
      }
      $scoreDisplay = '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' . $scoreColor . ';margin-right:6px;"></span>' . $score . '/100';
    }

    $row['tenant_id'] = $tenantLabel;
    $row['framework'] = $frameworkLabel;
    $row['assessment_date'] = $assessmentDate;
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['overall_score'] = [
      'data' => [
        '#markup' => $scoreDisplay,
      ],
    ];
    $row['assessor'] = $entity->getAssessor();

    return $row + parent::buildRow($entity);
  }

}
