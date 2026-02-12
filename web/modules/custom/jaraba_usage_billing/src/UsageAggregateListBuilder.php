<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de agregados de uso en admin.
 */
class UsageAggregateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['metric_name'] = $this->t('Métrica');
    $header['period_type'] = $this->t('Periodo');
    $header['period_start'] = $this->t('Inicio');
    $header['period_end'] = $this->t('Fin');
    $header['total_quantity'] = $this->t('Total');
    $header['event_count'] = $this->t('Eventos');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['metric_name'] = $entity->get('metric_name')->value ?? '-';
    $row['period_type'] = $entity->get('period_type')->value ?? '-';

    $start = $entity->get('period_start')->value;
    $row['period_start'] = $start ? date('Y-m-d H:i', (int) $start) : '-';

    $end = $entity->get('period_end')->value;
    $row['period_end'] = $end ? date('Y-m-d H:i', (int) $end) : '-';

    $row['total_quantity'] = $entity->get('total_quantity')->value ?? '0';
    $row['event_count'] = $entity->get('event_count')->value ?? '0';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';

    return $row + parent::buildRow($entity);
  }

}
