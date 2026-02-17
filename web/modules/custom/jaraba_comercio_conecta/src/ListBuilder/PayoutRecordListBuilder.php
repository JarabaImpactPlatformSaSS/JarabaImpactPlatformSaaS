<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class PayoutRecordListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['merchant'] = $this->t('Comerciante');
    $header['payout_amount'] = $this->t('Importe Bruto');
    $header['commission'] = $this->t('Comision');
    $header['net'] = $this->t('Neto');
    $header['status'] = $this->t('Estado');
    $header['period'] = $this->t('Periodo');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'processing' => $this->t('Procesando'),
      'paid' => $this->t('Pagado'),
      'failed' => $this->t('Fallido'),
      'cancelled' => $this->t('Cancelado'),
    ];

    $status = $entity->get('status')->value;

    $merchant_name = '';
    $merchant_ref = $entity->get('merchant_id')->entity;
    if ($merchant_ref) {
      $merchant_name = $merchant_ref->get('business_name')->value ?? $merchant_ref->label();
    }

    $payout = (float) $entity->get('payout_amount')->value;
    $commission = (float) $entity->get('commission')->value;
    $net = (float) $entity->get('net')->value;

    $row['merchant'] = $merchant_name;
    $row['payout_amount'] = number_format($payout, 2, ',', '.') . ' EUR';
    $row['commission'] = number_format($commission, 2, ',', '.') . ' EUR';
    $row['net'] = number_format($net, 2, ',', '.') . ' EUR';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['period'] = $entity->get('period')->value ?? '-';
    return $row + parent::buildRow($entity);
  }

}
