<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class FlashOfferListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['merchant'] = $this->t('Comercio');
    $header['discount'] = $this->t('Descuento');
    $header['start_time'] = $this->t('Inicio');
    $header['end_time'] = $this->t('Fin');
    $header['claims'] = $this->t('Canjes');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'scheduled' => $this->t('Programada'),
      'active' => $this->t('Activa'),
      'paused' => $this->t('Pausada'),
      'expired' => $this->t('Expirada'),
      'cancelled' => $this->t('Cancelada'),
    ];

    $status = $entity->get('status')->value;

    $merchant_name = '';
    $merchant_ref = $entity->get('merchant_id')->entity;
    if ($merchant_ref) {
      $merchant_name = $merchant_ref->get('business_name')->value ?? $merchant_ref->label();
    }

    $discount_type = $entity->get('discount_type')->value;
    $discount_value = (float) $entity->get('discount_value')->value;
    $discount_display = $discount_type === 'percentage'
      ? $discount_value . '%'
      : number_format($discount_value, 2, ',', '.') . ' EUR';

    $current_claims = (int) $entity->get('current_claims')->value;
    $max_claims = (int) $entity->get('max_claims')->value;
    $claims_display = $max_claims > 0 ? $current_claims . '/' . $max_claims : (string) $current_claims;

    $start_time = $entity->get('start_time')->value;
    $end_time = $entity->get('end_time')->value;

    $row['title'] = $entity->get('title')->value;
    $row['merchant'] = $merchant_name;
    $row['discount'] = $discount_display;
    $row['start_time'] = $start_time ? date('d/m/Y H:i', (int) $start_time) : '-';
    $row['end_time'] = $end_time ? date('d/m/Y H:i', (int) $end_time) : '-';
    $row['claims'] = $claims_display;
    $row['status'] = $status_labels[$status] ?? $status;
    return $row + parent::buildRow($entity);
  }

}
