<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ProductMetricSnapshot.
 */
class ProductMetricSnapshotListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['snapshot_date'] = $this->t('Fecha');
    $header['vertical'] = $this->t('Vertical');
    $header['activation_rate'] = $this->t('Activacion');
    $header['retention_d30_rate'] = $this->t('Retencion D30');
    $header['nps_score'] = $this->t('NPS');
    $header['monthly_churn_rate'] = $this->t('Churn');
    $header['kill_criteria_triggered'] = $this->t('Kill?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\ProductMetricSnapshot $entity */
    $row = [];
    $row['snapshot_date'] = $entity->get('snapshot_date')->value ?? '-';
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $activationRate = (float) ($entity->get('activation_rate')->value ?? 0);
    $row['activation_rate'] = round($activationRate * 100, 1) . '%';
    $retentionRate = (float) ($entity->get('retention_d30_rate')->value ?? 0);
    $row['retention_d30_rate'] = round($retentionRate * 100, 1) . '%';
    $row['nps_score'] = (string) round((float) ($entity->get('nps_score')->value ?? 0), 1);
    $churnRate = (float) ($entity->get('monthly_churn_rate')->value ?? 0);
    $row['monthly_churn_rate'] = round($churnRate * 100, 1) . '%';
    $killTriggered = (bool) ($entity->get('kill_criteria_triggered')->value ?? FALSE);
    $row['kill_criteria_triggered'] = $killTriggered ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
