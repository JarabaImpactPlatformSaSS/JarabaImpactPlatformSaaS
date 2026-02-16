<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de SLA Records en admin.
 *
 * Muestra tenant, periodo, uptime, target, downtime, crédito y acciones.
 */
class SlaRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_id'] = $this->t('Tenant');
    $header['period_start'] = $this->t('Inicio periodo');
    $header['period_end'] = $this->t('Fin periodo');
    $header['uptime_percentage'] = $this->t('Uptime (%)');
    $header['target_percentage'] = $this->t('Target (%)');
    $header['downtime_minutes'] = $this->t('Downtime (min)');
    $header['credit_applied'] = $this->t('Crédito aplicado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $period_start = $entity->get('period_start')->value;
    $period_end = $entity->get('period_end')->value;

    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['period_start'] = $period_start ? date('d/m/Y', (int) $period_start) : '-';
    $row['period_end'] = $period_end ? date('d/m/Y', (int) $period_end) : '-';
    $row['uptime_percentage'] = $entity->get('uptime_percentage')->value ?? '-';
    $row['target_percentage'] = $entity->get('target_percentage')->value ?? '-';
    $row['downtime_minutes'] = $entity->get('downtime_minutes')->value ?? '0';
    $row['credit_applied'] = $entity->get('credit_applied')->value ? $this->t('Sí') : $this->t('No');
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
