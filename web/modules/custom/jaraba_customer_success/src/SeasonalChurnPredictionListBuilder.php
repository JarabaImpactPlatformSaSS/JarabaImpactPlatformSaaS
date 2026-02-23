<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Seasonal Churn Prediction entities.
 */
class SeasonalChurnPredictionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['vertical'] = $this->t('Vertical');
    $header['month'] = $this->t('Month');
    $header['base_prob'] = $this->t('Base Prob.');
    $header['adjustment'] = $this->t('Adjustment');
    $header['adjusted_prob'] = $this->t('Adjusted');
    $header['urgency'] = $this->t('Urgency');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface $entity */
    $tenantRef = $entity->get('tenant_id')->entity;
    $row['tenant'] = $tenantRef ? $tenantRef->label() : $entity->getTenantId();
    $row['vertical'] = $entity->getVerticalId();
    $row['month'] = $entity->getPredictionMonth();
    $row['base_prob'] = round($entity->getBaseProbability() * 100) . '%';

    $adj = $entity->getSeasonalAdjustment();
    $adjColor = $adj > 0 ? '#DC3545' : ($adj < 0 ? '#00A9A5' : '#6c757d');
    $adjSign = $adj > 0 ? '+' : '';
    $row['adjustment'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $adjColor . ';">' . $adjSign . round($adj * 100) . '%</span>',
      ],
    ];

    $row['adjusted_prob'] = round($entity->getAdjustedProbability() * 100) . '%';

    $urgency = $entity->getInterventionUrgency();
    $urgencyColors = [
      'none' => '#6c757d',
      'low' => '#00A9A5',
      'medium' => '#FFB84D',
      'high' => '#FF8C42',
      'critical' => '#DC3545',
    ];
    $urgencyColor = $urgencyColors[$urgency] ?? '#6c757d';
    $row['urgency'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $urgencyColor . '; font-weight: bold;">' . ucfirst($urgency) . '</span>',
      ],
    ];

    $row['created'] = \Drupal::service('date.formatter')
      ->format((int) $entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
