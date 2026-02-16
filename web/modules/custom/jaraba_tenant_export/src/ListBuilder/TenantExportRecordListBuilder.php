<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de exportaciones de tenant en admin.
 */
class TenantExportRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['tenant_id'] = $this->t('Tenant');
    $header['export_type'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['progress'] = $this->t('Progreso');
    $header['file_size'] = $this->t('Tamaño');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $statusLabels = [
      'queued' => $this->t('En cola'),
      'collecting' => $this->t('Recopilando'),
      'packaging' => $this->t('Empaquetando'),
      'completed' => $this->t('Completado'),
      'failed' => $this->t('Fallido'),
      'expired' => $this->t('Expirado'),
      'cancelled' => $this->t('Cancelado'),
    ];

    $typeLabels = [
      'full' => $this->t('Completa'),
      'partial' => $this->t('Parcial'),
      'gdpr_portability' => $this->t('GDPR'),
    ];

    $status = $entity->get('status')->value;
    $type = $entity->get('export_type')->value;
    $fileSize = (int) ($entity->get('file_size')->value ?? 0);
    $created = (int) $entity->get('created')->value;

    $row['id'] = $entity->id();
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['export_type'] = $typeLabels[$type] ?? $type;
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['progress'] = $entity->get('progress')->value . '%';
    $row['file_size'] = $fileSize > 0 ? $this->formatBytes($fileSize) : '-';
    $row['created'] = $created ? date('d/m/Y H:i', $created) : '-';
    return $row + parent::buildRow($entity);
  }

  /**
   * Formatea bytes a formato legible.
   */
  protected function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) {
      return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
      return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
  }

}
