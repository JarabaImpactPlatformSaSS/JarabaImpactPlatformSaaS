<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class CouponRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['code'] = $this->t('Codigo');
    $header['discount'] = $this->t('Descuento');
    $header['uses'] = $this->t('Usos');
    $header['valid_until'] = $this->t('Valido hasta');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'active' => $this->t('Activo'),
      'inactive' => $this->t('Inactivo'),
      'expired' => $this->t('Expirado'),
    ];

    $type_labels = [
      'percentage' => '%',
      'fixed_amount' => ' EUR',
      'free_shipping' => '',
    ];

    $status = $entity->get('status')->value;
    $discount_type = $entity->get('discount_type')->value;
    $discount_value = (float) $entity->get('discount_value')->value;

    if ($discount_type === 'free_shipping') {
      $discount_display = $this->t('Envio gratis');
    }
    elseif ($discount_type === 'percentage') {
      $discount_display = $discount_value . '%';
    }
    else {
      $discount_display = number_format($discount_value, 2, ',', '.') . ' EUR';
    }

    $max_uses = (int) $entity->get('max_uses')->value;
    $current_uses = (int) $entity->get('current_uses')->value;
    $uses_display = $max_uses > 0 ? "$current_uses / $max_uses" : "$current_uses / " . $this->t('ilimitado');

    $valid_until = $entity->get('valid_until')->value;
    $valid_display = $valid_until ? date('d/m/Y', strtotime($valid_until)) : $this->t('Sin limite');

    $row['code'] = $entity->get('code')->value;
    $row['discount'] = $discount_display;
    $row['uses'] = $uses_display;
    $row['valid_until'] = $valid_display;
    $row['status'] = $status_labels[$status] ?? $status;
    return $row + parent::buildRow($entity);
  }

}
