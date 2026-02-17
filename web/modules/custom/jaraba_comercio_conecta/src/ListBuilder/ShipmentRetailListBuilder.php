<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ShipmentRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['tracking_number'] = $this->t('Tracking');
    $header['order'] = $this->t('Pedido');
    $header['carrier'] = $this->t('Transportista');
    $header['status'] = $this->t('Estado');
    $header['estimated_delivery'] = $this->t('Entrega Estimada');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'picked_up' => $this->t('Recogido'),
      'in_transit' => $this->t('En transito'),
      'out_for_delivery' => $this->t('En reparto'),
      'delivered' => $this->t('Entregado'),
      'returned' => $this->t('Devuelto'),
      'failed' => $this->t('Fallido'),
    ];

    $status = $entity->get('status')->value;

    $order_label = '';
    $order_ref = $entity->get('order_id')->entity;
    if ($order_ref) {
      $order_label = $order_ref->get('order_number')->value ?? '#' . $order_ref->id();
    }

    $carrier_label = '';
    $carrier_ref = $entity->get('carrier_id')->entity;
    if ($carrier_ref) {
      $carrier_label = $carrier_ref->get('carrier_name')->value ?? '';
    }

    $estimated = $entity->get('estimated_delivery')->value;

    $row['tracking_number'] = $entity->get('tracking_number')->value ?? '-';
    $row['order'] = $order_label;
    $row['carrier'] = $carrier_label;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['estimated_delivery'] = $estimated ? date('d/m/Y', strtotime($estimated)) : '-';
    return $row + parent::buildRow($entity);
  }

}
