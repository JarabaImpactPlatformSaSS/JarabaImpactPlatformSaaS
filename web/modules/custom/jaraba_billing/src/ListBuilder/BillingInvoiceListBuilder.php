<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de facturas de billing en admin.
 */
class BillingInvoiceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['invoice_number'] = $this->t('Número');
    $header['tenant_id'] = $this->t('Tenant');
    $header['status'] = $this->t('Estado');
    $header['amount_due'] = $this->t('Importe');
    $header['currency'] = $this->t('Moneda');
    $header['due_date'] = $this->t('Vencimiento');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'open' => $this->t('Abierta'),
      'paid' => $this->t('Pagada'),
      'void' => $this->t('Anulada'),
      'uncollectible' => $this->t('Incobrable'),
    ];

    $status = $entity->get('status')->value;
    $dueDate = $entity->get('due_date')->value;

    $row['invoice_number'] = $entity->get('invoice_number')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['amount_due'] = $entity->get('amount_due')->value ?? '0.00';
    $row['currency'] = $entity->get('currency')->value ?? 'EUR';
    $row['due_date'] = $dueDate ? date('d/m/Y', strtotime($dueDate)) : '-';
    return $row + parent::buildRow($entity);
  }

}
