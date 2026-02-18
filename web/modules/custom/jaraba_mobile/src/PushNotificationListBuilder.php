<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for PushNotification entities.
 *
 * Shows title, recipient, channel, status, sent_at.
 * Read-only entity — no edit operations.
 */
class PushNotificationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Title');
    $header['recipient'] = $this->t('Recipient');
    $header['channel'] = $this->t('Channel');
    $header['status'] = $this->t('Status');
    $header['sent_at'] = $this->t('Sent At');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_mobile\Entity\PushNotification $entity */
    $row['title'] = $entity->getTitle();

    $recipient = $entity->get('recipient_id')->entity;
    $row['recipient'] = $recipient
      ? $recipient->getDisplayName()
      : $this->t('User #@id', ['@id' => $entity->get('recipient_id')->target_id]);

    $row['channel'] = $entity->getChannel();
    $row['status'] = $entity->getStatusLabel();

    $sent_at = $entity->get('sent_at')->value;
    $row['sent_at'] = $sent_at
      ? \Drupal::service('date.formatter')->format(strtotime($sent_at), 'short')
      : '—';

    return $row + parent::buildRow($entity);
  }

}
