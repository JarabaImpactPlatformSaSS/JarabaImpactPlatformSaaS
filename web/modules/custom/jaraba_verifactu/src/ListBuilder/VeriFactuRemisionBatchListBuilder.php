<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de lotes de remision AEAT.
 *
 * Muestra estado del batch, registros totales/aceptados/rechazados,
 * entorno AEAT, y timestamps de envio/respuesta.
 */
class VeriFactuRemisionBatchListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 30;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('Batch ID');
    $header['status'] = $this->t('Status');
    $header['total_records'] = $this->t('Total');
    $header['accepted_records'] = $this->t('Accepted');
    $header['rejected_records'] = $this->t('Rejected');
    $header['aeat_environment'] = $this->t('Environment');
    $header['sent_at'] = $this->t('Sent');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'queued' => $this->t('Queued'),
      'sending' => $this->t('Sending'),
      'sent' => $this->t('Sent'),
      'partial_error' => $this->t('Partial Error'),
      'error' => $this->t('Error'),
    ];

    $env_labels = [
      'production' => $this->t('Production'),
      'testing' => $this->t('Testing'),
    ];

    $status = $entity->get('status')->value;
    $environment = $entity->get('aeat_environment')->value;
    $sent_at = $entity->get('sent_at')->value;

    $row['id'] = $entity->id();
    $row['status'] = $status_labels[$status] ?? $status;
    $row['total_records'] = $entity->get('total_records')->value ?? '0';
    $row['accepted_records'] = $entity->get('accepted_records')->value ?? '0';
    $row['rejected_records'] = $entity->get('rejected_records')->value ?? '0';
    $row['aeat_environment'] = $env_labels[$environment] ?? $environment;
    $row['sent_at'] = $sent_at ? date('d/m/Y H:i', (int) $sent_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = [];
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 0,
        'url' => $entity->toUrl('canonical'),
      ];
    }
    return $operations;
  }

}
