<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class OrderRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['order_number'] = $this->t('Pedido');
    $header['customer'] = $this->t('Cliente');
    $header['total'] = $this->t('Total');
    $header['status'] = $this->t('Estado');
    $header['payment_status'] = $this->t('Pago');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'pending' => $this->t('Pendiente'),
      'confirmed' => $this->t('Confirmado'),
      'processing' => $this->t('En preparacion'),
      'shipped' => $this->t('Enviado'),
      'delivered' => $this->t('Entregado'),
      'cancelled' => $this->t('Cancelado'),
      'refunded' => $this->t('Reembolsado'),
    ];

    $payment_labels = [
      'pending' => $this->t('Pendiente'),
      'paid' => $this->t('Pagado'),
      'refunded' => $this->t('Reembolsado'),
      'failed' => $this->t('Fallido'),
    ];

    $status = $entity->get('status')->value;
    $payment = $entity->get('payment_status')->value;

    $customer_name = '';
    $customer_ref = $entity->get('customer_uid')->entity;
    if ($customer_ref) {
      $customer_name = $customer_ref->getDisplayName();
    }

    $row['order_number'] = $entity->get('order_number')->value;
    $row['customer'] = $customer_name;
    $row['total'] = number_format((float) $entity->get('total')->value, 2, ',', '.') . ' EUR';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['payment_status'] = $payment_labels[$payment] ?? $payment;
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
