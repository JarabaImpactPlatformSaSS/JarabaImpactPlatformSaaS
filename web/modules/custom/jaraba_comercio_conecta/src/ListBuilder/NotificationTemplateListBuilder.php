<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class NotificationTemplateListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['channel'] = $this->t('Canal');
    $header['is_active'] = $this->t('Activa');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $channel_labels = [
      'email' => $this->t('Email'),
      'push' => $this->t('Push'),
      'sms' => $this->t('SMS'),
      'in_app' => $this->t('In-App'),
    ];

    $channel = $entity->get('channel')->value;
    $is_active = (bool) $entity->get('is_active')->value;

    $row['name'] = $entity->get('name')->value;
    $row['channel'] = $channel_labels[$channel] ?? $channel;
    $row['is_active'] = $is_active ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
