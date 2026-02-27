<?php

declare(strict_types=1);

namespace Drupal\jaraba_support;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for Business Hours Schedule config entities.
 */
class BusinessHoursScheduleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['timezone'] = $this->t('Timezone');
    $header['active'] = $this->t('Active');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_support\Entity\BusinessHoursSchedule $entity */
    $row['label'] = $entity->label();
    $row['timezone'] = $entity->getTimezone();
    $row['active'] = $entity->status() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
