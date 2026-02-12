<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista administrativa de Resellers con badges de estado y modelo.
 *
 * PROPOSITO:
 * Renderiza la tabla de resellers en /admin/config/resellers.
 *
 * LOGICA:
 * Muestra: nombre, empresa, email, comision, estado (badge color),
 * modelo de revenue share y operaciones.
 */
class ResellerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['company_name'] = $this->t('Empresa');
    $header['contact_email'] = $this->t('Email');
    $header['commission_rate'] = $this->t('Comision (%)');
    $header['status_reseller'] = $this->t('Estado');
    $header['revenue_share_model'] = $this->t('Modelo Revenue');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\Reseller $entity */

    // Badge de estado con colores.
    $statusColors = [
      'active' => '#43A047',
      'suspended' => '#E53935',
      'pending' => '#FF8C42',
    ];
    $statusLabels = [
      'active' => $this->t('Activo'),
      'suspended' => $this->t('Suspendido'),
      'pending' => $this->t('Pendiente'),
    ];
    $status = $entity->get('status_reseller')->value ?? 'pending';
    $statusColor = $statusColors[$status] ?? '#6C757D';
    $statusLabel = $statusLabels[$status] ?? $status;

    // Badge de modelo de revenue share.
    $modelLabels = [
      'percentage' => $this->t('Porcentaje'),
      'flat_fee' => $this->t('Tarifa fija'),
      'tiered' => $this->t('Escalonado'),
    ];
    $model = $entity->get('revenue_share_model')->value ?? 'percentage';
    $modelLabel = $modelLabels[$model] ?? $model;

    $row['name'] = $entity->label();
    $row['company_name'] = $entity->get('company_name')->value ?? '';
    $row['contact_email'] = $entity->get('contact_email')->value ?? '';
    $row['commission_rate'] = number_format((float) ($entity->get('commission_rate')->value ?? 0), 2, ',', '.') . '%';
    $row['status_reseller'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['revenue_share_model'] = $modelLabel;

    return $row + parent::buildRow($entity);
  }

}
