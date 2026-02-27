<?php

declare(strict_types=1);

namespace Drupal\jaraba_support;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for SLA Policy config entities.
 */
class SlaPolicyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['plan_tier'] = $this->t('Plan Tier');
    $header['priority'] = $this->t('Priority');
    $header['first_response'] = $this->t('First Response (h)');
    $header['resolution'] = $this->t('Resolution (h)');
    $header['business_hours'] = $this->t('Business Hours Only');
    $header['active'] = $this->t('Active');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_support\Entity\SlaPolicyInterface $entity */
    $row['label'] = $entity->label();
    $row['plan_tier'] = $entity->getPlanTier();
    $row['priority'] = $entity->getPriority();
    $row['first_response'] = $entity->getFirstResponseHours() . 'h';
    $row['resolution'] = $entity->getResolutionHours() . 'h';
    $row['business_hours'] = $entity->isBusinessHoursOnly() ? $this->t('Yes') : $this->t('No');
    $row['active'] = $entity->status() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
