<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de evaluaciones de compliance con badges de estado.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de ComplianceAssessment
 * en /admin/content/compliance-assessments.
 *
 * LOGICA:
 * Muestra: framework, control ID, control name, assessment status (badge),
 * tenant, evaluador, operaciones.
 *
 * Colores de estado:
 * - pass: verde (#198754)
 * - fail: rojo (#dc3545)
 * - warning: naranja (#fd7e14)
 * - not_assessed: gris (#6c757d)
 */
class ComplianceAssessmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['framework'] = $this->t('Marco');
    $header['control_id'] = $this->t('Control ID');
    $header['control_name'] = $this->t('Control');
    $header['assessment_status'] = $this->t('Estado');
    $header['tenant_id'] = $this->t('Tenant');
    $header['assessed_by'] = $this->t('Evaluador');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_security_compliance\Entity\ComplianceAssessment $entity */

    // Marco normativo.
    $frameworkLabels = [
      'soc2' => 'SOC 2',
      'iso27001' => 'ISO 27001',
      'ens' => 'ENS',
      'gdpr' => 'GDPR',
    ];
    $framework = $entity->getFramework();
    $frameworkLabel = $frameworkLabels[$framework] ?? $framework;

    // Badge de estado con color.
    $statusColors = [
      'pass' => '#198754',
      'fail' => '#dc3545',
      'warning' => '#fd7e14',
      'not_assessed' => '#6c757d',
    ];
    $statusLabels = [
      'pass' => $this->t('Cumple'),
      'fail' => $this->t('No Cumple'),
      'warning' => $this->t('Advertencia'),
      'not_assessed' => $this->t('No Evaluado'),
    ];
    $status = $entity->getAssessmentStatus();
    $statusColor = $statusColors[$status] ?? '#6c757d';
    $statusLabel = $statusLabels[$status] ?? $status;

    // Tenant.
    $tenant = $entity->get('tenant_id')->entity;
    $tenantLabel = $tenant ? $tenant->label() : $this->t('(Global)');

    // Evaluador.
    $assessor = $entity->get('assessed_by')->entity;
    $assessorLabel = $assessor ? $assessor->getDisplayName() : $this->t('N/A');

    $row['framework'] = $frameworkLabel;
    $row['control_id'] = [
      'data' => [
        '#markup' => '<code>' . htmlspecialchars($entity->getControlId()) . '</code>',
      ],
    ];
    $row['control_name'] = $entity->getControlName();
    $row['assessment_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['tenant_id'] = $tenantLabel;
    $row['assessed_by'] = $assessorLabel;

    return $row + parent::buildRow($entity);
  }

}
