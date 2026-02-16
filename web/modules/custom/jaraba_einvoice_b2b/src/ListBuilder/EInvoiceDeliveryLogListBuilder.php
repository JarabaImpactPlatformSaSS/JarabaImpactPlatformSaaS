<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for EInvoice Delivery Log entities.
 *
 * Append-only log: no edit/delete operations. Only view is available.
 * Displays operation, channel, response code, HTTP status, duration,
 * and created timestamp.
 *
 * Spec: Doc 181, Section 2.3.
 */
class EInvoiceDeliveryLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['einvoice_document_id'] = $this->t('Document ID');
    $header['operation'] = $this->t('Operation');
    $header['channel'] = $this->t('Channel');
    $header['response_code'] = $this->t('Response');
    $header['http_status'] = $this->t('HTTP');
    $header['duration_ms'] = $this->t('Duration (ms)');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $operationLabels = [
      'send' => $this->t('Send'),
      'receive' => $this->t('Receive'),
      'payment_status' => $this->t('Payment Status'),
      'spfe_submit' => $this->t('SPFE Submit'),
      'spfe_query' => $this->t('SPFE Query'),
      'validation' => $this->t('Validation'),
    ];
    $channelLabels = [
      'spfe' => $this->t('SPFE'),
      'email' => $this->t('Email'),
      'peppol' => $this->t('Peppol'),
      'platform' => $this->t('Platform'),
      'api' => $this->t('API'),
    ];

    $operation = $entity->get('operation')->value ?? '';
    $channel = $entity->get('channel')->value ?? '';

    $row['einvoice_document_id'] = $entity->get('einvoice_document_id')->value ?? '';
    $row['operation'] = $operationLabels[$operation] ?? $operation;
    $row['channel'] = $channelLabels[$channel] ?? $channel;
    $row['response_code'] = $entity->get('response_code')->value ?? '-';
    $row['http_status'] = $entity->get('http_status')->value ?? '-';
    $row['duration_ms'] = $entity->get('duration_ms')->value ?? '-';
    $row['created'] = date('d/m/Y H:i:s', (int) $entity->get('created')->value);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    // Append-only log: no edit/delete operations.
    return [];
  }

}
