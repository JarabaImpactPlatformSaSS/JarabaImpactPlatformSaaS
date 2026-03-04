<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de pilot tenants en admin.
 *
 * ESTRUCTURA: Genera la tabla en /admin/content/pilot-tenants.
 */
class PilotTenantListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['pilot_program'] = $this->t('Programa');
    $header['tenant_id'] = $this->t('Tenant');
    $header['status'] = $this->t('Estado');
    $header['activation_score'] = $this->t('Activacion');
    $header['churn_risk'] = $this->t('Riesgo Churn');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $statusLabels = [
      'enrolled' => $this->t('Inscrito'),
      'active' => $this->t('Activo'),
      'paused' => $this->t('Pausado'),
      'converted' => $this->t('Convertido'),
      'abandoned' => $this->t('Abandonado'),
    ];

    $churnLabels = [
      'low' => $this->t('Bajo'),
      'medium' => $this->t('Medio'),
      'high' => $this->t('Alto'),
      'critical' => $this->t('Critico'),
    ];

    $status = $entity->get('status')->value ?? '';
    $churnRisk = $entity->get('churn_risk')->value ?? '';

    // Resolve pilot program label safely (LABEL-NULLSAFE-001).
    $programLabel = '-';
    $programRef = $entity->get('pilot_program')->entity;
    if ($programRef) {
      $programLabel = $programRef->label() ?? (string) $programRef->id();
    }

    // Resolve tenant label safely.
    $tenantLabel = '-';
    $tenantRef = $entity->get('tenant_id')->entity;
    if ($tenantRef) {
      $tenantLabel = $tenantRef->label() ?? (string) $tenantRef->id();
    }

    $row = [];
    $row['pilot_program'] = $programLabel;
    $row['tenant_id'] = $tenantLabel;
    $row['status'] = $statusLabels[$status] ?? ($status !== '' ? $status : '-');
    $row['activation_score'] = number_format((float) ($entity->get('activation_score')->value ?? 0), 1);
    $row['churn_risk'] = $churnLabels[$churnRisk] ?? ($churnRisk !== '' ? $churnRisk : '-');
    return $row + parent::buildRow($entity);
  }

}
