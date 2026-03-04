<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder para ActivationCriteriaConfig.
 */
class ActivationCriteriaListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Nombre');
    $header['vertical'] = $this->t('Vertical');
    $header['activation_threshold'] = $this->t('Activacion');
    $header['retention_d30_threshold'] = $this->t('Retencion D30');
    $header['nps_threshold'] = $this->t('NPS');
    $header['churn_threshold'] = $this->t('Churn');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\ActivationCriteriaConfig $entity */
    $row = [];
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['vertical'] = $entity->getVertical();
    $row['activation_threshold'] = ($entity->getActivationThreshold() * 100) . '%';
    $row['retention_d30_threshold'] = ($entity->getRetentionD30Threshold() * 100) . '%';
    $row['nps_threshold'] = (string) $entity->getNpsThreshold();
    $row['churn_threshold'] = ($entity->getChurnThreshold() * 100) . '%';
    return $row + parent::buildRow($entity);
  }

}
