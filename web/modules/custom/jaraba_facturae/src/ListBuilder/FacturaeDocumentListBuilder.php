<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Facturae Document entities.
 */
class FacturaeDocumentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['facturae_number'] = $this->t('Number');
    $header['seller_name'] = $this->t('Issuer');
    $header['buyer_name'] = $this->t('Recipient');
    $header['total_invoice_amount'] = $this->t('Amount');
    $header['status'] = $this->t('Status');
    $header['face_status'] = $this->t('FACe');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['facturae_number'] = $entity->get('facturae_number')->value ?? '';
    $row['seller_name'] = $entity->get('seller_name')->value ?? '';
    $row['buyer_name'] = $entity->get('buyer_name')->value ?? '';
    $row['total_invoice_amount'] = $entity->get('total_invoice_amount')->value ?? '0.00';
    $row['status'] = $entity->get('status')->value ?? 'draft';
    $row['face_status'] = $entity->get('face_status')->value ?? 'not_sent';
    return $row + parent::buildRow($entity);
  }

}
