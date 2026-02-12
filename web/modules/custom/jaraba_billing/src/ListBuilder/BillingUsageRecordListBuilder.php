<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros de uso en admin.
 */
class BillingUsageRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['metric_key'] = $this->t('Métrica');
    $header['tenant_id'] = $this->t('Tenant');
    $header['quantity'] = $this->t('Cantidad');
    $header['unit'] = $this->t('Unidad');
    $header['period_start'] = $this->t('Inicio');
    $header['source'] = $this->t('Origen');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $periodStart = $entity->get('period_start')->value;

    $row['metric_key'] = $entity->get('metric_key')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['quantity'] = $entity->get('quantity')->value ?? '0';
    $row['unit'] = $entity->get('unit')->value ?? '-';
    $row['period_start'] = $periodStart ? date('d/m/Y', (int) $periodStart) : '-';
    $row['source'] = $entity->get('source')->value ?? 'metering';
    return $row + parent::buildRow($entity);
  }

}
