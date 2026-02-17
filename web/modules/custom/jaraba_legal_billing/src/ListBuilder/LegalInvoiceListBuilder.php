<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de facturas legales en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave de las facturas.
 */
class LegalInvoiceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['invoice_number'] = $this->t('Numero');
    $header['client_name'] = $this->t('Cliente');
    $header['total'] = $this->t('Total');
    $header['status'] = $this->t('Estado');
    $header['issue_date'] = $this->t('Emision');
    $header['due_date'] = $this->t('Vencimiento');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'issued' => $this->t('Emitida'),
      'sent' => $this->t('Enviada'),
      'viewed' => $this->t('Vista'),
      'paid' => $this->t('Pagada'),
      'partial' => $this->t('Pago parcial'),
      'overdue' => $this->t('Vencida'),
      'refunded' => $this->t('Reembolsada'),
      'cancelled' => $this->t('Cancelada'),
      'written_off' => $this->t('Incobrable'),
    ];

    $status = $entity->get('status')->value;
    $total = (float) ($entity->get('total')->value ?? 0);

    $row['invoice_number'] = $entity->get('invoice_number')->value ?? '-';
    $row['client_name'] = $entity->get('client_name')->value ?? '';
    $row['total'] = number_format($total, 2, ',', '.') . ' EUR';
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['issue_date'] = $entity->get('issue_date')->value ?? '-';
    $row['due_date'] = $entity->get('due_date')->value ?? '-';

    return $row + parent::buildRow($entity);
  }

}
