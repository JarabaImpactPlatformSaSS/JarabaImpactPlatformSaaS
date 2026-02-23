<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Vertical Retention Profile entities.
 */
class VerticalRetentionProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['vertical_id'] = $this->t('Vertical ID');
    $header['label'] = $this->t('Label');
    $header['max_inactivity'] = $this->t('Max Inactivity');
    $header['signals_count'] = $this->t('Signals');
    $header['status'] = $this->t('Status');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface $entity */
    $row['vertical_id'] = [
      'data' => ['#markup' => '<code>' . $entity->getVerticalId() . '</code>'],
    ];
    $row['label'] = $entity->getLabel();
    $row['max_inactivity'] = (string) $this->t('@days days', ['@days' => $entity->getMaxInactivityDays()]);

    $signalsCount = count($entity->getChurnRiskSignals());
    $row['signals_count'] = (string) $signalsCount;

    $statusColor = $entity->isActive() ? '#00A9A5' : '#6c757d';
    $statusLabel = $entity->isActive() ? (string) $this->t('Active') : (string) $this->t('Inactive');
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $statusColor . '; font-weight: bold;">' . $statusLabel . '</span>',
      ],
    ];

    $row['changed'] = \Drupal::service('date.formatter')
      ->format($entity->getChangedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

}
