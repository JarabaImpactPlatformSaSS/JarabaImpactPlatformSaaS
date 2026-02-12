<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PushSubscription entities.
 *
 * Displays push subscriptions in the admin content listing
 * at /admin/content/push-subscriptions.
 */
class PushSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user_id'] = $this->t('User');
    $header['endpoint'] = $this->t('Endpoint');
    $header['subscription_status'] = $this->t('Status');
    $header['tenant_id'] = $this->t('Tenant');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $endpoint = $entity->get('endpoint')->value ?? '';
    $row['user_id'] = $entity->get('user_id')->target_id ?? '-';
    $row['endpoint'] = mb_strlen($endpoint) > 60
      ? mb_substr($endpoint, 0, 60) . '...'
      : $endpoint;
    $row['subscription_status'] = $entity->get('subscription_status')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['created'] = $entity->get('created')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('created')->value, 'short')
      : '-';
    return $row + parent::buildRow($entity);
  }

}
