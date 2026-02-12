<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de políticas de seguridad con badges de estado y tipo.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de SecurityPolicy
 * en /admin/content/security-policies.
 *
 * LOGICA:
 * Muestra: nombre, tipo de política, versión, estado, tenant, operaciones.
 *
 * Colores de estado:
 * - draft: gris (#6c757d)
 * - active: verde (#198754)
 * - archived: naranja (#fd7e14)
 */
class SecurityPolicyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['policy_type'] = $this->t('Tipo');
    $header['version'] = $this->t('Versión');
    $header['policy_status'] = $this->t('Estado');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_security_compliance\Entity\SecurityPolicy $entity */

    // Badge de tipo de política.
    $typeColors = [
      'access_control' => '#0d6efd',
      'data_protection' => '#6f42c1',
      'incident_response' => '#fd7e14',
      'encryption' => '#20c997',
      'retention' => '#d63384',
    ];
    $typeLabels = [
      'access_control' => $this->t('Control de Acceso'),
      'data_protection' => $this->t('Protección de Datos'),
      'incident_response' => $this->t('Respuesta a Incidentes'),
      'encryption' => $this->t('Cifrado'),
      'retention' => $this->t('Retención'),
    ];
    $policyType = $entity->getPolicyType();
    $typeColor = $typeColors[$policyType] ?? '#6c757d';
    $typeLabel = $typeLabels[$policyType] ?? $policyType;

    // Badge de estado.
    $statusColors = [
      'draft' => '#6c757d',
      'active' => '#198754',
      'archived' => '#fd7e14',
    ];
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activa'),
      'archived' => $this->t('Archivada'),
    ];
    $status = $entity->getPolicyStatus();
    $statusColor = $statusColors[$status] ?? '#6c757d';
    $statusLabel = $statusLabels[$status] ?? $status;

    // Tenant.
    $tenant = $entity->get('tenant_id')->entity;
    $tenantLabel = $tenant ? $tenant->label() : $this->t('(Global)');

    $row['name'] = $entity->label();
    $row['policy_type'] = [
      'data' => [
        '#markup' => '<span style="background:' . $typeColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $typeLabel . '</span>',
      ],
    ];
    $row['version'] = $entity->getVersion();
    $row['policy_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['tenant_id'] = $tenantLabel;

    return $row + parent::buildRow($entity);
  }

}
