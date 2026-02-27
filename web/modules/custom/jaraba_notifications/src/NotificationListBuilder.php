<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad Notification en admin.
 */
class NotificationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['type'] = $this->t('Tipo');
    $header['title'] = $this->t('Titulo');
    $header['owner'] = $this->t('Usuario');
    $header['read_status'] = $this->t('Leida');
    $header['created'] = $this->t('Creada');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['id'] = $entity->id();
    $row['type'] = $entity->getNotificationType();
    $row['title'] = $entity->getTitle();
    $row['owner'] = $entity->getOwner()?->getDisplayName() ?? '-';
    $row['read_status'] = $entity->isRead() ? $this->t('Si') : $this->t('No');
    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short',
    );
    return $row + parent::buildRow($entity);
  }

}
