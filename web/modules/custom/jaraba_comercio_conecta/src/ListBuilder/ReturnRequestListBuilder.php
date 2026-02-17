<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ReturnRequestListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['order'] = $this->t('Pedido');
    $header['reason'] = $this->t('Motivo');
    $header['status'] = $this->t('Estado');
    $header['refund'] = $this->t('Reembolso');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'requested' => $this->t('Solicitada'),
      'approved' => $this->t('Aprobada'),
      'rejected' => $this->t('Rechazada'),
      'returned' => $this->t('Devuelto'),
      'refunded' => $this->t('Reembolsado'),
    ];

    $reason_labels = [
      'defective' => $this->t('Defectuoso'),
      'wrong_item' => $this->t('Incorrecto'),
      'not_as_described' => $this->t('No coincide'),
      'changed_mind' => $this->t('Cambio de opinion'),
      'other' => $this->t('Otro'),
    ];

    $status = $entity->get('status')->value;
    $reason = $entity->get('reason')->value;

    $order_number = '';
    $order_ref = $entity->get('order_id')->entity;
    if ($order_ref) {
      $order_number = $order_ref->get('order_number')->value;
    }

    $row['order'] = $order_number;
    $row['reason'] = $reason_labels[$reason] ?? $reason;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['refund'] = number_format((float) $entity->get('refund_amount')->value, 2, ',', '.') . ' EUR';
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
