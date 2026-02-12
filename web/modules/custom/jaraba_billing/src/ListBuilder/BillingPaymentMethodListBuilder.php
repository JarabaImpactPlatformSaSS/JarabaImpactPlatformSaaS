<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de métodos de pago en admin.
 */
class BillingPaymentMethodListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['type'] = $this->t('Tipo');
    $header['tenant_id'] = $this->t('Tenant');
    $header['card_brand'] = $this->t('Marca');
    $header['card_last4'] = $this->t('Últimos 4');
    $header['is_default'] = $this->t('Predeterminado');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'card' => $this->t('Tarjeta'),
      'sepa_debit' => $this->t('SEPA'),
      'bank_transfer' => $this->t('Transferencia'),
    ];
    $status_labels = [
      'active' => $this->t('Activo'),
      'expired' => $this->t('Expirado'),
      'detached' => $this->t('Desvinculado'),
    ];

    $type = $entity->get('type')->value;
    $status = $entity->get('status')->value;

    $row['type'] = $type_labels[$type] ?? $type;
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['card_brand'] = $entity->get('card_brand')->value ?? '-';
    $row['card_last4'] = $entity->get('card_last4')->value ?? '-';
    $row['is_default'] = $entity->get('is_default')->value ? $this->t('Sí') : $this->t('No');
    $row['status'] = $status_labels[$status] ?? $status;
    return $row + parent::buildRow($entity);
  }

}
