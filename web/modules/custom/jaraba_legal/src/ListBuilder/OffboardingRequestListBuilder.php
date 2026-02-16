<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de Offboarding Requests en admin.
 *
 * Muestra tenant, motivo, estado, periodo de gracia, completado y acciones.
 */
class OffboardingRequestListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_name'] = $this->t('Tenant');
    $header['reason'] = $this->t('Motivo');
    $header['status'] = $this->t('Estado');
    $header['grace_period_end'] = $this->t('Fin gracia');
    $header['completed_at'] = $this->t('Completado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $reason_labels = [
      'voluntary' => $this->t('Voluntaria'),
      'non_payment' => $this->t('Impago'),
      'aup_violation' => $this->t('Violación AUP'),
      'contract_end' => $this->t('Fin contrato'),
      'other' => $this->t('Otro'),
    ];

    $status_labels = [
      'requested' => $this->t('Solicitada'),
      'grace_period' => $this->t('Periodo gracia'),
      'export_pending' => $this->t('Export pendiente'),
      'export_complete' => $this->t('Export completada'),
      'data_deletion' => $this->t('Eliminación datos'),
      'completed' => $this->t('Completada'),
      'cancelled' => $this->t('Cancelada'),
    ];

    $reason = $entity->get('reason')->value;
    $status = $entity->get('status')->value;
    $grace_end = $entity->get('grace_period_end')->value;
    $completed_at = $entity->get('completed_at')->value;

    $row['tenant_name'] = $entity->get('tenant_name')->value ?? '-';
    $row['reason'] = $reason_labels[$reason] ?? $reason;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['grace_period_end'] = $grace_end ? date('d/m/Y', (int) $grace_end) : '-';
    $row['completed_at'] = $completed_at ? date('d/m/Y H:i', (int) $completed_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
