<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class PosConnectionListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['merchant'] = $this->t('Comerciante');
    $header['provider'] = $this->t('Proveedor');
    $header['status'] = $this->t('Estado');
    $header['last_sync_at'] = $this->t('Ultima Sync');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'active' => $this->t('Activa'),
      'inactive' => $this->t('Inactiva'),
      'error' => $this->t('Error'),
      'pending' => $this->t('Pendiente'),
    ];

    $status = $entity->get('status')->value;

    $merchant_name = '';
    $merchant_ref = $entity->get('merchant_id')->entity;
    if ($merchant_ref) {
      $merchant_name = $merchant_ref->get('business_name')->value ?? $merchant_ref->label();
    }

    $last_sync = $entity->get('last_sync_at')->value;

    $row['name'] = $entity->get('name')->value;
    $row['merchant'] = $merchant_name;
    $row['provider'] = $entity->get('provider')->value;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['last_sync_at'] = $last_sync ? date('d/m/Y H:i', $last_sync) : '-';
    return $row + parent::buildRow($entity);
  }

}
