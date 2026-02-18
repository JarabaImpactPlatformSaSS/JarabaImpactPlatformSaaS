<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for MobileDevice entities.
 *
 * Shows device_model, platform, user, last_active, is_active.
 */
class MobileDeviceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['device_model'] = $this->t('Device Model');
    $header['platform'] = $this->t('Platform');
    $header['user'] = $this->t('User');
    $header['last_active'] = $this->t('Last Active');
    $header['is_active'] = $this->t('Active');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_mobile\Entity\MobileDevice $entity */
    $row['device_model'] = $entity->get('device_model')->value ?: $this->t('Unknown');
    $row['platform'] = $entity->get('platform')->value ?: '—';

    $owner = $entity->getOwner();
    $row['user'] = $owner ? $owner->getDisplayName() : $this->t('User #@id', ['@id' => $entity->getOwnerId()]);

    $last_active = $entity->get('last_active')->value;
    $row['last_active'] = $last_active
      ? \Drupal::service('date.formatter')->format(strtotime($last_active), 'short')
      : '—';

    $row['is_active'] = $entity->isActive() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
