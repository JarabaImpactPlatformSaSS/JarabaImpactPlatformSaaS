<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PendingSyncAction entities.
 *
 * Displays pending background sync actions in the admin content listing
 * at /admin/content/pending-sync-actions.
 */
class PendingSyncActionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['action_type'] = $this->t('Action');
    $header['target_entity_type'] = $this->t('Entity Type');
    $header['target_entity_id'] = $this->t('Entity ID');
    $header['sync_status'] = $this->t('Status');
    $header['retry_count'] = $this->t('Retries');
    $header['user_id'] = $this->t('User');
    $header['created'] = $this->t('Queued');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['action_type'] = $entity->get('action_type')->value ?? '-';
    $row['target_entity_type'] = $entity->get('target_entity_type')->value ?? '-';
    $row['target_entity_id'] = $entity->get('target_entity_id')->value ?? '-';
    $row['sync_status'] = $entity->get('sync_status')->value ?? '-';
    $retryCount = $entity->get('retry_count')->value ?? 0;
    $maxRetries = $entity->get('max_retries')->value ?? 3;
    $row['retry_count'] = $retryCount . '/' . $maxRetries;
    $row['user_id'] = $entity->get('user_id')->target_id ?? '-';
    $row['created'] = $entity->get('created')->value
      ? \Drupal::service('date.formatter')->format((int) $entity->get('created')->value, 'short')
      : '-';
    return $row + parent::buildRow($entity);
  }

}
