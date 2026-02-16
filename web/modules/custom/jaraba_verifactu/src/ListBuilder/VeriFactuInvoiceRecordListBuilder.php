<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros VeriFactu en admin.
 *
 * Muestra numero de factura, tipo, importe total, estado AEAT,
 * y fecha de creacion. No incluye operaciones de editar/eliminar
 * porque los registros son append-only.
 */
class VeriFactuInvoiceRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['numero_factura'] = $this->t('Invoice Number');
    $header['record_type'] = $this->t('Type');
    $header['tipo_factura'] = $this->t('Invoice Type');
    $header['importe_total'] = $this->t('Total');
    $header['aeat_status'] = $this->t('AEAT Status');
    $header['fecha_expedicion'] = $this->t('Issue Date');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'alta' => $this->t('Alta'),
      'anulacion' => $this->t('Anulacion'),
    ];

    $status_labels = [
      'pending' => $this->t('Pending'),
      'accepted' => $this->t('Accepted'),
      'rejected' => $this->t('Rejected'),
      'error' => $this->t('Error'),
    ];

    $record_type = $entity->get('record_type')->value;
    $aeat_status = $entity->get('aeat_status')->value;
    $fecha = $entity->get('fecha_expedicion')->value;

    $row['numero_factura'] = $entity->get('numero_factura')->value ?? '-';
    $row['record_type'] = $type_labels[$record_type] ?? $record_type;
    $row['tipo_factura'] = $entity->get('tipo_factura')->value ?? '-';
    $row['importe_total'] = $entity->get('importe_total')->value ?? '0.00';
    $row['aeat_status'] = $status_labels[$aeat_status] ?? $aeat_status;
    $row['fecha_expedicion'] = $fecha ? date('d/m/Y', strtotime($fecha)) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * No operations for append-only records. Only view is permitted.
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
