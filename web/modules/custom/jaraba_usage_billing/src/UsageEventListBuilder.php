<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de eventos de uso en admin.
 */
class UsageEventListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['event_type'] = $this->t('Tipo de Evento');
    $header['metric_name'] = $this->t('Métrica');
    $header['quantity'] = $this->t('Cantidad');
    $header['unit'] = $this->t('Unidad');
    $header['tenant_id'] = $this->t('Tenant');
    $header['recorded_at'] = $this->t('Registrado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['event_type'] = $entity->get('event_type')->value ?? '-';
    $row['metric_name'] = $entity->get('metric_name')->value ?? '-';
    $row['quantity'] = $entity->get('quantity')->value ?? '0';
    $row['unit'] = $entity->get('unit')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';

    $recorded = $entity->get('recorded_at')->value;
    $row['recorded_at'] = $recorded ? date('Y-m-d H:i', (int) $recorded) : '-';

    return $row + parent::buildRow($entity);
  }

}
