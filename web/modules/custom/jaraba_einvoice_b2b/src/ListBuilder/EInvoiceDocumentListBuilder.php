<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for EInvoice Document entities.
 *
 * Displays documents in a sortable table with key columns:
 * invoice number, direction, format, buyer/seller, amounts,
 * delivery status, payment status, SPFE status, and created date.
 *
 * Spec: Doc 181, Section 2.1.
 */
class EInvoiceDocumentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['invoice_number'] = $this->t('Invoice Number');
    $header['direction'] = $this->t('Direction');
    $header['format'] = $this->t('Format');
    $header['buyer_name'] = $this->t('Buyer');
    $header['total_amount'] = $this->t('Total');
    $header['delivery_status'] = $this->t('Delivery');
    $header['payment_status'] = $this->t('Payment');
    $header['spfe_status'] = $this->t('SPFE');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $directionLabels = [
      'outbound' => $this->t('Outbound'),
      'inbound' => $this->t('Inbound'),
    ];
    $formatLabels = [
      'ubl_2.1' => 'UBL 2.1',
      'facturae_3.2.2' => 'Facturae 3.2.2',
      'cii_d16b' => 'CII D16B',
    ];
    $deliveryLabels = [
      'pending' => $this->t('Pending'),
      'sent' => $this->t('Sent'),
      'delivered' => $this->t('Delivered'),
      'failed' => $this->t('Failed'),
      'rejected' => $this->t('Rejected'),
    ];
    $paymentLabels = [
      'pending' => $this->t('Pending'),
      'paid' => $this->t('Paid'),
      'partial' => $this->t('Partial'),
      'overdue' => $this->t('Overdue'),
      'disputed' => $this->t('Disputed'),
    ];
    $spfeLabels = [
      'not_sent' => $this->t('Not sent'),
      'sent' => $this->t('Sent'),
      'accepted' => $this->t('Accepted'),
      'rejected' => $this->t('Rejected'),
      'error' => $this->t('Error'),
    ];
    $statusLabels = [
      'draft' => $this->t('Draft'),
      'pending' => $this->t('Pending'),
      'validated' => $this->t('Validated'),
      'signed' => $this->t('Signed'),
      'sent' => $this->t('Sent'),
      'delivered' => $this->t('Delivered'),
      'error' => $this->t('Error'),
      'cancelled' => $this->t('Cancelled'),
    ];

    $direction = $entity->get('direction')->value ?? 'outbound';
    $format = $entity->get('format')->value ?? 'ubl_2.1';
    $deliveryStatus = $entity->get('delivery_status')->value ?? 'pending';
    $paymentStatus = $entity->get('payment_status')->value ?? 'pending';
    $spfeStatus = $entity->get('spfe_status')->value ?? 'not_sent';
    $status = $entity->get('status')->value ?? 'draft';
    $totalAmount = $entity->get('total_amount')->value ?? '0.00';
    $currency = $entity->get('currency_code')->value ?? 'EUR';

    $row['invoice_number'] = $entity->get('invoice_number')->value ?? '';
    $row['direction'] = $directionLabels[$direction] ?? $direction;
    $row['format'] = $formatLabels[$format] ?? $format;
    $row['buyer_name'] = $entity->get('buyer_name')->value ?? '';
    $row['total_amount'] = number_format((float) $totalAmount, 2, ',', '.') . ' ' . $currency;
    $row['delivery_status'] = $deliveryLabels[$deliveryStatus] ?? $deliveryStatus;
    $row['payment_status'] = $paymentLabels[$paymentStatus] ?? $paymentStatus;
    $row['spfe_status'] = $spfeLabels[$spfeStatus] ?? $spfeStatus;
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => -10,
        'url' => $entity->toUrl('canonical'),
      ];
    }

    return $operations;
  }

}
