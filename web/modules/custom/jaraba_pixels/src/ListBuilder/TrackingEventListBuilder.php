<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de eventos de seguimiento en admin.
 */
class TrackingEventListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['event_name'] = $this->t('Nombre del Evento');
    $header['event_category'] = $this->t('Categoría');
    $header['visitor_id'] = $this->t('Visitor ID');
    $header['is_conversion'] = $this->t('Conversión');
    $header['sent_server_side'] = $this->t('Server-Side');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['event_name'] = $entity->get('event_name')->value ?? '-';
    $row['event_category'] = $entity->get('event_category')->value ?? '-';
    $row['visitor_id'] = $entity->get('visitor_id')->value ?? '-';
    $row['is_conversion'] = $entity->get('is_conversion')->value ? $this->t('Sí') : $this->t('No');
    $row['sent_server_side'] = $entity->get('sent_server_side')->value ? $this->t('Sí') : $this->t('No');
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    return $row + parent::buildRow($entity);
  }

}
