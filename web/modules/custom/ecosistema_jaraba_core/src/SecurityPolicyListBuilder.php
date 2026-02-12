<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de políticas de seguridad con badges de estado y tipo.
 *
 * PROPÓSITO:
 * Renderiza la tabla administrativa de SecurityPolicy
 * en /admin/config/security-policies.
 *
 * LÓGICA:
 * Muestra: nombre, ámbito, tipo de política, modo de aplicación,
 * estado activo (badge color), operaciones.
 */
class SecurityPolicyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['scope'] = $this->t('Ámbito');
    $header['policy_type'] = $this->t('Tipo');
    $header['enforcement'] = $this->t('Aplicación');
    $header['active'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\SecurityPolicy $entity */

    // Badge de ámbito.
    $scopeColors = [
      'global' => '#233D63',
      'tenant' => '#00A9A5',
    ];
    $scope = $entity->get('scope')->value ?? 'global';
    $scopeColor = $scopeColors[$scope] ?? '#6C757D';
    $scopeLabel = ucfirst($scope);

    // Badge de tipo de política.
    $typeColors = [
      'password' => '#0d6efd',
      'session' => '#6f42c1',
      'access' => '#fd7e14',
      'data' => '#20c997',
    ];
    $policyType = $entity->get('policy_type')->value ?? '';
    $typeColor = $typeColors[$policyType] ?? '#6C757D';
    $typeLabel = ucfirst($policyType);

    // Badge de modo de aplicación.
    $enforcementColors = [
      'enforce' => '#dc3545',
      'audit' => '#fd7e14',
      'disabled' => '#6C757D',
    ];
    $enforcement = $entity->get('enforcement')->value ?? 'audit';
    $enforcementColor = $enforcementColors[$enforcement] ?? '#6C757D';
    $enforcementLabel = ucfirst($enforcement);

    // Badge de estado activo/inactivo.
    $isActive = (bool) $entity->get('active')->value;
    $statusColor = $isActive ? '#00A9A5' : '#DC3545';
    $statusLabel = $isActive ? $this->t('Activa') : $this->t('Inactiva');

    $row['name'] = $entity->label();
    $row['scope'] = [
      'data' => [
        '#markup' => '<span style="background:' . $scopeColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $scopeLabel . '</span>',
      ],
    ];
    $row['policy_type'] = [
      'data' => [
        '#markup' => '<span style="background:' . $typeColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $typeLabel . '</span>',
      ],
    ];
    $row['enforcement'] = [
      'data' => [
        '#markup' => '<span style="background:' . $enforcementColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $enforcementLabel . '</span>',
      ],
    ];
    $row['active'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
