<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de evaluaciones de riesgo ISO 27001 con badges de nivel y estado.
 *
 * Renderiza la tabla administrativa de RiskAssessment
 * en /admin/content/risk-assessments.
 *
 * Muestra: asset, threat, risk_level (badge), treatment, status (badge).
 *
 * Colores de risk_level:
 * - low: verde (#198754)
 * - medium: naranja (#fd7e14)
 * - high: rojo (#dc3545)
 * - critical: rojo oscuro (#842029)
 */
class RiskAssessmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['asset'] = $this->t('Activo');
    $header['threat'] = $this->t('Amenaza');
    $header['risk_level'] = $this->t('Nivel de Riesgo');
    $header['treatment'] = $this->t('Tratamiento');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_security_compliance\Entity\RiskAssessment $entity */

    // Badge de nivel de riesgo.
    $riskLevelColors = [
      'low' => '#198754',
      'medium' => '#fd7e14',
      'high' => '#dc3545',
      'critical' => '#842029',
    ];
    $riskLevelLabels = [
      'low' => $this->t('Bajo'),
      'medium' => $this->t('Medio'),
      'high' => $this->t('Alto'),
      'critical' => $this->t('Critico'),
    ];
    $riskLevel = $entity->getRiskLevel();
    $riskLevelColor = $riskLevelColors[$riskLevel] ?? '#6c757d';
    $riskLevelLabel = $riskLevelLabels[$riskLevel] ?? $riskLevel;

    // Badge de tratamiento.
    $treatmentLabels = [
      'accept' => $this->t('Aceptar'),
      'mitigate' => $this->t('Mitigar'),
      'transfer' => $this->t('Transferir'),
      'avoid' => $this->t('Evitar'),
    ];
    $treatment = $entity->getTreatment();
    $treatmentLabel = $treatmentLabels[$treatment] ?? $treatment;

    // Badge de estado.
    $statusColors = [
      'open' => '#0d6efd',
      'mitigating' => '#fd7e14',
      'accepted' => '#198754',
      'closed' => '#6c757d',
    ];
    $statusLabels = [
      'open' => $this->t('Abierto'),
      'mitigating' => $this->t('En Mitigacion'),
      'accepted' => $this->t('Aceptado'),
      'closed' => $this->t('Cerrado'),
    ];
    $status = $entity->getStatus();
    $statusColor = $statusColors[$status] ?? '#6c757d';
    $statusLabel = $statusLabels[$status] ?? $status;

    $row['asset'] = $entity->getAsset();
    $row['threat'] = $entity->getThreat();
    $row['risk_level'] = [
      'data' => [
        '#markup' => '<span style="background:' . $riskLevelColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $riskLevelLabel . '</span>',
      ],
    ];
    $row['treatment'] = $treatmentLabel;
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
